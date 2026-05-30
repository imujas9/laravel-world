<?php

namespace Imujas9\World;

use Illuminate\Support\ServiceProvider;
use Imujas9\World\Commands\WorldSeedCommand;
use Imujas9\World\Commands\WorldTranslationsCommand;
use Imujas9\World\Contracts\CountryRepository;
use Imujas9\World\Contracts\StateRepository;
use Imujas9\World\Contracts\CityRepository;
use Imujas9\World\Repositories\Database\DbCountryRepository;
use Imujas9\World\Repositories\Database\DbStateRepository;
use Imujas9\World\Repositories\Database\DbCityRepository;
use Imujas9\World\Repositories\File\FileCountryRepository;
use Imujas9\World\Repositories\File\FileStateRepository;
use Imujas9\World\Repositories\File\FileCityRepository;
use Imujas9\World\Repositories\File\FileDataLoader;

class WorldServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/world.php', 'world');

        if (config('world.driver', 'file') === 'database') {
            $this->registerDatabaseDriver();
        } else {
            $this->registerFileDriver();
        }
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/world.php' => config_path('world.php'),
            ], 'world-config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'world-migrations');

            $commands = [WorldTranslationsCommand::class];

            if (config('world.driver', 'file') === 'database') {
                $commands[] = WorldSeedCommand::class;
            }

            $this->commands($commands);
        }
    }

    private function registerDatabaseDriver(): void
    {
        $this->app->bind(CountryRepository::class, fn () => new DbCountryRepository($this->resolveDefaultLang()));
        $this->app->bind(StateRepository::class,   fn () => new DbStateRepository($this->resolveDefaultLang()));
        $this->app->bind(CityRepository::class,    fn () => new DbCityRepository($this->resolveDefaultLang()));
    }

    private function registerFileDriver(): void
    {
        $this->app->singleton(FileDataLoader::class, fn () => new FileDataLoader(
            dataPath:             config('world.data_path') ?? __DIR__ . '/../resources/data',
            cityTranslationsPath: config('world.city_translations_path'),
        ));

        $this->app->bind(CountryRepository::class, function () {
            return new FileCountryRepository(
                $this->app->make(FileDataLoader::class),
                $this->resolveDefaultLang(),
                $this->configLang(),
            );
        });

        $this->app->bind(StateRepository::class, function () {
            return new FileStateRepository(
                $this->app->make(FileDataLoader::class),
                $this->resolveDefaultLang(),
                $this->configLang(),
            );
        });

        $this->app->bind(CityRepository::class, function () {
            return new FileCityRepository(
                $this->app->make(FileDataLoader::class),
                $this->resolveDefaultLang(),
            );
        });
    }

    private function resolveDefaultLang(): string
    {
        $locale = $this->app->getLocale();

        if (strlen($locale) > 2) {
            $locale = strtolower(substr(str_replace('-', '_', $locale), 0, 2));
        }

        return $locale ?: $this->configLang();
    }

    private function configLang(): string
    {
        return config('world.default_lang', 'en') ?: 'en';
    }
}
