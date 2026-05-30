<?php

namespace Imujas9\World\Tests\Unit\Repositories\File;

use PHPUnit\Framework\TestCase;
use Imujas9\World\DTO\CityData;
use Imujas9\World\Query\WorldQueryBuilder;
use Imujas9\World\Repositories\File\FileCityRepository;
use Imujas9\World\Repositories\File\FileDataLoader;

class FileCityRepositoryTest extends TestCase
{
    private FileCityRepository $repo;

    protected function setUp(): void
    {
        $loader     = new FileDataLoader(__DIR__ . '/../../../Fixtures/data');
        $this->repo = new FileCityRepository($loader, 'en');
    }

    public function test_all_returns_all_cities(): void
    {
        $results = $this->repo->all();

        $this->assertCount(5, $results);
        $this->assertContainsOnlyInstancesOf(CityData::class, $results);
    }

    public function test_all_uses_english_name_by_default(): void
    {
        $results = $this->repo->all();
        $city    = $results->firstWhere('id', 1);

        $this->assertSame('Ahmedabad', $city->name);
    }

    public function test_find_returns_correct_city(): void
    {
        $city = $this->repo->find(1);

        $this->assertInstanceOf(CityData::class, $city);
        $this->assertSame(1, $city->id);
        $this->assertSame('Ahmedabad', $city->name);
        $this->assertSame('GJ', $city->state_code);
        $this->assertSame('IN', $city->country_code);
    }

    public function test_find_returns_null_for_unknown_id(): void
    {
        $this->assertNull($this->repo->find(99999));
    }

    public function test_where_country_filters_cities(): void
    {
        $results = $this->repo->whereCountry('IN')->get();

        $this->assertCount(3, $results);
        foreach ($results as $city) {
            $this->assertSame('IN', $city->country_code);
        }
    }

    public function test_where_state_filters_cities(): void
    {
        $results = $this->repo->whereState('GJ')->get();

        $this->assertCount(2, $results);
        foreach ($results as $city) {
            $this->assertSame('GJ', $city->state_code);
        }
    }

    public function test_where_state_and_country_combined(): void
    {
        $results = $this->repo->whereCountry('IN')->whereState('MH')->get();

        $this->assertCount(1, $results);
        $this->assertSame('Mumbai', $results->first()->name);
    }

    public function test_where_like_matches_partial_name(): void
    {
        $results = $this->repo->newQuery()->whereLike('name', 'Ahm%')->get();

        $this->assertCount(1, $results);
        $this->assertSame('Ahmedabad', $results->first()->name);
    }

    public function test_search_finds_by_partial_name(): void
    {
        $results = $this->repo->newQuery()->search('Los')->get();

        $this->assertCount(1, $results);
        $this->assertSame('Los Angeles', $results->first()->name);
    }

    public function test_where_in_filters_by_multiple_state_codes(): void
    {
        $results = $this->repo->newQuery()->whereIn('state_code', ['GJ', 'CA'])->get();

        $this->assertCount(3, $results);
        $stateCodes = $results->pluck('state_code')->unique()->sort()->values()->toArray();
        $this->assertSame(['CA', 'GJ'], $stateCodes);
    }

    public function test_lang_translates_name(): void
    {
        $results = $this->repo->lang('hi')->whereState('GJ')->get();
        $city    = $results->firstWhere('id', 1);

        $this->assertSame('अहमदाबाद', $city->name);
    }

    public function test_lang_falls_back_to_base_name_when_translation_missing(): void
    {
        // Paris (id=5) has no Hindi translation in fixtures
        $results = $this->repo->lang('hi')->whereCountry('FR')->get();

        $this->assertSame('Paris', $results->first()->name);
    }

    public function test_lang_multiple_builds_names_array(): void
    {
        $results = $this->repo->lang('en', 'hi')->whereState('GJ')->get();
        $city    = $results->firstWhere('id', 1);

        $this->assertSame('Ahmedabad', $city->names['en']);
        $this->assertSame('अहमदाबाद', $city->names['hi']);
    }

    public function test_new_query_returns_builder(): void
    {
        $this->assertInstanceOf(WorldQueryBuilder::class, $this->repo->newQuery());
    }

    public function test_count_returns_total(): void
    {
        $this->assertSame(5, $this->repo->newQuery()->count());
    }

    public function test_limit_restricts_results(): void
    {
        $this->assertCount(2, $this->repo->newQuery()->limit(2)->get());
    }

    public function test_paginate_splits_results(): void
    {
        $page = $this->repo->newQuery()->paginate(2, 2);

        $this->assertSame(5, $page->total());
        $this->assertCount(2, $page->items());
        $this->assertSame(2, $page->currentPage());
    }

    public function test_city_data_has_null_state_id_from_file_driver(): void
    {
        // cities.json doesn't carry state_id — should be null in file mode
        $city = $this->repo->find(1);

        $this->assertNull($city->state_id);
        $this->assertNull($city->country_id);
    }
}
