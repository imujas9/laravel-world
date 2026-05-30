<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a searchable `name` column to the countries table and an index on the
 * `name` column of all three world tables for LIKE-search performance.
 *
 * Run this migration if you created the tables before v1.2.0.
 */
return new class extends Migration
{
    private string $countries;
    private string $states;
    private string $cities;

    public function __construct()
    {
        $prefix          = config('world.table_prefix', 'world_');
        $this->countries = $prefix . 'countries';
        $this->states    = $prefix . 'states';
        $this->cities    = $prefix . 'cities';
    }

    public function up(): void
    {
        // Countries: add name column (English default name) + index
        if (Schema::hasTable($this->countries)) {
            Schema::table($this->countries, function (Blueprint $table) {
                if (! Schema::hasColumn($this->countries, 'name')) {
                    $table->string('name', 100)->nullable()->after('subregion');
                }
                if (! $this->hasIndex($this->countries, 'name')) {
                    $table->index('name');
                }
            });
        }

        // States: add index on existing name column
        if (Schema::hasTable($this->states) && ! $this->hasIndex($this->states, 'name')) {
            Schema::table($this->states, function (Blueprint $table) {
                $table->index('name');
            });
        }

        // Cities: add index on existing name column
        if (Schema::hasTable($this->cities) && ! $this->hasIndex($this->cities, 'name')) {
            Schema::table($this->cities, function (Blueprint $table) {
                $table->index('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable($this->countries)) {
            Schema::table($this->countries, function (Blueprint $table) {
                if ($this->hasIndex($this->countries, 'name')) {
                    $table->dropIndex([$this->countries . '_name_index']);
                }
                if (Schema::hasColumn($this->countries, 'name')) {
                    $table->dropColumn('name');
                }
            });
        }

        if (Schema::hasTable($this->states) && $this->hasIndex($this->states, 'name')) {
            Schema::table($this->states, function (Blueprint $table) {
                $table->dropIndex([$this->states . '_name_index']);
            });
        }

        if (Schema::hasTable($this->cities) && $this->hasIndex($this->cities, 'name')) {
            Schema::table($this->cities, function (Blueprint $table) {
                $table->dropIndex([$this->cities . '_name_index']);
            });
        }
    }

    private function hasIndex(string $table, string $column): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn ($index) => in_array($column, $index['columns'], true));
    }
};
