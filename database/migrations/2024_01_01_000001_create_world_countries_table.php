<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $table;

    public function __construct()
    {
        $this->table = config('world.table_prefix', 'world_') . 'countries';
    }

    public function up(): void
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('code', 2)->unique();
            $table->string('iso3', 3)->nullable()->index();
            $table->string('phone_code', 10)->nullable();
            $table->string('currency', 10)->nullable();
            $table->string('flag', 10)->nullable();
            $table->string('name', 100)->nullable()->index();
            $table->string('region', 50)->nullable()->index();
            $table->string('subregion', 100)->nullable();
            $table->string('capital', 100)->nullable();
            $table->string('tld', 10)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->json('translations')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table);
    }
};
