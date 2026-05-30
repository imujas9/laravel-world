<?php

namespace Imujas9\World\Repositories\File;

use Imujas9\World\Query\WorldQueryBuilder;
use Illuminate\Support\Collection;

trait AppliesFileWheres
{
    private function applyWheres(array $row, array $wheres): bool
    {
        foreach ($wheres as [$field, $operator, $value]) {
            $rowValue = $row[$field] ?? null;

            $matched = match ($operator) {
                '='      => strtolower((string) $rowValue) === strtolower((string) $value),
                '!='     => strtolower((string) $rowValue) !== strtolower((string) $value),
                '>'      => (float) $rowValue > (float) $value,
                '>='     => (float) $rowValue >= (float) $value,
                '<'      => (float) $rowValue < (float) $value,
                '<='     => (float) $rowValue <= (float) $value,
                'like'   => $this->matchLike((string) $rowValue, (string) $value),
                'in'     => in_array(
                    strtolower((string) $rowValue),
                    array_map(fn ($v) => strtolower((string) $v), $value),
                    true
                ),
                'not in' => ! in_array(
                    strtolower((string) $rowValue),
                    array_map(fn ($v) => strtolower((string) $v), $value),
                    true
                ),
                default => false,
            };

            if (! $matched) {
                return false;
            }
        }

        return true;
    }

    private function matchLike(string $haystack, string $pattern): bool
    {
        $regex = '/^' . str_replace(['%', '_'], ['.*', '.'], preg_quote($pattern, '/')) . '$/isu';
        return (bool) preg_match($regex, $haystack);
    }

    private function applyOrder(Collection $collection, WorldQueryBuilder $query): Collection
    {
        $field = $query->getOrderByField();
        if ($field === null) {
            return $collection;
        }

        return $query->getOrderDir() === 'desc'
            ? $collection->sortByDesc(fn ($item) => $item->{$field} ?? null)
            : $collection->sortBy(fn ($item) => $item->{$field} ?? null);
    }
}
