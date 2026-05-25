<?php

namespace Imujas9\World\Repositories\File;

use Imujas9\World\Contracts\CountryRepository;
use Imujas9\World\DTO\CountryData;
use Imujas9\World\Query\WorldQueryBuilder;
use Illuminate\Support\Collection;

class FileCountryRepository implements CountryRepository
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

    public function find(int $id): ?CountryData
    {
        return $this->newQuery()->where('id', $id)->first();
    }

    public function findByCode(string $code): ?CountryData
    {
        return $this->newQuery()->where('code', strtoupper($code))->first();
    }

    public function lang(string ...$langs): WorldQueryBuilder
    {
        return $this->newQuery()->lang(...$langs);
    }

    public function __call(string $name, array $args): WorldQueryBuilder
    {
        return $this->newQuery()->{$name}(...$args);
    }

    private function execute(WorldQueryBuilder $query): Collection
    {
        $rows  = $this->loader->load('countries.json');
        $langs = $query->getLangs();

        $translations = [];
        foreach ($langs as $lang) {
            $translations[$lang] = $this->loader->loadTranslation('countries', $lang);
        }

        $fallback = $this->loader->loadTranslation('countries', $this->fallbackLang);

        $collection = collect($rows)
            ->filter(fn ($row) => $this->applyWheres($row, $query->getWheres()))
            ->map(function ($row) use ($langs, $translations, $fallback) {
                $resolved = [];
                foreach ($langs as $lang) {
                    $code = $row['code'];
                    $resolved[$lang] = $translations[$lang][$code]
                        ?? $fallback[$code]
                        ?? null;
                }
                return CountryData::fromArray($row, $resolved);
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
        foreach ($wheres as $field => $value) {
            $rowValue = $row[$field] ?? null;
            if (strtolower((string) $rowValue) !== strtolower((string) $value)) {
                return false;
            }
        }
        return true;
    }

    private function applyOrder(Collection $collection, WorldQueryBuilder $query): Collection
    {
        $field = $query->getOrderByField();
        if ($field === null) {
            return $collection;
        }

        return $query->getOrderDir() === 'desc'
            ? $collection->sortByDesc(fn (CountryData $c) => $this->getSortValue($c, $field))
            : $collection->sortBy(fn (CountryData $c) => $this->getSortValue($c, $field));
    }

    private function getSortValue(CountryData $item, string $field): mixed
    {
        return match ($field) {
            'name'  => $item->name,
            default => $item->{$field} ?? null,
        };
    }
}
