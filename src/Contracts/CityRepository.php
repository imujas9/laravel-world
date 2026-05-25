<?php

namespace Imujas9\World\Contracts;

use Imujas9\World\DTO\CityData;
use Imujas9\World\Query\WorldQueryBuilder;
use Illuminate\Support\Collection;

interface CityRepository
{
    public function newQuery(): WorldQueryBuilder;

    public function all(): Collection;

    public function find(int $id): ?CityData;

    public function lang(string ...$langs): WorldQueryBuilder;

    public function whereCountry(string $countryCode): WorldQueryBuilder;

    public function whereState(string $stateCode): WorldQueryBuilder;
}
