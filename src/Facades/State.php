<?php

namespace Imujas9\World\Facades;

use Illuminate\Support\Facades\Facade;
use Imujas9\World\Contracts\StateRepository;
use Imujas9\World\Query\WorldQueryBuilder;
use Illuminate\Support\Collection;

/**
 * @method static Collection                     all()
 * @method static \Imujas9\World\DTO\StateData|null find(int $id)
 * @method static \Imujas9\World\DTO\StateData|null findByCode(string $code)
 * @method static WorldQueryBuilder              lang(string ...$langs)
 * @method static WorldQueryBuilder              whereCountry(string $countryCode)
 * @method static WorldQueryBuilder              where(string $field, mixed $value)
 * @method static WorldQueryBuilder              orderBy(string $field, string $direction = 'asc')
 * @method static WorldQueryBuilder              limit(int $value)
 * @method static WorldQueryBuilder              newQuery()
 *
 * @see StateRepository
 */
class State extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StateRepository::class;
    }
}
