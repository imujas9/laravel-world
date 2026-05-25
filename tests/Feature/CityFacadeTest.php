<?php

namespace Imujas9\World\Tests\Feature;

use Imujas9\World\DTO\CityData;
use Imujas9\World\Facades\City;
use Imujas9\World\Query\WorldQueryBuilder;
use Imujas9\World\Tests\TestCase;
use Illuminate\Support\Collection;

class CityFacadeTest extends TestCase
{
    public function test_all_returns_collection_of_city_data(): void
    {
        $results = City::all();

        $this->assertInstanceOf(Collection::class, $results);
        $this->assertContainsOnlyInstancesOf(CityData::class, $results);
    }

    public function test_find_returns_city_by_id(): void
    {
        $city = City::find(1);

        $this->assertInstanceOf(CityData::class, $city);
        $this->assertSame(1, $city->id);
        $this->assertSame('Ahmedabad', $city->name);
    }

    public function test_find_returns_null_for_unknown_id(): void
    {
        $this->assertNull(City::find(99999));
    }

    public function test_where_country_filters_cities(): void
    {
        $results = City::whereCountry('IN')->get();

        $this->assertGreaterThan(0, $results->count());
        foreach ($results as $city) {
            $this->assertSame('IN', $city->country_code);
        }
    }

    public function test_where_state_filters_cities(): void
    {
        $results = City::whereState('GJ')->get();

        $this->assertGreaterThan(0, $results->count());
        foreach ($results as $city) {
            $this->assertSame('GJ', $city->state_code);
        }
    }

    public function test_where_country_and_where_state_chained(): void
    {
        $results = City::whereCountry('IN')->whereState('MH')->get();

        foreach ($results as $city) {
            $this->assertSame('IN', $city->country_code);
            $this->assertSame('MH', $city->state_code);
        }
    }

    public function test_lang_translates_city_name(): void
    {
        $city = City::lang('hi')->whereState('GJ')->get()->firstWhere('id', 1);

        $this->assertSame('अहमदाबाद', $city->name);
    }

    public function test_lang_multiple_builds_names_array(): void
    {
        $city = City::lang('en', 'hi')->whereState('GJ')->get()->firstWhere('id', 1);

        $this->assertSame('Ahmedabad', $city->names['en']);
        $this->assertSame('अहमदाबाद', $city->names['hi']);
    }

    public function test_lang_returns_query_builder(): void
    {
        $this->assertInstanceOf(WorldQueryBuilder::class, City::lang('en'));
    }

    public function test_count_works_with_filter(): void
    {
        $count = City::whereCountry('IN')->count();
        $this->assertGreaterThan(0, $count);
    }

    public function test_paginate_returns_correct_total(): void
    {
        $total = City::whereCountry('IN')->count();
        $page  = City::whereCountry('IN')->paginate(2, 1);

        $this->assertSame($total, $page->total());
        $this->assertSame(1, $page->currentPage());
    }

    public function test_first_returns_single_city(): void
    {
        $city = City::whereState('GJ')->first();

        $this->assertInstanceOf(CityData::class, $city);
        $this->assertSame('GJ', $city->state_code);
    }
}
