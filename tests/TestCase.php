<?php

namespace Imujas9\World\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Imujas9\World\WorldServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [WorldServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('world.driver',                  'file');
        $app['config']->set('world.default_lang',            'en');
        $app['config']->set('world.data_path',               $this->fixturesPath());
        $app['config']->set('world.city_translations_path',  $this->fixturesPath() . '/translations/cities');
    }

    protected function fixturesPath(): string
    {
        return __DIR__ . '/Fixtures/data';
    }
}
