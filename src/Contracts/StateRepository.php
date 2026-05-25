<?php

namespace Imujas9\World\Contracts;

use Imujas9\World\DTO\StateData;
use Imujas9\World\Query\WorldQueryBuilder;
use Illuminate\Support\Collection;

interface StateRepository
{
    public function newQuery(): WorldQueryBuilder;

    public function all(): Collection;

    public function find(int $id): ?StateData;

    public function findByCode(string $code): ?StateData;

    public function lang(string ...$langs): WorldQueryBuilder;

    public function whereCountry(string $countryCode): WorldQueryBuilder;
}
