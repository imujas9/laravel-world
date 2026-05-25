<?php

namespace Imujas9\World\Tests\Unit\Repositories\File;

use PHPUnit\Framework\TestCase;
use Imujas9\World\DTO\CountryData;
use Imujas9\World\Query\WorldQueryBuilder;
use Imujas9\World\Repositories\File\FileCountryRepository;
use Imujas9\World\Repositories\File\FileDataLoader;

class FileCountryRepositoryTest extends TestCase
{
    private FileCountryRepository $repo;

    protected function setUp(): void
    {
        $loader     = new FileDataLoader(__DIR__ . '/../../../Fixtures/data');
        $this->repo = new FileCountryRepository($loader, 'en');
    }

    public function test_all_returns_all_countries(): void
    {
        $results = $this->repo->all();

        $this->assertCount(3, $results);
        $this->assertContainsOnlyInstancesOf(CountryData::class, $results);
    }

    public function test_all_populates_name_from_default_lang(): void
    {
        $results = $this->repo->all();
        $india   = $results->firstWhere('code', 'IN');

        $this->assertSame('India', $india->name);
    }

    public function test_find_returns_country_by_id(): void
    {
        $country = $this->repo->find(1);

        $this->assertInstanceOf(CountryData::class, $country);
        $this->assertSame(1, $country->id);
        $this->assertSame('IN', $country->code);
        $this->assertSame('India', $country->name);
    }

    public function test_find_returns_null_for_unknown_id(): void
    {
        $this->assertNull($this->repo->find(9999));
    }

    public function test_find_by_code_returns_correct_country(): void
    {
        $country = $this->repo->findByCode('IN');

        $this->assertInstanceOf(CountryData::class, $country);
        $this->assertSame('IN', $country->code);
        $this->assertSame('IND', $country->iso3);
        $this->assertSame('India', $country->name);
    }

    public function test_find_by_code_is_case_insensitive(): void
    {
        $this->assertNotNull($this->repo->findByCode('in'));
        $this->assertNotNull($this->repo->findByCode('IN'));
    }

    public function test_find_by_code_returns_null_for_unknown_code(): void
    {
        $this->assertNull($this->repo->findByCode('XX'));
    }

    public function test_lang_returns_query_builder(): void
    {
        $builder = $this->repo->lang('hi');

        $this->assertInstanceOf(WorldQueryBuilder::class, $builder);
    }

    public function test_lang_single_translates_name(): void
    {
        $results = $this->repo->lang('hi')->get();
        $india   = $results->firstWhere('code', 'IN');

        $this->assertSame('भारत', $india->name);
        $this->assertSame([], $india->names);
    }

    public function test_lang_multiple_sets_names_array(): void
    {
        $results = $this->repo->lang('en', 'hi')->get();
        $india   = $results->firstWhere('code', 'IN');

        $this->assertNull($india->name);
        $this->assertSame('India', $india->names['en']);
        $this->assertSame('भारत', $india->names['hi']);
    }

    public function test_where_region_filters_correctly(): void
    {
        $results = $this->repo->lang('en')->whereRegion('Asia')->get();

        $this->assertCount(1, $results);
        $this->assertSame('IN', $results->first()->code);
    }

    public function test_where_filters_by_arbitrary_field(): void
    {
        $results = $this->repo->newQuery()->where('currency', 'EUR')->get();

        $this->assertCount(1, $results);
        $this->assertSame('FR', $results->first()->code);
    }

    public function test_new_query_returns_builder(): void
    {
        $this->assertInstanceOf(WorldQueryBuilder::class, $this->repo->newQuery());
    }

    public function test_proxy_calls_go_to_builder(): void
    {
        $builder = $this->repo->whereRegion('Asia');

        $this->assertInstanceOf(WorldQueryBuilder::class, $builder);
    }

    public function test_limit_restricts_results(): void
    {
        $results = $this->repo->newQuery()->limit(2)->get();

        $this->assertCount(2, $results);
    }

    public function test_offset_skips_records(): void
    {
        $all   = $this->repo->all();
        $paged = $this->repo->newQuery()->offset(1)->get();

        $this->assertCount($all->count() - 1, $paged);
        $this->assertSame($all->get(1)->code, $paged->first()->code);
    }

    public function test_order_by_name_ascending(): void
    {
        $results = $this->repo->lang('en')->orderBy('name')->get();
        $names   = $results->pluck('name')->values()->toArray();

        $sorted = $names;
        sort($sorted);
        $this->assertSame($sorted, $names);
    }

    public function test_count_returns_total(): void
    {
        $this->assertSame(3, $this->repo->newQuery()->count());
    }

    public function test_paginate_returns_correct_page(): void
    {
        $page = $this->repo->newQuery()->paginate(2, 1);

        $this->assertSame(3, $page->total());
        $this->assertCount(2, $page->items());
    }

    public function test_missing_translation_falls_back_to_default_lang(): void
    {
        $results = $this->repo->lang('es')->get();
        $india   = $results->firstWhere('code', 'IN');

        $this->assertSame('India', $india->name);
    }

    public function test_country_data_includes_id(): void
    {
        $all = $this->repo->all();
        foreach ($all as $country) {
            $this->assertGreaterThan(0, $country->id);
        }
    }
}
