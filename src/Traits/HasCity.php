<?php

namespace Imujas9\World\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Imujas9\World\DTO\CityData;
use Imujas9\World\Facades\City as CityFacade;
use Imujas9\World\Models\City;

trait HasCity
{
    public function getCityAttribute(): ?CityData
    {
        if (empty($this->city_id)) {
            return null;
        }

        if ($this->relationLoaded('city')) {
            $model = $this->getRelation('city');
            return $model ? CityData::fromEloquent($model) : null;
        }

        return CityFacade::find($this->city_id);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id', 'id');
    }
}
