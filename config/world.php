<?php

return [

    /*
     * 'file'     — reads from bundled JSON files, zero setup required
     * 'database' — reads from DB tables (run migrations + php artisan world:seed first)
     */
    'driver' => env('WORLD_DRIVER', 'file'),

    /*
     * Default language used when lang() is not called explicitly.
     */
    'default_lang' => env('WORLD_LANG', 'en'),

    /*
     * Prefix applied to all world table names in database driver mode.
     */
    'table_prefix' => 'world_',

    /*
     * Override the path to the JSON data directory.
     * Leave null to use the bundled resources/data directory.
     */
    'data_path' => null,

    /*
     * Where downloaded city translation files are stored.
     * Run `php artisan world:translations {lang...}` to populate this directory.
     * City translations are NOT bundled with the package to keep the install size small.
     */
    'city_translations_path' => storage_path('app/world/translations/cities'),

];
