<?php

namespace Imujas9\World\Repositories\Database;

use Imujas9\World\Contracts\CountryRepository;
use Imujas9\World\DTO\CountryData;
use Imujas9\World\Models\Country;
use Imujas9\World\Query\WorldQueryBuilder;
use Illuminate\Support\Collection;

class DbCountryRepository implements CountryRepository
{
    public function __construct(private readonly string $defaultLang) {}

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
        $model = Country::find($id);
        return $model ? $this->toDto($model, [$this->defaultLang]) : null;
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
        $langs   = $query->getLangs();
        $wheres  = $query->getWheres();
        $builder = Country::query();

        foreach ($wheres as $field => $value) {
            $builder->where($field, $value);
        }

        if ($query->getOrderByField()) {
            $builder->orderBy($query->getOrderByField(), $query->getOrderDir());
        }

        if ($query->getLimitValue() !== null) {
            $builder->limit($query->getLimitValue());
        }

        if ($query->getOffsetValue() !== null) {
            $builder->offset($query->getOffsetValue());
        }

        return $builder->get()->map(fn (Country $model) => $this->toDto($model, $langs));
    }

    private function toDto(Country $model, array $langs): CountryData
    {
        $translations = $model->translations ?? [];
        $resolved     = [];

        foreach ($langs as $lang) {
            $resolved[$lang] = $translations[$lang]
                ?? $translations[$this->defaultLang]
                ?? null;
        }

        return CountryData::fromArray($model->toArray(), $resolved);
    }
}
