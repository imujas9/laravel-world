<?php

namespace Imujas9\World\Facades;

use Illuminate\Support\Facades\Facade;
use Imujas9\World\Contracts\CountryRepository;
use Imujas9\World\Query\WorldQueryBuilder;
use Illuminate\Support\Collection;

/**
 * @method static Collection                      all()
 * @method static \Imujas9\World\DTO\CountryData|null find(int $id)
 * @method static \Imujas9\World\DTO\CountryData|null findByCode(string $code)
 * @method static WorldQueryBuilder               lang(string ...$langs)
 * @method static WorldQueryBuilder               where(string $field, mixed $value)
 * @method static WorldQueryBuilder               whereRegion(string $region)
 * @method static WorldQueryBuilder               orderBy(string $field, string $direction = 'asc')
 * @method static WorldQueryBuilder               limit(int $value)
 * @method static WorldQueryBuilder               newQuery()
 *
 * @see CountryRepository
 */
class Country extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CountryRepository::class;
    }
}
