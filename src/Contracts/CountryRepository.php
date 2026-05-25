<?php

namespace Imujas9\World\Contracts;

use Imujas9\World\DTO\CountryData;
use Imujas9\World\Query\WorldQueryBuilder;
use Illuminate\Support\Collection;

interface CountryRepository
{
    public function newQuery(): WorldQueryBuilder;

    public function all(): Collection;

    public function find(int $id): ?CountryData;

    public function findByCode(string $code): ?CountryData;

    public function lang(string ...$langs): WorldQueryBuilder;
}
