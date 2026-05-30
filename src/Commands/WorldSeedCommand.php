<?php

namespace Imujas9\World\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WorldSeedCommand extends Command
{
    protected $signature   = 'world:seed {--truncate : Truncate tables before seeding}';
    protected $description = 'Seed world countries, states and cities into the database from bundled JSON files';

    public function handle(): int
    {
        $dataPath = config('world.data_path') ?? __DIR__ . '/../../resources/data';
        $prefix   = config('world.table_prefix', 'world_');

        if ($this->option('truncate')) {
            $this->info('Truncating world tables...');
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table("{$prefix}cities")->truncate();
            DB::table("{$prefix}states")->truncate();
            DB::table("{$prefix}countries")->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $countryCodeToId = $this->seedCountries($dataPath, $prefix);
        $stateCodeToId   = $this->seedStates($dataPath, $prefix, $countryCodeToId);
        $this->seedCities($dataPath, $prefix, $countryCodeToId, $stateCodeToId);

        $this->info('World data seeded successfully.');
        return self::SUCCESS;
    }

    private function seedCountries(string $dataPath, string $prefix): array
    {
        $this->info('Seeding countries...');

        $countries    = $this->loadJson("{$dataPath}/countries.json");
        $translations = $this->loadTranslations($dataPath, 'countries');

        $codeToId = [];
        $rows     = [];

        foreach ($countries as $country) {
            $code    = $country['code'];
            $id      = (int) $country['id'];
            $codeToId[$code] = $id;

            $countryTranslations = [];
            foreach ($translations as $lang => $map) {
                if (isset($map[$code])) {
                    $countryTranslations[$lang] = $map[$code];
                }
            }

            $rows[] = [
                'id'           => $id,
                'code'         => $code,
                'name'         => $countryTranslations['en'] ?? null,
                'iso3'         => $country['iso3']       ?? null,
                'phone_code'   => $country['phone_code'] ?? null,
                'currency'     => $country['currency']   ?? null,
                'flag'         => $country['flag']       ?? null,
                'region'       => $country['region']     ?? null,
                'subregion'    => $country['subregion']  ?? null,
                'capital'      => $country['capital']    ?? null,
                'tld'          => $country['tld']        ?? null,
                'latitude'     => $country['latitude']   ?? null,
                'longitude'    => $country['longitude']  ?? null,
                'translations' => json_encode($countryTranslations),
            ];
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table("{$prefix}countries")->insertOrIgnore($chunk);
        }

        $this->line('  ' . count($rows) . ' countries seeded.');
        return $codeToId;
    }

    private function seedStates(string $dataPath, string $prefix, array $countryCodeToId): array
    {
        $this->info('Seeding states...');

        $states       = $this->loadJson("{$dataPath}/states.json");
        $translations = $this->loadTranslations($dataPath, 'states');

        $codeToId = [];
        $rows     = [];

        foreach ($states as $state) {
            $code    = $state['code'];
            $id      = (int) $state['id'];
            $codeToId[$code] = $id;

            $stateTranslations = [];
            foreach ($translations as $lang => $map) {
                if (isset($map[$code])) {
                    $stateTranslations[$lang] = $map[$code];
                }
            }

            $rows[] = [
                'id'           => $id,
                'code'         => $code,
                'country_id'   => $countryCodeToId[$state['country_code']] ?? ($state['country_id'] ?? 0),
                'country_code' => $state['country_code'],
                'name'         => $state['name']      ?? null,
                'type'         => $state['type']      ?? null,
                'latitude'     => $state['latitude']  ?? null,
                'longitude'    => $state['longitude'] ?? null,
                'translations' => json_encode($stateTranslations),
            ];
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table("{$prefix}states")->insertOrIgnore($chunk);
        }

        $this->line('  ' . count($rows) . ' states seeded.');
        return $codeToId;
    }

    private function seedCities(string $dataPath, string $prefix, array $countryCodeToId, array $stateCodeToId): void
    {
        $this->info('Seeding cities...');

        $translations = $this->loadTranslations($dataPath, 'cities');
        $path         = "{$dataPath}/cities.json";

        if (! file_exists($path)) {
            $this->warn("File not found: {$path}");
            return;
        }

        // Stream cities one record at a time to avoid loading 30 MB into memory
        $chunk = [];
        $total = 0;

        foreach ($this->streamJson($path) as $city) {
            $id               = (string) $city['id'];
            $cityTranslations = [];
            foreach ($translations as $lang => $map) {
                if (isset($map[$id])) {
                    $cityTranslations[$lang] = $map[$id];
                }
            }

            $chunk[] = [
                'id'           => $city['id'],
                'name'         => $city['name'],
                'state_id'     => $stateCodeToId[$city['state_code']] ?? 0,
                'state_code'   => $city['state_code'],
                'country_id'   => $countryCodeToId[$city['country_code']] ?? 0,
                'country_code' => $city['country_code'],
                'latitude'     => $city['latitude']  ?? null,
                'longitude'    => $city['longitude'] ?? null,
                'translations' => json_encode($cityTranslations ?: null),
            ];

            if (count($chunk) >= 500) {
                DB::table("{$prefix}cities")->insertOrIgnore($chunk);
                $total += count($chunk);
                $chunk  = [];
            }
        }

        if ($chunk !== []) {
            DB::table("{$prefix}cities")->insertOrIgnore($chunk);
            $total += count($chunk);
        }

        $this->line("  {$total} cities seeded.");
    }

    private function streamJson(string $path): \Generator
    {
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
                    if ($depth > 0) $buffer .= $c;
                    continue;
                }

                if ($c === '\\' && $inString) {
                    $escape = true;
                    if ($depth > 0) $buffer .= $c;
                    continue;
                }

                if ($c === '"') {
                    $inString = ! $inString;
                    if ($depth > 0) $buffer .= $c;
                    continue;
                }

                if ($inString) {
                    if ($depth > 0) $buffer .= $c;
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

    private function loadJson(string $path): array
    {
        if (! file_exists($path)) {
            $this->warn("File not found: {$path}");
            return [];
        }
        return json_decode(file_get_contents($path), true) ?? [];
    }

    private function loadTranslations(string $dataPath, string $entity): array
    {
        $dir    = "{$dataPath}/translations/{$entity}";
        $result = [];

        if (! is_dir($dir)) {
            return $result;
        }

        foreach (glob("{$dir}/*.json") as $file) {
            $lang          = basename($file, '.json');
            $result[$lang] = json_decode(file_get_contents($file), true) ?? [];
        }

        return $result;
    }
}
