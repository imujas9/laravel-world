<?php

namespace Imujas9\World\Repositories\File;

use Imujas9\World\Contracts\CityRepository;
use Imujas9\World\DTO\CityData;
use Imujas9\World\Query\WorldQueryBuilder;
use Illuminate\Support\Collection;

class FileCityRepository implements CityRepository
{
    public function __construct(
        private readonly FileDataLoader $loader,
        private readonly string         $defaultLang,
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

    public function find(int $id): ?CityData
    {
        return $this->newQuery()->where('id', $id)->first();
    }

    public function lang(string ...$langs): WorldQueryBuilder
    {
        return $this->newQuery()->lang(...$langs);
    }

    public function whereCountry(string $countryCode): WorldQueryBuilder
    {
        return $this->newQuery()->whereCountry($countryCode);
    }

    public function whereState(string $stateCode): WorldQueryBuilder
    {
        return $this->newQuery()->whereState($stateCode);
    }

    public function __call(string $name, array $args): WorldQueryBuilder
    {
        return $this->newQuery()->{$name}(...$args);
    }

    private function execute(WorldQueryBuilder $query): Collection
    {
        $wheres = $query->getWheres();
        $langs  = $query->getLangs();

        $translations = [];
        foreach ($langs as $lang) {
            $translations[$lang] = $this->loader->loadTranslation('cities', $lang);
        }

        // Stream cities.json one record at a time to avoid loading 30 MB into memory.
        // Only matching records are accumulated, so a country-filtered query uses a
        // fraction of the memory compared to loading the full array.
        $items = [];
        foreach ($this->loader->stream('cities.json') as $row) {
            if (! $this->applyWheres($row, $wheres)) {
                continue;
            }

            $resolved = [];
            foreach ($langs as $lang) {
                $id              = (string) $row['id'];
                $resolved[$lang] = $translations[$lang][$id] ?? $row['name'] ?? null;
            }

            $items[] = CityData::fromArray($row, $resolved);
        }

        $collection = collect($items);
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
            ? $collection->sortByDesc(fn (CityData $c) => $c->{$field} ?? null)
            : $collection->sortBy(fn (CityData $c) => $c->{$field} ?? null);
    }
}
