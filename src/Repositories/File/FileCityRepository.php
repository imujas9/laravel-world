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
        $rows  = $this->loader->load('cities.json');
        $langs = $query->getLangs();

        $translations = [];
        foreach ($langs as $lang) {
            $translations[$lang] = $this->loader->loadTranslation('cities', $lang);
        }

        $collection = collect($rows)
            ->filter(fn ($row) => $this->applyWheres($row, $query->getWheres()))
            ->map(function ($row) use ($langs, $translations) {
                $resolved = [];
                foreach ($langs as $lang) {
                    $id = (string) $row['id'];
                    // Cities fall back to the English name stored in cities.json itself
                    $resolved[$lang] = $translations[$lang][$id] ?? $row['name'] ?? null;
                }
                return CityData::fromArray($row, $resolved);
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
            ? $collection->sortByDesc(fn (CityData $c) => $c->{$field} ?? null)
            : $collection->sortBy(fn (CityData $c) => $c->{$field} ?? null);
    }
}
