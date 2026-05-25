# 🌍 Laravel World

[![Latest Version on Packagist](https://img.shields.io/packagist/v/imujas9/laravel-world.svg?style=flat-square)](https://packagist.org/packages/imujas9/laravel-world)
[![Total Downloads](https://img.shields.io/packagist/dt/imujas9/laravel-world.svg?style=flat-square)](https://packagist.org/packages/imujas9/laravel-world)
[![PHP Version](https://img.shields.io/packagist/php-v/imujas9/laravel-world.svg?style=flat-square)](https://packagist.org/packages/imujas9/laravel-world)

World countries, states, and cities for Laravel — with multi-language support. Works **without a database** out of the box. Switch to a database driver any time with a single env variable.

---

## ✨ What Makes This Different

| Feature                              | Laravel World | Others  |
| ------------------------------------ | ------------- | ------- |
| Works without a database             | ✅            | ❌      |
| Switch to DB with one env var        | ✅            | ❌      |
| Auto locale detection                | ✅            | ❌      |
| Multi-language in one query          | ✅            | ❌      |
| City translations on demand          | ✅            | ❌      |
| 20+ languages for countries & states | ✅            | Partial |
| Fluent chainable API                 | ✅            | Limited |

---

## 📦 What's Included

- **250 countries** — numeric ID, ISO codes, phone codes, currencies, flags, regions, coordinates
- **5,308 states / provinces** — linked to countries by ID and code, with administrative type
- **156,025 cities** — linked to states and countries by ID and code, with coordinates
- **20 languages** for country names · **27** for states · **24** for cities (downloaded on demand)

---

## Requirements & Installation

- PHP **8.1+** · Laravel **10**, **11**, or **12**

```bash
composer require imujas9/laravel-world
```

No migrations, no seeding, no config. Auto-registers and works immediately.

---

## Quick Start

```php
use Imujas9\World\Facades\Country;
use Imujas9\World\Facades\State;
use Imujas9\World\Facades\City;

Country::all();                          // all countries, names in app locale
Country::find(101);                      // by numeric ID
Country::findByCode('IN');               // by ISO2 code

State::whereCountry('IN')->get();        // states of India
State::find(12);                         // by numeric ID
State::findByCode('GJ');                 // by code

City::whereState('GJ')->get();           // cities of Gujarat
City::whereCountry('IN')->whereState('MH')->get();

// Explicit language
Country::lang('hi')->get();              // Hindi names
Country::lang('en', 'hi')->get();        // both → name_en, name_hi

// Locale-aware — no lang() needed when app locale is set
App::setLocale('hi');
Country::all();                          // names in Hindi automatically
```

---

<details>
<summary>⚙️ Configuration</summary>

```bash
php artisan vendor:publish --tag=world-config
```

```php
// config/world.php
return [
    // 'file' (default, zero setup) or 'database'
    'driver' => env('WORLD_DRIVER', 'file'),

    // Fallback language. Priority: app locale → this → 'en'
    'default_lang' => env('WORLD_LANG', 'en'),

    // Table prefix for database driver
    'table_prefix' => 'world_',

    // Storage path for downloaded city translations
    'city_translations_path' => storage_path('app/world/translations/cities'),
];
```

</details>

---

<details>
<summary>🌐 Locale Support</summary>

The package reads `app()->getLocale()` automatically. No `lang()` call needed once locale is set in middleware.

**Resolution order** (when `lang()` is not called):

1. `app()->getLocale()` — set by middleware or `App::setLocale()`
2. `config('world.default_lang')` — `WORLD_LANG` env value
3. `'en'` — hardcoded fallback

Locale strings are normalised: `en_US` → `en`, `zh_CN` → `zh`.

**Middleware example:**

```php
App::setLocale($request->user()?->locale ?? 'en');

// Anywhere downstream — no lang() needed:
Country::all();                    // names in user's language
Country::findByCode('IN');         // name in locale
State::whereCountry('IN')->get();  // state names in locale
```

**Fallback behaviour:**

| Locale  | Translation file exists | Result                       |
| ------- | ----------------------- | ---------------------------- |
| `hi`    | ✅                      | Hindi name                   |
| `ga`    | ❌                      | Falls back to `default_lang` |
| `en_US` | ✅ normalised to `en`   | English name                 |

`lang()` always overrides the locale for that specific query.

</details>

---

<details>
<summary>🏙️ City Translations</summary>

City names work in English out of the box. Translations are **not bundled** (they add 51 MB to every install) — download only what you need:

```bash
php artisan world:translations hi fr de   # specific languages
php artisan world:translations --all      # all 24 languages
php artisan world:translations hi --force # re-download to update
php artisan world:translations            # list available / downloaded
```

Once downloaded, translations are used automatically:

```php
City::lang('hi')->whereState('GJ')->get();
```

**Available:** `ar` `bn` `br` `de` `es` `fa` `fr` `ga` `hi` `hr` `hy` `id` `it` `ja` `ko` `nl` `pl` `pt` `ru` `tr` `uk` `ur` `vi` `zh`

> Add `php artisan world:translations hi` to your deploy script for the languages your app uses.

</details>

---

<details>
<summary>🗄️ Database Driver</summary>

Use the database driver when you need SQL joins, full-text search, or indexing.

```bash
php artisan vendor:publish --tag=world-migrations
php artisan migrate
php artisan world:seed
```

> Re-seed: `php artisan world:seed --truncate`

```env
WORLD_DRIVER=database
```

Your application code stays **exactly the same** — the driver swap is transparent.

</details>

---

<details>
<summary>📖 API Reference</summary>

### Country

```php
Country::all();
Country::find(101);                              // by numeric ID
Country::findByCode('IN');                       // by ISO2 (case-insensitive)
Country::lang('hi')->get();                      // single language
Country::lang('en', 'hi')->get();                // multiple → name_en, name_hi
Country::lang('en')->whereRegion('Asia')->get();
Country::lang('en')->where('currency', 'INR')->get();
Country::lang('en')->orderBy('name')->get();
Country::lang('en')->orderBy('name', 'desc')->get();
Country::lang('en')->limit(10)->offset(20)->get();
Country::lang('en')->whereRegion('Asia')->first();
Country::whereRegion('Europe')->count();
Country::lang('en')->paginate(15);               // page 1
Country::lang('en')->paginate(15, 2);            // page 2
```

### State

```php
State::all();
State::find(12);                                 // by numeric ID
State::findByCode('GJ');                         // by code (case-insensitive)
State::whereCountry('IN')->get();
State::lang('hi')->whereCountry('IN')->get();
State::lang('en', 'hi')->whereCountry('IN')->get();
State::whereCountry('US')->count();
State::whereCountry('IN')->paginate(20);
```

### City

```php
City::all();
City::find(1);                                   // by numeric ID
City::whereCountry('IN')->get();
City::whereState('GJ')->get();
City::whereCountry('IN')->whereState('MH')->get();
City::lang('hi')->whereState('GJ')->get();
City::lang('en', 'hi')->whereState('GJ')->get();
City::whereCountry('IN')->paginate(50);
```

</details>

---

<details>
<summary>📊 Result Objects</summary>

All methods return consistent value objects regardless of driver.

### CountryData

| Property     | Type           | Description                                      |
| ------------ | -------------- | ------------------------------------------------ |
| `id`         | `int`          | Numeric ID                                       |
| `code`       | `string`       | ISO2 (e.g. `IN`)                                 |
| `iso3`       | `string`       | ISO3 (e.g. `IND`)                                |
| `phone_code` | `string`       | Dialling code (e.g. `91`)                        |
| `currency`   | `string`       | ISO 4217 (e.g. `INR`)                            |
| `flag`       | `string\|null` | Emoji flag (e.g. `🇮🇳`)                           |
| `region`     | `string\|null` | e.g. `Asia`                                      |
| `subregion`  | `string\|null` | e.g. `Southern Asia`                             |
| `capital`    | `string\|null` | Capital city                                     |
| `tld`        | `string\|null` | e.g. `.in`                                       |
| `name`       | `string\|null` | Translated name (single lang)                    |
| `names`      | `array`        | `['en' => 'India', 'hi' => 'भारत']` (multi-lang) |

### StateData

| Property       | Type           | Description                   |
| -------------- | -------------- | ----------------------------- |
| `id`           | `int`          | Numeric ID                    |
| `code`         | `string`       | State code                    |
| `country_code` | `string`       | Parent country ISO2           |
| `country_id`   | `int`          | Parent country numeric ID     |
| `type`         | `string\|null` | State / Province / Region…    |
| `latitude`     | `string\|null` |                               |
| `longitude`    | `string\|null` |                               |
| `name`         | `string\|null` | Translated name (single lang) |
| `names`        | `array`        | Translated names (multi-lang) |

### CityData

| Property       | Type           | Description                   |
| -------------- | -------------- | ----------------------------- |
| `id`           | `int`          | Numeric ID                    |
| `state_code`   | `string`       | Parent state code             |
| `country_code` | `string`       | Parent country ISO2           |
| `latitude`     | `string\|null` |                               |
| `longitude`    | `string\|null` |                               |
| `name`         | `string\|null` | Translated name (single lang) |
| `names`        | `array`        | Translated names (multi-lang) |

</details>

---

<details>
<summary>🌍 Available Languages</summary>

> Missing translations fall back to the English name automatically.
> City translations must be downloaded separately — see [City Translations](#️-city-translations).

| Code | Language             | Countries | States | Cities |
| ---- | -------------------- | --------- | ------ | ------ |
| `en` | English              | ✅        | ✅     | ✅     |
| `ar` | Arabic               | ✅        | ✅     | ✅     |
| `bn` | Bengali              | ❌        | ✅     | ✅     |
| `br` | Breton               | ✅        | ✅     | ✅     |
| `de` | German               | ✅        | ✅     | ✅     |
| `el` | Greek                | ❌        | ✅     | ❌     |
| `es` | Spanish              | ✅        | ✅     | ✅     |
| `et` | Estonian             | ❌        | ✅     | ❌     |
| `fa` | Persian              | ✅        | ✅     | ✅     |
| `fi` | Finnish              | ❌        | ✅     | ❌     |
| `fr` | French               | ✅        | ✅     | ✅     |
| `ga` | Irish                | ❌        | ❌     | ✅     |
| `gu` | Gujarati             | ✅        | ✅     | ❌     |
| `hi` | Hindi                | ✅        | ✅     | ✅     |
| `hr` | Croatian             | ✅        | ✅     | ✅     |
| `hy` | Armenian             | ❌        | ✅     | ✅     |
| `id` | Indonesian           | ❌        | ✅     | ✅     |
| `it` | Italian              | ✅        | ✅     | ✅     |
| `ja` | Japanese             | ✅        | ✅     | ✅     |
| `ko` | Korean               | ✅        | ✅     | ✅     |
| `nl` | Dutch                | ✅        | ✅     | ✅     |
| `pl` | Polish               | ✅        | ✅     | ✅     |
| `pt` | Portuguese           | ✅        | ✅     | ✅     |
| `ru` | Russian              | ✅        | ✅     | ✅     |
| `tr` | Turkish              | ✅        | ✅     | ✅     |
| `uk` | Ukrainian            | ✅        | ✅     | ✅     |
| `ur` | Urdu                 | ❌        | ❌     | ✅     |
| `vi` | Vietnamese           | ❌        | ✅     | ✅     |
| `zh` | Chinese (Simplified) | ✅        | ✅     | ✅     |

</details>

---

<details>
<summary>🔗 Model Traits</summary>

Drop a trait into any Eloquent model to get `country`, `state`, and `city` accessors that work with both drivers.

### HasWorldRelations — all in one

```php
// Migration
$table->unsignedBigInteger('country_id')->nullable();
$table->unsignedBigInteger('state_id')->nullable();
$table->unsignedBigInteger('city_id')->nullable();
```

```php
use Imujas9\World\Traits\HasWorldRelations;

class User extends Authenticatable
{
    use HasWorldRelations;
}
```

```php
$user->country;             // CountryData (both drivers)
$user->country->name;       // "India"
$user->country->flag;       // "🇮🇳"
$user->state->name;         // "Gujarat"
$user->city->name;          // "Ahmedabad"

// Eager loading (database driver only)
User::with('country', 'state', 'city')->get();
// $user->country still returns CountryData, not a raw model
```

### Individual traits

| Trait        | Column needed | Type                 |
| ------------ | ------------- | -------------------- |
| `HasCountry` | `country_id`  | `unsignedBigInteger` |
| `HasState`   | `state_id`    | `unsignedBigInteger` |
| `HasCity`    | `city_id`     | `unsignedBigInteger` |

### Using string codes instead of integer IDs

```php
class User extends Authenticatable
{
    use HasCountry, HasState;

    protected string $countryForeignKey = 'country_code';
    protected string $countryOwnerKey   = 'code';

    protected string $stateForeignKey   = 'state_code';
    protected string $stateOwnerKey     = 'code';
}
```

### Driver behaviour

|                         | File driver      | Database driver                |
| ----------------------- | ---------------- | ------------------------------ |
| `$user->country`        | ✅ `CountryData` | ✅ `CountryData`               |
| `User::with('country')` | ❌ no DB table   | ✅ eager loads → `CountryData` |

</details>

---

<details>
<summary>🛠️ Contributing</summary>

Contributions, bug reports, and feature requests are welcome.

1. Fork the repo · create a branch · write tests · open a PR

```bash
composer install
./vendor/bin/phpunit
```

**Updating world data** — all data lives in `resources/data/` as plain JSON:

| File                                 | Purpose                                    |
| ------------------------------------ | ------------------------------------------ |
| `countries.json`                     | Country base data                          |
| `states.json`                        | State base data                            |
| `cities.json`                        | City base data                             |
| `translations/countries/{lang}.json` | Country names per language                 |
| `translations/states/{lang}.json`    | State names per language                   |
| `translations/cities/{lang}.json`    | City names per language (keyed by city ID) |

To add a new language, create `translations/countries/{lang}.json` with `{ "ISO2": "Name" }` pairs and repeat for states/cities.

</details>

---

## About

Hi, I'm **Ujas Patel** — a Backend Developer(TALL Stack), based in Ahmedabad, India. I built this package because every existing world-data solution for Laravel forces a database setup before you can use even a simple country list. Laravel World works out of the box — and scales to a full database driver when you need it.

**Reach out:** [imujaspatel [at] gmail [dot] com](mailto:imujaspatel@gmail.com)

[![GitHub](https://img.shields.io/badge/GitHub-imujas9-181717?style=flat-square&logo=github)](https://github.com/imujas9)
[![X](https://img.shields.io/badge/X-imujas9-1DA1F2?style=flat-square&logo=x)](https://x.com/imujas9)
[![LinkedIn](https://img.shields.io/badge/LinkedIn-imujas-0A66C2?style=flat-square&logo=linkedin)](https://www.linkedin.com/in/imujas)

---

## Support

| Need               | Where to go                                                                |
| ------------------ | -------------------------------------------------------------------------- |
| 🐛 Bug             | [Open an issue](https://github.com/imujas9/laravel-world/issues)           |
| 💡 Feature request | [Open an issue](https://github.com/imujas9/laravel-world/issues)           |
| ❓ Question        | [Start a discussion](https://github.com/imujas9/laravel-world/discussions) |

If this package saved you time, a ⭐ on [GitHub](https://github.com/imujas9/laravel-world) goes a long way.

---

## License

This package is open-source software licensed under the MIT License. Feel free to use, modify, and distribute it according to the terms of the license.
