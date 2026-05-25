<?php

namespace Imujas9\World\Repositories\File;

use RuntimeException;

class FileDataLoader
{
    private array $cache = [];

    public function __construct(
        private readonly string  $dataPath,
        private readonly ?string $cityTranslationsPath = null,
    ) {}

    public function load(string $file): array
    {
        if (isset($this->cache[$file])) {
            return $this->cache[$file];
        }

        $path = rtrim($this->dataPath, '/') . '/' . ltrim($file, '/');

        if (! file_exists($path)) {
            throw new RuntimeException("World data file not found: {$path}");
        }

        $this->cache[$file] = json_decode(file_get_contents($path), true);

        return $this->cache[$file];
    }

    /**
     * Load a translation map for a given entity and language.
     *
     * City translations are not bundled with the package. They are stored in
     * the configured city_translations_path after running world:translations.
     * Returns an empty array when a language file is missing — the caller
     * falls back to the default language or the English name in the data file.
     */
    public function loadTranslation(string $entity, string $lang): array
    {
        $cacheKey = "{$entity}:{$lang}";

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        // City translations live in the user-controlled storage path
        if ($entity === 'cities' && $this->cityTranslationsPath !== null) {
            $storagePath = rtrim($this->cityTranslationsPath, '/') . "/{$lang}.json";

            if (file_exists($storagePath)) {
                $data = json_decode(file_get_contents($storagePath), true) ?? [];
                return $this->cache[$cacheKey] = $data;
            }

            // Not downloaded yet — return empty so caller falls back to English name
            return $this->cache[$cacheKey] = [];
        }

        // Countries and states translations are bundled with the package
        $file = "translations/{$entity}/{$lang}.json";
        $path = rtrim($this->dataPath, '/') . '/' . $file;

        if (! file_exists($path)) {
            return $this->cache[$cacheKey] = [];
        }

        $data = json_decode(file_get_contents($path), true) ?? [];
        return $this->cache[$cacheKey] = $data;
    }
}
