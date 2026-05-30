<?php

namespace Imujas9\World\Tests\Unit\Query;

use PHPUnit\Framework\TestCase;
use Imujas9\World\Query\WorldQueryBuilder;
use Illuminate\Support\Collection;

class WorldQueryBuilderTest extends TestCase
{
    private function makeBuilder(array $rows = []): WorldQueryBuilder
    {
        return new WorldQueryBuilder(
            executor:    fn ($q) => collect($rows),
            defaultLang: 'en',
        );
    }

    public function test_get_calls_executor(): void
    {
        $called  = false;
        $builder = new WorldQueryBuilder(
            executor:    function () use (&$called) { $called = true; return collect(); },
            defaultLang: 'en',
        );

        $builder->get();
        $this->assertTrue($called);
    }

    public function test_lang_sets_single_language(): void
    {
        $builder = $this->makeBuilder();
        $builder->lang('hi');

        $this->assertSame(['hi'], $builder->getLangs());
    }

    public function test_lang_sets_multiple_languages(): void
    {
        $builder = $this->makeBuilder();
        $builder->lang('en', 'hi', 'fr');

        $this->assertSame(['en', 'hi', 'fr'], $builder->getLangs());
    }

    public function test_default_lang_used_when_lang_not_called(): void
    {
        $builder = new WorldQueryBuilder(fn () => collect(), 'hi');

        $this->assertSame(['hi'], $builder->getLangs());
    }

    // --- getWheres() returns array of [field, operator, value] triples ---

    public function test_where_country_stores_triple(): void
    {
        $builder = $this->makeBuilder();
        $builder->whereCountry('in');

        $this->assertSame([['country_code', '=', 'IN']], $builder->getWheres());
    }

    public function test_where_state_stores_triple(): void
    {
        $builder = $this->makeBuilder();
        $builder->whereState('gj');

        $this->assertSame([['state_code', '=', 'GJ']], $builder->getWheres());
    }

    public function test_where_region_stores_triple(): void
    {
        $builder = $this->makeBuilder();
        $builder->whereRegion('Asia');

        $this->assertSame([['region', '=', 'Asia']], $builder->getWheres());
    }

    public function test_where_two_args_stores_equality_triple(): void
    {
        $builder = $this->makeBuilder();
        $builder->where('currency', 'INR');

        $this->assertSame([['currency', '=', 'INR']], $builder->getWheres());
    }

    public function test_where_three_args_stores_operator_triple(): void
    {
        $builder = $this->makeBuilder();
        $builder->where('id', '>', 5);

        $this->assertSame([['id', '>', 5]], $builder->getWheres());
    }

    public function test_where_like_stores_like_triple(): void
    {
        $builder = $this->makeBuilder();
        $builder->whereLike('name', 'New%');

        $this->assertSame([['name', 'like', 'New%']], $builder->getWheres());
    }

    public function test_where_in_stores_in_triple(): void
    {
        $builder = $this->makeBuilder();
        $builder->whereIn('code', ['IN', 'US', 'FR']);

        $this->assertSame([['code', 'in', ['IN', 'US', 'FR']]], $builder->getWheres());
    }

    public function test_search_adds_like_triple_on_name(): void
    {
        $builder = $this->makeBuilder();
        $builder->search('mumbai');

        $this->assertSame([['name', 'like', '%mumbai%']], $builder->getWheres());
    }

    public function test_search_accepts_custom_field(): void
    {
        $builder = $this->makeBuilder();
        $builder->search('delhi', 'capital');

        $this->assertSame([['capital', 'like', '%delhi%']], $builder->getWheres());
    }

    public function test_multiple_wheres_accumulate(): void
    {
        $builder = $this->makeBuilder();
        $builder->whereCountry('IN')->whereState('GJ');

        $this->assertCount(2, $builder->getWheres());
        $this->assertSame(['country_code', '=', 'IN'], $builder->getWheres()[0]);
        $this->assertSame(['state_code',   '=', 'GJ'], $builder->getWheres()[1]);
    }

    public function test_chaining_returns_same_instance(): void
    {
        $builder = $this->makeBuilder();

        $this->assertSame($builder, $builder->lang('en'));
        $this->assertSame($builder, $builder->whereCountry('IN'));
        $this->assertSame($builder, $builder->whereLike('name', 'A%'));
        $this->assertSame($builder, $builder->whereIn('code', ['IN']));
        $this->assertSame($builder, $builder->search('test'));
        $this->assertSame($builder, $builder->limit(5));
        $this->assertSame($builder, $builder->offset(2));
        $this->assertSame($builder, $builder->orderBy('name'));
    }

    public function test_first_returns_single_item(): void
    {
        $item    = (object) ['name' => 'India'];
        $builder = new WorldQueryBuilder(fn () => collect([$item]), 'en');

        $this->assertSame($item, $builder->first());
    }

    public function test_first_returns_null_when_empty(): void
    {
        $builder = $this->makeBuilder([]);
        $this->assertNull($builder->first());
    }

    public function test_count_returns_number_of_results(): void
    {
        $builder = new WorldQueryBuilder(fn () => collect([1, 2, 3]), 'en');
        $this->assertSame(3, $builder->count());
    }

    public function test_paginate_returns_paginator(): void
    {
        $items   = range(1, 10);
        $builder = new WorldQueryBuilder(
            executor: function (WorldQueryBuilder $q) use ($items) {
                $result = collect($items);
                if ($q->getOffsetValue() !== null) {
                    $result = $result->slice($q->getOffsetValue());
                }
                if ($q->getLimitValue() !== null) {
                    $result = $result->take($q->getLimitValue());
                }
                return $result->values();
            },
            defaultLang: 'en',
        );

        $paged = $builder->paginate(3, 2);

        $this->assertSame(10, $paged->total());
        $this->assertSame(3, $paged->perPage());
        $this->assertSame(2, $paged->currentPage());
        $this->assertCount(3, $paged->items());
    }

    public function test_paginate_uses_total_executor_for_count(): void
    {
        $countCalled = 0;
        $builder = new WorldQueryBuilder(
            executor:      fn ($q) => collect(range(1, (int) $q->getLimitValue())),
            defaultLang:   'en',
            totalExecutor: function () use (&$countCalled) {
                $countCalled++;
                return 100;
            },
        );

        $paged = $builder->paginate(5, 1);

        $this->assertSame(1, $countCalled);
        $this->assertSame(100, $paged->total());
        $this->assertCount(5, $paged->items());
    }

    public function test_limit_and_offset_exposed_correctly(): void
    {
        $builder = $this->makeBuilder();
        $builder->limit(10)->offset(5);

        $this->assertSame(10, $builder->getLimitValue());
        $this->assertSame(5, $builder->getOffsetValue());
    }

    public function test_order_by_defaults_to_asc(): void
    {
        $builder = $this->makeBuilder();
        $builder->orderBy('name');

        $this->assertSame('name', $builder->getOrderByField());
        $this->assertSame('asc', $builder->getOrderDir());
    }

    public function test_order_by_desc(): void
    {
        $builder = $this->makeBuilder();
        $builder->orderBy('name', 'desc');

        $this->assertSame('desc', $builder->getOrderDir());
    }
}
