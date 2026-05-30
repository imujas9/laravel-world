<?php

namespace Imujas9\World\Tests\Unit\Repositories\File;

use PHPUnit\Framework\TestCase;
use Imujas9\World\Repositories\File\FileDataLoader;
use RuntimeException;

class FileDataLoaderTest extends TestCase
{
    private string $fixturesPath;
    private FileDataLoader $loader;

    protected function setUp(): void
    {
        $this->fixturesPath = __DIR__ . '/../../../Fixtures/data';
        $this->loader       = new FileDataLoader($this->fixturesPath);
    }

    public function test_loads_json_file(): void
    {
        $data = $this->loader->load('countries.json');

        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertSame('IN', $data[0]['code']);
    }

    public function test_caches_result_on_second_call(): void
    {
        $first  = $this->loader->load('countries.json');
        $second = $this->loader->load('countries.json');

        $this->assertSame($first, $second);
    }

    public function test_throws_on_missing_file(): void
    {
        $this->expectException(RuntimeException::class);
        $this->loader->load('nonexistent.json');
    }

    public function test_loads_translation_file(): void
    {
        $map = $this->loader->loadTranslation('countries', 'en');

        $this->assertIsArray($map);
        $this->assertSame('India', $map['IN']);
        $this->assertSame('United States', $map['US']);
    }

    public function test_returns_empty_array_for_missing_translation_file(): void
    {
        $map = $this->loader->loadTranslation('countries', 'xyz_nonexistent');

        $this->assertSame([], $map);
    }

    public function test_caches_translation_result(): void
    {
        $first  = $this->loader->loadTranslation('countries', 'en');
        $second = $this->loader->loadTranslation('countries', 'en');

        $this->assertSame($first, $second);
    }

    public function test_stream_yields_all_records(): void
    {
        $records = iterator_to_array($this->loader->stream('countries.json'), false);

        $this->assertCount(3, $records);
        $this->assertIsArray($records[0]);
        $this->assertArrayHasKey('code', $records[0]);
    }

    public function test_stream_yields_correct_data(): void
    {
        $codes = [];
        foreach ($this->loader->stream('countries.json') as $row) {
            $codes[] = $row['code'];
        }

        $this->assertContains('IN', $codes);
        $this->assertContains('US', $codes);
        $this->assertContains('FR', $codes);
    }

    public function test_stream_yields_same_data_as_load(): void
    {
        $loaded   = $this->loader->load('countries.json');
        $streamed = iterator_to_array($this->loader->stream('countries.json'), false);

        $this->assertCount(count($loaded), $streamed);
        $this->assertSame($loaded[0]['code'], $streamed[0]['code']);
    }

    public function test_stream_throws_on_missing_file(): void
    {
        $this->expectException(RuntimeException::class);
        iterator_to_array($this->loader->stream('nonexistent.json'));
    }
}
