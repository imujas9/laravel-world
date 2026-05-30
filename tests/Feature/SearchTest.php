<?php

namespace Imujas9\World\Tests\Feature;

use Imujas9\World\DTO\CityData;
use Imujas9\World\Facades\City;
use Imujas9\World\Facades\Country;
use Imujas9\World\Facades\State;
use Imujas9\World\Tests\TestCase;

/**
 * Feature tests for search/filter operators using the bundled 3-country fixture.
 * Fixtures: IN (id=1, Asia), US (id=2, Americas), FR (id=3, Europe)
 * States:   GJ/MH (IN), CA/NY (US), IDF (FR)
 */
class SearchTest extends TestCase
{
    // ─── Country – search() ──────────────────────────────────────────────────

    public function test_country_search_finds_exact_name_match(): void
    {
        $results = Country::lang('en')->search('India')->get();

        $this->assertCount(1, $results);
        $this->assertSame('IN', $results->first()->code);
    }

    public function test_country_search_finds_partial_match(): void
    {
        $results = Country::lang('en')->search('Ind')->get();

        $this->assertGreaterThan(0, $results->count());
        $this->assertSame('IN', $results->first()->code);
    }

    public function test_country_search_is_case_insensitive(): void
    {
        $lower = Country::lang('en')->search('india')->count();
        $upper = Country::lang('en')->search('INDIA')->count();

        $this->assertSame(1, $lower);
        $this->assertSame(1, $upper);
    }

    public function test_country_search_returns_empty_for_no_match(): void
    {
        $results = Country::lang('en')->search('zzznomatch')->get();

        $this->assertCount(0, $results);
    }

    public function test_country_search_on_capital_field(): void
    {
        $results = Country::lang('en')->search('Delhi', 'capital')->get();

        $this->assertGreaterThan(0, $results->count());
        $this->assertSame('IN', $results->first()->code);
    }

    public function test_country_search_finds_us_by_united(): void
    {
        $results = Country::lang('en')->search('United')->get();

        $this->assertGreaterThan(0, $results->count());
        $codes = $results->pluck('code')->toArray();
        $this->assertContains('US', $codes);
    }

    // ─── Country – whereLike() ───────────────────────────────────────────────

    public function test_country_where_like_starts_with(): void
    {
        // "United States" starts with "United"
        $results = Country::lang('en')->whereLike('name', 'United%')->get();

        $this->assertGreaterThan(0, $results->count());
        $codes = $results->pluck('code')->toArray();
        $this->assertContains('US', $codes);
    }

    public function test_country_where_like_ends_with(): void
    {
        // "India" ends with "dia"
        $results = Country::lang('en')->whereLike('name', '%dia')->get();

        $this->assertGreaterThan(0, $results->count());
        $codes = $results->pluck('code')->toArray();
        $this->assertContains('IN', $codes);
    }

    public function test_country_where_like_substring(): void
    {
        // "France" contains "ran"
        $results = Country::lang('en')->whereLike('name', '%ran%')->get();

        $this->assertGreaterThan(0, $results->count());
        $codes = $results->pluck('code')->toArray();
        $this->assertContains('FR', $codes);
    }

    public function test_country_where_like_on_non_name_field(): void
    {
        $results = Country::lang('en')->whereLike('currency', 'IN%')->get();

        $this->assertGreaterThan(0, $results->count());
        $this->assertSame('IN', $results->first()->code); // INR
    }

    public function test_country_where_like_returns_empty_on_no_match(): void
    {
        $results = Country::lang('en')->whereLike('name', 'ZZZNOMATCH%')->get();

        $this->assertCount(0, $results);
    }

    // ─── Country – whereIn() ─────────────────────────────────────────────────

    public function test_country_where_in_returns_matching_codes(): void
    {
        $results = Country::whereIn('code', ['IN', 'US'])->get();

        $this->assertCount(2, $results);
        $codes = $results->pluck('code')->sort()->values()->toArray();
        $this->assertSame(['IN', 'US'], $codes);
    }

    public function test_country_where_in_single_item(): void
    {
        $results = Country::whereIn('code', ['FR'])->get();

        $this->assertCount(1, $results);
        $this->assertSame('FR', $results->first()->code);
    }

    public function test_country_where_in_returns_empty_when_no_match(): void
    {
        $results = Country::whereIn('code', ['ZZ', 'XX'])->get();

        $this->assertCount(0, $results);
    }

    public function test_country_where_in_all_three_fixtures(): void
    {
        $results = Country::whereIn('code', ['IN', 'US', 'FR'])->get();

        $this->assertCount(3, $results);
    }

    // ─── Country – where() with operators ────────────────────────────────────

    public function test_country_where_equality_operator(): void
    {
        $results = Country::where('code', '=', 'IN')->get();

        $this->assertCount(1, $results);
        $this->assertSame('IN', $results->first()->code);
    }

    public function test_country_where_not_equal_operator(): void
    {
        $results = Country::where('code', '!=', 'IN')->get();

        $codes = $results->pluck('code')->toArray();
        $this->assertNotContains('IN', $codes);
        $this->assertCount(2, $results);
    }

    public function test_country_where_greater_than_operator(): void
    {
        $results = Country::where('id', '>', 1)->get();

        $this->assertCount(2, $results);
        foreach ($results as $country) {
            $this->assertGreaterThan(1, $country->id);
        }
    }

    public function test_country_where_less_than_operator(): void
    {
        $results = Country::where('id', '<', 3)->get();

        $this->assertCount(2, $results);
        foreach ($results as $country) {
            $this->assertLessThan(3, $country->id);
        }
    }

    public function test_country_where_greater_than_or_equal_operator(): void
    {
        $results = Country::where('id', '>=', 2)->get();

        $this->assertCount(2, $results);
    }

    public function test_country_where_less_than_or_equal_operator(): void
    {
        $results = Country::where('id', '<=', 2)->get();

        $this->assertCount(2, $results);
    }

    public function test_country_where_on_region(): void
    {
        $results = Country::where('region', 'Asia')->get();

        $this->assertCount(1, $results);
        $this->assertSame('IN', $results->first()->code);
    }

    // ─── Country – chaining ──────────────────────────────────────────────────

    public function test_country_chained_where_in_and_region(): void
    {
        $results = Country::whereIn('code', ['IN', 'US', 'FR'])
            ->whereRegion('Asia')
            ->get();

        $this->assertCount(1, $results);
        $this->assertSame('IN', $results->first()->code);
    }

    public function test_country_chained_search_and_where(): void
    {
        $results = Country::lang('en')
            ->search('a')
            ->where('region', 'Asia')
            ->get();

        $codes = $results->pluck('code')->toArray();
        $this->assertContains('IN', $codes);
        foreach ($results as $country) {
            $this->assertSame('Asia', $country->region);
        }
    }

    // ─── State ───────────────────────────────────────────────────────────────

    public function test_state_search_returns_matching_states(): void
    {
        $results = State::whereCountry('IN')->search('gujar')->get();

        $this->assertGreaterThan(0, $results->count());
        $codes = $results->pluck('code')->toArray();
        $this->assertContains('GJ', $codes);
    }

    public function test_state_search_case_insensitive(): void
    {
        $lower = State::whereCountry('IN')->search('gujar')->count();
        $upper = State::whereCountry('IN')->search('GUJAR')->count();

        $this->assertSame($lower, $upper);
        $this->assertGreaterThan(0, $lower);
    }

    public function test_state_where_like_partial_match(): void
    {
        // "Gujarat" starts with "Guja"
        $results = State::whereCountry('IN')->whereLike('name', 'Guja%')->get();

        $this->assertGreaterThan(0, $results->count());
        $codes = $results->pluck('code')->toArray();
        $this->assertContains('GJ', $codes);
    }

    public function test_state_where_in_multiple_country_codes(): void
    {
        $results = State::whereIn('country_code', ['IN', 'US'])->get();

        $this->assertGreaterThan(0, $results->count());
        foreach ($results as $state) {
            $this->assertContains($state->country_code, ['IN', 'US']);
        }
    }

    public function test_state_where_in_returns_empty_when_no_match(): void
    {
        $results = State::whereIn('country_code', ['ZZ'])->get();

        $this->assertCount(0, $results);
    }

    public function test_state_where_not_equal_operator(): void
    {
        $all      = State::whereCountry('IN')->count();
        $filtered = State::whereCountry('IN')->where('code', '!=', 'GJ')->count();

        $this->assertSame($all - 1, $filtered);
    }

    // ─── City ─────────────────────────────────────────────────────────────────

    public function test_city_search_returns_matching_cities(): void
    {
        $results = City::whereState('GJ')->search('abad')->get();

        $this->assertGreaterThan(0, $results->count());
        foreach ($results as $city) {
            $this->assertStringContainsStringIgnoringCase('abad', $city->name ?? '');
        }
    }

    public function test_city_search_case_insensitive(): void
    {
        $lower = City::whereState('GJ')->search('abad')->count();
        $upper = City::whereState('GJ')->search('ABAD')->count();

        $this->assertSame($lower, $upper);
        $this->assertGreaterThan(0, $lower);
    }

    public function test_city_where_like_partial_match(): void
    {
        $results = City::whereState('GJ')->whereLike('name', 'Ahmed%')->get();

        $this->assertGreaterThan(0, $results->count());
        foreach ($results as $city) {
            $this->assertStringStartsWith('Ahmed', $city->name ?? '');
        }
    }

    public function test_city_where_in_multiple_state_codes(): void
    {
        $results = City::whereIn('state_code', ['GJ', 'MH'])->get();

        $this->assertGreaterThan(0, $results->count());
        foreach ($results as $city) {
            $this->assertContains($city->state_code, ['GJ', 'MH']);
        }
    }

    public function test_city_where_in_returns_empty_when_no_match(): void
    {
        $results = City::whereIn('state_code', ['ZZ'])->get();

        $this->assertCount(0, $results);
    }

    public function test_city_search_returns_correct_type(): void
    {
        $results = City::whereCountry('IN')->search('abad')->get();

        $this->assertContainsOnlyInstancesOf(CityData::class, $results);
    }

    public function test_chained_where_in_and_search(): void
    {
        $results = City::whereIn('state_code', ['GJ', 'MH'])
            ->search('abad')
            ->get();

        $this->assertGreaterThan(0, $results->count());
        foreach ($results as $city) {
            $this->assertStringContainsStringIgnoringCase('abad', $city->name ?? '');
            $this->assertContains($city->state_code, ['GJ', 'MH']);
        }
    }

    public function test_city_where_not_equal_operator(): void
    {
        $all      = City::whereState('GJ')->count();
        $filtered = City::whereState('GJ')->where('country_code', '!=', 'IN')->count();

        $this->assertSame(0, $filtered);
        $this->assertGreaterThan(0, $all);
    }
}
