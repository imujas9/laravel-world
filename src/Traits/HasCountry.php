<?php

namespace Imujas9\World\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Imujas9\World\DTO\CountryData;
use Imujas9\World\Facades\Country as CountryFacade;
use Imujas9\World\Models\Country;

trait HasCountry
{
    // Override in your model to use code-based FK instead:
    //   protected string $countryForeignKey = 'country_code';
    //   protected string $countryOwnerKey   = 'code';
    protected string $countryForeignKey = 'country_id';
    protected string $countryOwnerKey   = 'id';

    public function getCountryAttribute(): ?CountryData
    {
        $fk = $this->{$this->countryForeignKey} ?? null;

        if (empty($fk)) {
            return null;
        }

        if ($this->relationLoaded('country')) {
            $model = $this->getRelation('country');
            return $model ? CountryData::fromEloquent($model) : null;
        }

        return $this->countryOwnerKey === 'id'
            ? CountryFacade::find((int) $fk)
            : CountryFacade::findByCode((string) $fk);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, $this->countryForeignKey, $this->countryOwnerKey);
    }
}
