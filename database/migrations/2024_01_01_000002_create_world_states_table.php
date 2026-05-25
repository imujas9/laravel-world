<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $table;
    private string $countriesTable;

    public function __construct()
    {
        $prefix               = config('world.table_prefix', 'world_');
        $this->table          = $prefix . 'states';
        $this->countriesTable = $prefix . 'countries';
    }

    public function up(): void
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('code', 10)->unique();
            $table->unsignedBigInteger('country_id')->index();
            $table->string('country_code', 2)->index();
            $table->string('name', 100)->nullable();
            $table->string('type', 50)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->json('translations')->nullable();

            $table->foreign('country_id')
                  ->references('id')
                  ->on($this->countriesTable);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table);
    }
};
