<?php

namespace Imujas9\World\Tests\Unit\Repositories\File;

use PHPUnit\Framework\TestCase;
use Imujas9\World\DTO\StateData;
use Imujas9\World\Query\WorldQueryBuilder;
use Imujas9\World\Repositories\File\FileStateRepository;
use Imujas9\World\Repositories\File\FileDataLoader;

class FileStateRepositoryTest extends TestCase
{
    private FileStateRepository $repo;

    protected function setUp(): void
    {
        $loader     = new FileDataLoader(__DIR__ . '/../../../Fixtures/data');
        $this->repo = new FileStateRepository($loader, 'en');
    }

    public function test_all_returns_all_states(): void
    {
        $results = $this->repo->all();

        $this->assertCount(5, $results);
        $this->assertContainsOnlyInstancesOf(StateData::class, $results);
    }

    public function test_all_populates_name_from_default_lang(): void
    {
        $results = $this->repo->all();
        $gj      = $results->firstWhere('code', 'GJ');

        $this->assertSame('Gujarat', $gj->name);
    }

    public function test_find_returns_state_by_id(): void
    {
        $state = $this->repo->find(1);

        $this->assertInstanceOf(StateData::class, $state);
        $this->assertSame(1, $state->id);
        $this->assertSame('GJ', $state->code);
        $this->assertSame('IN', $state->country_code);
        $this->assertSame(1, $state->country_id);
        $this->assertSame('Gujarat', $state->name);
    }

    public function test_find_returns_null_for_unknown_id(): void
    {
        $this->assertNull($this->repo->find(9999));
    }

    public function test_find_by_code_returns_correct_state(): void
    {
        $state = $this->repo->findByCode('GJ');

        $this->assertInstanceOf(StateData::class, $state);
        $this->assertSame('GJ', $state->code);
        $this->assertSame('IN', $state->country_code);
        $this->assertSame('Gujarat', $state->name);
    }

    public function test_find_by_code_is_case_insensitive(): void
    {
        $this->assertNotNull($this->repo->findByCode('gj'));
        $this->assertNotNull($this->repo->findByCode('GJ'));
    }

    public function test_find_by_code_returns_null_for_unknown_code(): void
    {
        $this->assertNull($this->repo->findByCode('XX'));
    }

    public function test_where_country_filters_states(): void
    {
        $results = $this->repo->whereCountry('IN')->get();

        $this->assertCount(2, $results);
        foreach ($results as $state) {
            $this->assertSame('IN', $state->country_code);
        }
    }

    public function test_where_country_is_case_insensitive(): void
    {
        $upper = $this->repo->whereCountry('IN')->count();
        $lower = $this->repo->whereCountry('in')->count();

        $this->assertSame($upper, $lower);
    }

    public function test_lang_translates_name(): void
    {
        $results = $this->repo->lang('hi')->whereCountry('IN')->get();
        $gj      = $results->firstWhere('code', 'GJ');

        $this->assertSame('गुजरात', $gj->name);
    }

    public function test_lang_falls_back_to_name_field_when_translation_missing(): void
    {
        $results = $this->repo->lang('hi')->whereCountry('US')->get();
        $ca      = $results->firstWhere('code', 'CA');

        $this->assertSame('California', $ca->name);
    }

    public function test_lang_multiple_builds_names_array(): void
    {
        $results = $this->repo->lang('en', 'hi')->whereCountry('IN')->get();
        $gj      = $results->firstWhere('code', 'GJ');

        $this->assertSame('Gujarat', $gj->names['en']);
        $this->assertSame('गुजरात', $gj->names['hi']);
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

    public function test_state_data_includes_id_and_country_id(): void
    {
        $all = $this->repo->all();
        foreach ($all as $state) {
            $this->assertGreaterThan(0, $state->id);
            $this->assertGreaterThan(0, $state->country_id);
        }
    }
}
