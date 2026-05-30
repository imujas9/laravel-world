<?php

namespace Imujas9\World\Repositories\Database;

use Imujas9\World\Contracts\StateRepository;
use Imujas9\World\DTO\StateData;
use Imujas9\World\Models\State;
use Imujas9\World\Query\WorldQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DbStateRepository implements StateRepository
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

    public function find(int $id): ?StateData
    {
        $model = State::find($id);
        return $model ? $this->toDto($model, [$this->defaultLang]) : null;
    }

    public function findByCode(string $code, ?string $countryCode = null): ?StateData
    {
        $query = $this->newQuery()->where('code', strtoupper($code));

        if ($countryCode !== null) {
            $query->whereCountry($countryCode);
        }

        return $query->first();
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
        $langs   = $query->getLangs();
        $builder = State::query();

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

        return $builder->get()->map(fn (State $model) => $this->toDto($model, $langs));
    }

    private function executeCount(WorldQueryBuilder $query): int
    {
        $builder = State::query();
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

    private function toDto(State $model, array $langs): StateData
    {
        $translations = $model->translations ?? [];
        $resolved     = [];

        foreach ($langs as $lang) {
            $resolved[$lang] = $translations[$lang]
                ?? $translations[$this->defaultLang]
                ?? $model->name
                ?? null;
        }

        return StateData::fromArray($model->toArray(), $resolved);
    }
}
