<?php

namespace Imujas9\World\Commands;

use Illuminate\Console\Command;

class WorldTranslationsCommand extends Command
{
    protected $signature = 'world:translations
                            {langs?* : Language codes to download (e.g. hi fr de). Omit to see available list.}
                            {--all   : Download all available languages}
                            {--force : Re-download even if already present}';

    protected $description = 'Download city translation files for the file driver';

    const AVAILABLE = [
        'ar' => 'Arabic',
        'bn' => 'Bengali',
        'br' => 'Breton',
        'de' => 'German',
        'es' => 'Spanish',
        'fa' => 'Persian',
        'fr' => 'French',
        'ga' => 'Irish',
        'hi' => 'Hindi',
        'hr' => 'Croatian',
        'hy' => 'Armenian',
        'id' => 'Indonesian',
        'it' => 'Italian',
        'ja' => 'Japanese',
        'ko' => 'Korean',
        'nl' => 'Dutch',
        'pl' => 'Polish',
        'pt' => 'Portuguese',
        'ru' => 'Russian',
        'tr' => 'Turkish',
        'uk' => 'Ukrainian',
        'ur' => 'Urdu',
        'vi' => 'Vietnamese',
        'zh' => 'Chinese (Simplified)',
    ];

    const SOURCE_URL = 'https://raw.githubusercontent.com/imujas9/laravel-world/main/resources/data/translations/cities/{lang}.json';

    public function handle(): int
    {
        $langs = $this->option('all')
            ? array_keys(self::AVAILABLE)
            : $this->argument('langs');

        if (empty($langs)) {
            $this->showAvailable();
            return self::SUCCESS;
        }

        $invalid = array_diff($langs, array_keys(self::AVAILABLE));
        foreach ($invalid as $code) {
            $this->warn("  Unknown language code: {$code}");
        }
        $langs = array_intersect($langs, array_keys(self::AVAILABLE));

        if (empty($langs)) {
            return self::FAILURE;
        }

        $storagePath = config('world.city_translations_path', storage_path('app/world/translations/cities'));

        if (! is_dir($storagePath) && ! mkdir($storagePath, 0755, true)) {
            $this->error("Could not create directory: {$storagePath}");
            return self::FAILURE;
        }

        $force    = $this->option('force');
        $success  = 0;
        $skipped  = 0;
        $failed   = 0;

        foreach ($langs as $lang) {
            $dest = "{$storagePath}/{$lang}.json";
            $name = self::AVAILABLE[$lang];

            if (file_exists($dest) && ! $force) {
                $this->line("  <comment>skip</comment>  {$lang} ({$name}) — already downloaded, use --force to update");
                $skipped++;
                continue;
            }

            $url     = str_replace('{lang}', $lang, self::SOURCE_URL);
            $content = $this->download($url);

            if ($content === null) {
                $this->line("  <fg=red>fail</>  {$lang} ({$name})");
                $failed++;
                continue;
            }

            file_put_contents($dest, $content);
            $kb = round(strlen($content) / 1024);
            $this->line("  <info>done</info>  {$lang} ({$name}) — {$kb} KB");
            $success++;
        }

        $this->newLine();
        $this->line("Downloaded: <info>{$success}</info>  Skipped: <comment>{$skipped}</comment>  Failed: <fg=red>{$failed}</>");

        if ($success > 0) {
            $this->line("Stored in: {$storagePath}");
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function showAvailable(): void
    {
        $storagePath = config('world.city_translations_path', storage_path('app/world/translations/cities'));

        $this->line('Available city translation languages:');
        $this->newLine();

        foreach (self::AVAILABLE as $code => $name) {
            $file      = "{$storagePath}/{$code}.json";
            $status    = file_exists($file) ? '<info>downloaded</info>' : '<comment>not downloaded</comment>';
            $this->line("  <fg=cyan>{$code}</> — {$name} [{$status}]");
        }

        $this->newLine();
        $this->line('Usage:');
        $this->line('  <fg=cyan>php artisan world:translations hi fr de</>   download specific languages');
        $this->line('  <fg=cyan>php artisan world:translations --all</>       download all languages');
        $this->line('  <fg=cyan>php artisan world:translations hi --force</>  re-download to update');
    }

    private function download(string $url): ?string
    {
        $context = stream_context_create([
            'http' => [
                'timeout'    => 30,
                'user_agent' => 'laravel-world-package',
            ],
        ]);

        $content = @file_get_contents($url, false, $context);

        return $content !== false ? $content : null;
    }
}
