<?php

namespace Imujas9\World\Tests\Feature;

use Imujas9\World\DTO\StateData;
use Imujas9\World\Facades\State;
use Imujas9\World\Query\WorldQueryBuilder;
use Imujas9\World\Tests\TestCase;
use Illuminate\Support\Collection;

class StateFacadeTest extends TestCase
{
    public function test_all_returns_collection_of_state_data(): void
    {
        $results = State::all();

        $this->assertInstanceOf(Collection::class, $results);
        $this->assertContainsOnlyInstancesOf(StateData::class, $results);
    }

    public function test_find_returns_state_by_id(): void
    {
        $state = State::find(1);

        $this->assertInstanceOf(StateData::class, $state);
        $this->assertSame(1, $state->id);
        $this->assertSame('GJ', $state->code);
        $this->assertSame('IN', $state->country_code);
        $this->assertSame(1, $state->country_id);
        $this->assertSame('Gujarat', $state->name);
    }

    public function test_find_returns_null_for_unknown_id(): void
    {
        $this->assertNull(State::find(9999));
    }

    public function test_find_by_code_returns_state(): void
    {
        $state = State::findByCode('GJ');

        $this->assertInstanceOf(StateData::class, $state);
        $this->assertSame('GJ', $state->code);
        $this->assertSame('IN', $state->country_code);
        $this->assertSame('Gujarat', $state->name);
    }

    public function test_find_by_code_returns_null_for_unknown_code(): void
    {
        $this->assertNull(State::findByCode('ZZ'));
    }

    public function test_where_country_filters_by_country(): void
    {
        $results = State::whereCountry('IN')->get();

        $this->assertGreaterThan(0, $results->count());
        foreach ($results as $state) {
            $this->assertSame('IN', $state->country_code);
        }
    }

    public function test_where_country_is_case_insensitive(): void
    {
        $upper = State::whereCountry('IN')->count();
        $lower = State::whereCountry('in')->count();

        $this->assertSame($upper, $lower);
    }

    public function test_lang_translates_state_name(): void
    {
        $state = State::lang('hi')->whereCountry('IN')->get()->firstWhere('code', 'GJ');

        $this->assertSame('गुजरात', $state->name);
    }

    public function test_lang_multiple_builds_names_array(): void
    {
        $state = State::lang('en', 'hi')->whereCountry('IN')->get()->firstWhere('code', 'GJ');

        $this->assertSame('Gujarat', $state->names['en']);
        $this->assertSame('गुजरात', $state->names['hi']);
    }

    public function test_lang_returns_query_builder(): void
    {
        $this->assertInstanceOf(WorldQueryBuilder::class, State::lang('en'));
    }

    public function test_count_via_where_country(): void
    {
        $count = State::whereCountry('IN')->count();
        $this->assertGreaterThan(0, $count);
    }

    public function test_paginate_works_on_filtered_results(): void
    {
        $total = State::whereCountry('US')->count();
        $page  = State::whereCountry('US')->paginate(1, 1);

        $this->assertSame($total, $page->total());
        $this->assertCount(1, $page->items());
    }
}
