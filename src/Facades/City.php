<?php

namespace Imujas9\World\Facades;

use Illuminate\Support\Facades\Facade;
use Imujas9\World\Contracts\CityRepository;
use Imujas9\World\Query\WorldQueryBuilder;
use Illuminate\Support\Collection;

/**
 * @method static Collection                       all()
 * @method static \Imujas9\World\DTO\CityData|null find(int $id)
 * @method static WorldQueryBuilder                lang(string ...$langs)
 * @method static WorldQueryBuilder                whereCountry(string $countryCode)
 * @method static WorldQueryBuilder                whereState(string $stateCode)
 * @method static WorldQueryBuilder                where(string $field, mixed $operatorOrValue, mixed $value = null)
 * @method static WorldQueryBuilder                whereLike(string $field, string $value)
 * @method static WorldQueryBuilder                whereIn(string $field, array $values)
 * @method static WorldQueryBuilder                search(string $term, string $field = 'name')
 * @method static WorldQueryBuilder                orderBy(string $field, string $direction = 'asc')
 * @method static WorldQueryBuilder                limit(int $value)
 * @method static WorldQueryBuilder                offset(int $value)
 * @method static WorldQueryBuilder                newQuery()
 *
 * @see CityRepository
 */
class City extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CityRepository::class;
    }
}
