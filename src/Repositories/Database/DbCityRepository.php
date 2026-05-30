<?php

namespace Imujas9\World\Repositories\Database;

use Imujas9\World\Contracts\CityRepository;
use Imujas9\World\DTO\CityData;
use Imujas9\World\Models\City;
use Imujas9\World\Query\WorldQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DbCityRepository implements CityRepository
{
    public function __construct(private readonly string $defaultLang) {}

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
        $langs   = $query->getLangs();
        $builder = City::query();

        $this->applyWheres($builder, $query->getWheres());

        if ($query->getOrderByField()) {
            $builder->orderBy($query->getOrderByField(), $query->getOrderDir());
        }

        if ($query->getLimitValue() !== null) {
            $builder->limit($query->getLimitValue());
        }

        if ($query->getOffsetValue() !== null) {
            $builder->offset($query->getOffsetValue());
        }

        return $builder->get()->map(function (City $model) use ($langs) {
            $translations = $model->translations ?? [];
            $resolved     = [];

            foreach ($langs as $lang) {
                $resolved[$lang] = $translations[$lang]
                    ?? $model->name
                    ?? null;
            }

            return CityData::fromArray($model->toArray(), $resolved);
        });
    }

    private function executeCount(WorldQueryBuilder $query): int
    {
        $builder = City::query();
        $this->applyWheres($builder, $query->getWheres());
        return $builder->count();
    }

    private function applyWheres(Builder $builder, array $wheres): void
    {
        foreach ($wheres as [$field, $operator, $value]) {
            match ($operator) {
                'in'     => $builder->whereIn($field, $value),
                'not in' => $builder->whereNotIn($field, $value),
                'like'   => $builder->where($field, 'LIKE', $value),
                default  => $builder->where($field, $operator, $value),
            };
        }
    }
}
