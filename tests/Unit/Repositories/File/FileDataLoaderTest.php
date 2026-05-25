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
        // Missing lang files are not errors — caller falls back to default lang
        $map = $this->loader->loadTranslation('countries', 'xyz_nonexistent');

        $this->assertSame([], $map);
    }

    public function test_caches_translation_result(): void
    {
        $first  = $this->loader->loadTranslation('countries', 'en');
        $second = $this->loader->loadTranslation('countries', 'en');

        $this->assertSame($first, $second);
    }
}
