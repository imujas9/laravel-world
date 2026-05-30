<?php

namespace Imujas9\World\Repositories\File;

use Imujas9\World\Contracts\StateRepository;
use Imujas9\World\DTO\StateData;
use Imujas9\World\Query\WorldQueryBuilder;
use Illuminate\Support\Collection;

class FileStateRepository implements StateRepository
{
    public function __construct(
        private readonly FileDataLoader $loader,
        private readonly string         $defaultLang,
        private readonly string         $fallbackLang = 'en',
    ) {}

    public function newQuery(): WorldQueryBuilder
    {
        return new WorldQueryBuilder(
            executor:    fn (WorldQueryBuilder $q) => $this->execute($q),
            defaultLang: $this->defaultLang,
        );
    }

    public function all(): Collection
    {
        return $this->newQuery()->get();
    }

    public function find(int $id): ?StateData
    {
        return $this->newQuery()->where('id', $id)->first();
    }

    public function findByCode(string $code): ?StateData
    {
        return $this->newQuery()->where('code', strtoupper($code))->first();
    }

    public function lang(string ...$langs): WorldQueryBuilder
    {
        return $this->newQuery()->lang(...$langs);
    }

    public function whereCountry(string $countryCode): WorldQueryBuilder
    {
        return $this->newQuery()->whereCountry($countryCode);
    }

    public function __call(string $name, array $args): WorldQueryBuilder
    {
        return $this->newQuery()->{$name}(...$args);
    }

    private function execute(WorldQueryBuilder $query): Collection
    {
        $rows  = $this->loader->load('states.json');
        $langs = $query->getLangs();

        $translations = [];
        foreach ($langs as $lang) {
            $translations[$lang] = $this->loader->loadTranslation('states', $lang);
        }

        $fallback = $this->loader->loadTranslation('states', $this->fallbackLang);

        $collection = collect($rows)
            ->filter(fn ($row) => $this->applyWheres($row, $query->getWheres()))
            ->map(function ($row) use ($langs, $translations, $fallback) {
                $resolved = [];
                foreach ($langs as $lang) {
                    $code            = $row['code'];
                    $resolved[$lang] = $translations[$lang][$code]
                        ?? $fallback[$code]
                        ?? $row['name']
                        ?? null;
                }
                return StateData::fromArray($row, $resolved);
            });

        $collection = $this->applyOrder($collection, $query);

        if ($query->getOffsetValue() !== null) {
            $collection = $collection->slice($query->getOffsetValue());
        }

        if ($query->getLimitValue() !== null) {
            $collection = $collection->take($query->getLimitValue());
        }

        return $collection->values();
    }

    private function applyWheres(array $row, array $wheres): bool
    {
        foreach ($wheres as [$field, $operator, $value]) {
            $rowValue = $row[$field] ?? null;

            $matched = match ($operator) {
                '='      => strtolower((string) $rowValue) === strtolower((string) $value),
                '!='     => strtolower((string) $rowValue) !== strtolower((string) $value),
                '>'      => (float) $rowValue > (float) $value,
                '>='     => (float) $rowValue >= (float) $value,
                '<'      => (float) $rowValue < (float) $value,
                '<='     => (float) $rowValue <= (float) $value,
                'like'   => $this->matchLike((string) $rowValue, (string) $value),
                'in'     => in_array(
                    strtolower((string) $rowValue),
                    array_map(fn ($v) => strtolower((string) $v), $value),
                    true
                ),
                'not in' => ! in_array(
                    strtolower((string) $rowValue),
                    array_map(fn ($v) => strtolower((string) $v), $value),
                    true
                ),
                default => false,
            };

            if (! $matched) {
                return false;
            }
        }

        return true;
    }

    private function matchLike(string $haystack, string $pattern): bool
    {
        $regex = '/^' . str_replace(['%', '_'], ['.*', '.'], preg_quote($pattern, '/')) . '$/isu';
        return (bool) preg_match($regex, $haystack);
    }

    private function applyOrder(Collection $collection, WorldQueryBuilder $query): Collection
    {
        $field = $query->getOrderByField();
        if ($field === null) {
            return $collection;
        }

        return $query->getOrderDir() === 'desc'
            ? $collection->sortByDesc(fn (StateData $s) => $s->{$field} ?? null)
            : $collection->sortBy(fn (StateData $s) => $s->{$field} ?? null);
    }
}
