<?php

namespace Imujas9\World\Repositories\File;

use Imujas9\World\Contracts\CityRepository;
use Imujas9\World\DTO\CityData;
use Imujas9\World\Query\WorldQueryBuilder;
use Illuminate\Support\Collection;

class FileCityRepository implements CityRepository
{
    use AppliesFileWheres;

    public function __construct(
        private readonly FileDataLoader $loader,
        private readonly string         $defaultLang,
    ) {}

    public function newQuery(): WorldQueryBuilder
    {
        return new WorldQueryBuilder(
            executor:      fn (WorldQueryBuilder $q) => $this->execute($q),
            defaultLang:   $this->defaultLang,
            totalExecutor: fn (WorldQueryBuilder $q) => $this->executeCount($q),
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
        $limit  = $query->getLimitValue();
        $offset = $query->getOffsetValue() ?? 0;
        $order  = $query->getOrderByField();

        $translations = [];
        foreach ($langs as $lang) {
            $translations[$lang] = $this->loader->loadTranslation('cities', $lang);
        }

        // When ordering is requested we must collect all matching records first,
        // sort them, then slice — there is no way to do this in a single forward pass.
        if ($order !== null) {
            $items = $this->collectAll($wheres, $langs, $translations);
            $collection = $this->applyOrder(collect($items), $query);

            if ($offset > 0) {
                $collection = $collection->slice($offset);
            }

            if ($limit !== null) {
                $collection = $collection->take($limit);
            }

            return $collection->values();
        }

        // No ordering — stream with offset skip and early exit on limit.
        // City::find(1) and paginate() both benefit from this path.
        $items   = [];
        $skipped = 0;

        foreach ($this->loader->stream('cities.json') as $row) {
            if (! $this->applyWheres($row, $wheres)) {
                continue;
            }

            if ($skipped < $offset) {
                $skipped++;
                continue;
            }

            $items[] = $this->toDto($row, $langs, $translations);

            if ($limit !== null && count($items) >= $limit) {
                break;
            }
        }

        return collect($items);
    }

    private function executeCount(WorldQueryBuilder $query): int
    {
        $wheres = $query->getWheres();
        $count  = 0;

        foreach ($this->loader->stream('cities.json') as $row) {
            if ($this->applyWheres($row, $wheres)) {
                $count++;
            }
        }

        return $count;
    }

    private function collectAll(array $wheres, array $langs, array $translations): array
    {
        $items = [];

        foreach ($this->loader->stream('cities.json') as $row) {
            if ($this->applyWheres($row, $wheres)) {
                $items[] = $this->toDto($row, $langs, $translations);
            }
        }

        return $items;
    }

    private function toDto(array $row, array $langs, array $translations): CityData
    {
        $resolved = [];
        foreach ($langs as $lang) {
            $id              = (string) $row['id'];
            $resolved[$lang] = $translations[$lang][$id] ?? $row['name'] ?? null;
        }

        return CityData::fromArray($row, $resolved);
    }
}
