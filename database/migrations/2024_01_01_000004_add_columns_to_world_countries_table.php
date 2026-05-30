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
        Schema::table($this->table, function (Blueprint $table) {
            if (! Schema::hasColumn($this->table, 'capital')) {
                $table->string('capital', 100)->nullable()->after('subregion');
            }
            if (! Schema::hasColumn($this->table, 'tld')) {
                $table->string('tld', 10)->nullable()->after('capital');
            }
            if (! Schema::hasColumn($this->table, 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('tld');
            }
            if (! Schema::hasColumn($this->table, 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table($this->table, function (Blueprint $table) {
            $table->dropColumn(['capital', 'tld', 'latitude', 'longitude']);
        });
    }
};
