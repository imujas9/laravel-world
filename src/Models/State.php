<?php

namespace Imujas9\World\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class State extends Model
{
    public $timestamps   = false;
    public $incrementing = false;

    protected $primaryKey = 'id';
    protected $keyType    = 'int';

    protected $casts = [
        'id'           => 'integer',
        'country_id'   => 'integer',
        'translations' => 'array',
    ];

    public function getTable(): string
    {
        return config('world.table_prefix', 'world_') . 'states';
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class, 'state_id', 'id');
    }
}
