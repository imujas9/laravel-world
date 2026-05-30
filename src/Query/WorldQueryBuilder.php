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
        protected readonly Closure  $executor,
        protected readonly string   $defaultLang,
        protected readonly ?Closure $totalExecutor = null,
    ) {}

    public function lang(string ...$langs): static
    {
        $this->langs = $langs;
        return $this;
    }

    public function whereCountry(string $code): static
    {
        $this->wheres[] = ['country_code', '=', strtoupper($code)];
        return $this;
    }

    public function whereState(string $code): static
    {
        $this->wheres[] = ['state_code', '=', strtoupper($code)];
        return $this;
    }

    public function whereRegion(string $region): static
    {
        $this->wheres[] = ['region', '=', $region];
        return $this;
    }

    /**
     * Add a filter condition.
     *
     * Two-argument form:  where('code', 'US')            — equality
     * Three-argument form: where('name', 'like', 'New%') — with operator
     */
    public function where(string $field, mixed $operatorOrValue, mixed $value = null): static
    {
        if ($value === null) {
            $this->wheres[] = [$field, '=', $operatorOrValue];
        } else {
            $this->wheres[] = [$field, strtolower((string) $operatorOrValue), $value];
        }
        return $this;
    }

    public function whereLike(string $field, string $value): static
    {
        $this->wheres[] = [$field, 'like', $value];
        return $this;
    }

    public function whereIn(string $field, array $values): static
    {
        $this->wheres[] = [$field, 'in', $values];
        return $this;
    }

    /**
     * Full-text partial search on a field (case-insensitive).
     */
    public function search(string $term, string $field = 'name'): static
    {
        return $this->whereLike($field, '%' . $term . '%');
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
        // request() is only available inside a Laravel HTTP context
        $request = app()->bound('request') ? request() : null;
        $page    = (int) ($request?->input('page') ?? $page);

        $total = $this->getTotal();

        $this->limitValue  = $perPage;
        $this->offsetValue = ($page - 1) * $perPage;
        $items = $this->get();

        $paginator = new LengthAwarePaginator($items, $total, $perPage, $page);

        if ($request !== null) {
            $paginator
                ->withPath($request->url())
                ->appends($request->except('page'));
        }

        return $paginator;
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

    private function getTotal(): int
    {
        if ($this->totalExecutor !== null) {
            return ($this->totalExecutor)($this);
        }

        $savedLimit        = $this->limitValue;
        $savedOffset       = $this->offsetValue;
        $this->limitValue  = null;
        $this->offsetValue = null;
        $total             = $this->get()->count();
        $this->limitValue  = $savedLimit;
        $this->offsetValue = $savedOffset;

        return $total;
    }
}
