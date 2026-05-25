<?php

namespace Imujas9\World\Tests\Feature;

use Imujas9\World\Facades\Country;
use Imujas9\World\Tests\TestCase;

class LocaleTest extends TestCase
{
    public function test_app_locale_is_used_as_default_lang(): void
    {
        $this->app->setLocale('hi');
        Country::clearResolvedInstances();

        $india = Country::findByCode('IN');

        $this->assertSame('भारत', $india->name);
    }

    public function test_config_default_lang_used_when_locale_is_en(): void
    {
        $this->app->setLocale('en');
        $this->app['config']->set('world.default_lang', 'en');
        Country::clearResolvedInstances();

        $india = Country::findByCode('IN');

        $this->assertSame('India', $india->name);
    }

    public function test_locale_normalises_region_suffix(): void
    {
        // 'en_US' should normalise to 'en'
        $this->app->setLocale('en_US');
        Country::clearResolvedInstances();

        $india = Country::findByCode('IN');

        $this->assertSame('India', $india->name);
    }

    public function test_config_default_lang_used_when_locale_has_no_translation(): void
    {
        // 'ga' (Irish) has no country translations in fixtures — falls back to config lang 'en'
        $this->app->setLocale('ga');
        $this->app['config']->set('world.default_lang', 'en');
        Country::clearResolvedInstances();

        $india = Country::findByCode('IN');

        $this->assertSame('India', $india->name);
    }

    protected function tearDown(): void
    {
        Country::clearResolvedInstances();
        parent::tearDown();
    }
}
