<?php

namespace Imujas9\World\Tests\Feature;

use Imujas9\World\DTO\CountryData;
use Imujas9\World\Facades\Country;
use Imujas9\World\Query\WorldQueryBuilder;
use Imujas9\World\Tests\TestCase;
use Illuminate\Support\Collection;

class CountryFacadeTest extends TestCase
{
    public function test_all_returns_collection_of_country_data(): void
    {
        $results = Country::all();

        $this->assertInstanceOf(Collection::class, $results);
        $this->assertGreaterThan(0, $results->count());
        $this->assertContainsOnlyInstancesOf(CountryData::class, $results);
    }

    public function test_find_returns_country_by_id(): void
    {
        $country = Country::find(1);

        $this->assertInstanceOf(CountryData::class, $country);
        $this->assertSame(1, $country->id);
        $this->assertSame('IN', $country->code);
        $this->assertSame('India', $country->name);
    }

    public function test_find_returns_null_for_unknown_id(): void
    {
        $this->assertNull(Country::find(9999));
    }

    public function test_find_by_code_returns_country(): void
    {
        $country = Country::findByCode('IN');

        $this->assertInstanceOf(CountryData::class, $country);
        $this->assertSame('IN', $country->code);
        $this->assertSame('India', $country->name);
    }

    public function test_find_by_code_returns_null_for_unknown_code(): void
    {
        $this->assertNull(Country::findByCode('ZZ'));
    }

    public function test_lang_returns_query_builder(): void
    {
        $this->assertInstanceOf(WorldQueryBuilder::class, Country::lang('en'));
    }

    public function test_lang_single_populates_name_field(): void
    {
        $india = Country::lang('hi')->get()->firstWhere('code', 'IN');

        $this->assertSame('भारत', $india->name);
        $this->assertSame([], $india->names);
    }

    public function test_lang_multiple_populates_names_array(): void
    {
        $india = Country::lang('en', 'hi')->get()->firstWhere('code', 'IN');

        $this->assertNull($india->name);
        $this->assertSame('India', $india->names['en']);
        $this->assertSame('भारत', $india->names['hi']);
    }

    public function test_where_region_filters_results(): void
    {
        $results = Country::lang('en')->whereRegion('Asia')->get();

        $this->assertGreaterThan(0, $results->count());
        foreach ($results as $country) {
            $this->assertSame('Asia', $country->region);
        }
    }

    public function test_chaining_lang_and_where(): void
    {
        $results = Country::lang('en')
            ->whereRegion('Europe')
            ->get();

        $this->assertGreaterThan(0, $results->count());
        $codes = $results->pluck('code')->toArray();
        $this->assertContains('FR', $codes);
    }

    public function test_paginate_returns_paginator(): void
    {
        $page = Country::newQuery()->paginate(10, 1);

        $this->assertSame(10, $page->perPage());
        $this->assertSame(1, $page->currentPage());
        $this->assertGreaterThan(0, $page->total());
    }

    public function test_count_returns_integer(): void
    {
        $this->assertIsInt(Country::newQuery()->count());
    }

    public function test_order_by_name_asc(): void
    {
        $names = Country::lang('en')->orderBy('name')->get()->pluck('name')->toArray();

        $sorted = $names;
        sort($sorted);
        $this->assertSame($sorted, $names);
    }
}
