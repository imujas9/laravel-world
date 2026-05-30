<?php

namespace Imujas9\World\Repositories\File;

use Generator;
use RuntimeException;

class FileDataLoader
{
    /** In-process memory cache — prevents re-reading the same file twice per request. */
    private array $memory = [];

    public function __construct(
        private readonly string  $dataPath,
        private readonly ?string $cityTranslationsPath = null,
    ) {}

    public function load(string $file): array
    {
        if (isset($this->memory[$file])) {
            return $this->memory[$file];
        }

        return $this->memory[$file] = $this->readJson($this->resolvePath($file));
    }

    /**
     * Stream a JSON array file one object at a time without loading the entire
     * file into memory. Essential for large files like cities.json (~30 MB).
     */
    public function stream(string $file): Generator
    {
        $path = $this->resolvePath($file);

        $handle   = fopen($path, 'r');
        $buffer   = '';
        $depth    = 0;
        $inString = false;
        $escape   = false;

        while (! feof($handle)) {
            $chunk = fread($handle, 65536);

            for ($i = 0, $len = strlen($chunk); $i < $len; $i++) {
                $c = $chunk[$i];

                if ($escape) {
                    $escape = false;
                    if ($depth > 0) {
                        $buffer .= $c;
                    }
                    continue;
                }

                if ($c === '\\' && $inString) {
                    $escape = true;
                    if ($depth > 0) {
                        $buffer .= $c;
                    }
                    continue;
                }

                if ($c === '"') {
                    $inString = ! $inString;
                    if ($depth > 0) {
                        $buffer .= $c;
                    }
                    continue;
                }

                if ($inString) {
                    if ($depth > 0) {
                        $buffer .= $c;
                    }
                    continue;
                }

                if ($c === '{') {
                    $depth++;
                    $buffer .= $c;
                } elseif ($c === '}' && $depth > 0) {
                    $buffer .= $c;
                    $depth--;
                    if ($depth === 0) {
                        $data = json_decode($buffer, true);
                        if ($data !== null) {
                            yield $data;
                        }
                        $buffer = '';
                    }
                } elseif ($depth > 0) {
                    $buffer .= $c;
                }
            }
        }

        fclose($handle);
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
        $memKey = "{$entity}:{$lang}";

        if (isset($this->memory[$memKey])) {
            return $this->memory[$memKey];
        }

        return $this->memory[$memKey] = $this->resolveTranslation($entity, $lang);
    }

    public function dataPath(): string
    {
        return $this->dataPath;
    }

    public function cityTranslationsPath(): ?string
    {
        return $this->cityTranslationsPath;
    }

    private function resolvePath(string $file): string
    {
        $path = rtrim($this->dataPath, '/') . '/' . ltrim($file, '/');

        if (! file_exists($path)) {
            throw new RuntimeException("World data file not found: {$path}");
        }

        return $path;
    }

    private function readJson(string $path): array
    {
        return json_decode(file_get_contents($path), true) ?? [];
    }

    private function resolveTranslation(string $entity, string $lang): array
    {
        if ($entity === 'cities' && $this->cityTranslationsPath !== null) {
            $path = rtrim($this->cityTranslationsPath, '/') . "/{$lang}.json";

            if (file_exists($path)) {
                return json_decode(file_get_contents($path), true) ?? [];
            }

            return [];
        }

        $path = rtrim($this->dataPath, '/') . "/translations/{$entity}/{$lang}.json";

        if (! file_exists($path)) {
            return [];
        }

        return json_decode(file_get_contents($path), true) ?? [];
    }
}
