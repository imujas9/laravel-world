<?php

namespace Imujas9\World\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends Model
{
    public $timestamps = false;

    protected $casts = [
        'id'         => 'integer',
        'state_id'   => 'integer',
        'country_id' => 'integer',
        'translations' => 'array',
    ];

    public function getTable(): string
    {
        return config('world.table_prefix', 'world_') . 'cities';
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id', 'id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }
}
