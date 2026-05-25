<?php

namespace Imujas9\World\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    public $timestamps   = false;
    public $incrementing = false;

    protected $primaryKey = 'id';
    protected $keyType    = 'int';

    protected $casts = [
        'id'           => 'integer',
        'translations' => 'array',
    ];

    public function getTable(): string
    {
        return config('world.table_prefix', 'world_') . 'countries';
    }

    public function states(): HasMany
    {
        return $this->hasMany(State::class, 'country_id', 'id');
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class, 'country_id', 'id');
    }
}
