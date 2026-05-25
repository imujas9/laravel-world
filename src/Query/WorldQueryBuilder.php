<?php

namespace Imujas9\World\Query;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class WorldQueryBuilder
{
    protected array   $langs        = [];
    protected array   $wheres       = [];
    protected ?int    $limitValue   = null;
    protected ?int    $offsetValue  = null;
    protected ?string $orderByField = null;
    protected string  $orderDir     = 'asc';

    public function __construct(
        protected readonly Closure $executor,
        protected readonly string  $defaultLang,
    ) {}

    public function lang(string ...$langs): static
    {
        $this->langs = $langs;
        return $this;
    }

    public function whereCountry(string $code): static
    {
        $this->wheres['country_code'] = strtoupper($code);
        return $this;
    }

    public function whereState(string $code): static
    {
        $this->wheres['state_code'] = strtoupper($code);
        return $this;
    }

    public function whereRegion(string $region): static
    {
        $this->wheres['region'] = $region;
        return $this;
    }

    public function where(string $field, mixed $value): static
    {
        $this->wheres[$field] = $value;
        return $this;
    }

    public function limit(int $value): static
    {
        $this->limitValue = $value;
        return $this;
    }

    public function offset(int $value): static
    {
        $this->offsetValue = $value;
        return $this;
    }

    public function orderBy(string $field, string $direction = 'asc'): static
    {
        $this->orderByField = $field;
        $this->orderDir     = strtolower($direction);
        return $this;
    }

    public function get(): Collection
    {
        return ($this->executor)($this);
    }

    public function first(): mixed
    {
        $this->limitValue = 1;
        return $this->get()->first();
    }

    public function count(): int
    {
        return $this->get()->count();
    }

    public function paginate(int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $results = $this->get();
        $total   = $results->count();
        $items   = $results->forPage($page, $perPage)->values();

        return new LengthAwarePaginator($items, $total, $perPage, $page);
    }

    public function getLangs(): array
    {
        return $this->langs ?: [$this->defaultLang];
    }

    public function getWheres(): array
    {
        return $this->wheres;
    }

    public function getLimitValue(): ?int
    {
        return $this->limitValue;
    }

    public function getOffsetValue(): ?int
    {
        return $this->offsetValue;
    }

    public function getOrderByField(): ?string
    {
        return $this->orderByField;
    }

    public function getOrderDir(): string
    {
        return $this->orderDir;
    }
}
