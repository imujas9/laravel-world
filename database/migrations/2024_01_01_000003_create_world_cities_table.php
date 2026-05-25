<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $table;
    private string $countriesTable;
    private string $statesTable;

    public function __construct()
    {
        $prefix               = config('world.table_prefix', 'world_');
        $this->table          = $prefix . 'cities';
        $this->countriesTable = $prefix . 'countries';
        $this->statesTable    = $prefix . 'states';
    }

    public function up(): void
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name', 100);
            $table->unsignedBigInteger('state_id')->index();
            $table->string('state_code', 10)->index();
            $table->unsignedBigInteger('country_id')->index();
            $table->string('country_code', 2)->index();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->json('translations')->nullable();

            $table->foreign('country_id')
                  ->references('id')
                  ->on($this->countriesTable);

            $table->foreign('state_id')
                  ->references('id')
                  ->on($this->statesTable);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table);
    }
};
