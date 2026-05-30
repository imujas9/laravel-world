<?php

namespace Imujas9\World\DTO;

class CityData
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $state_code,
        public readonly string  $country_code,
        public readonly ?int    $state_id    = null,
        public readonly ?int    $country_id  = null,
        public readonly ?string $name        = null,
        public readonly array   $names       = [],
        public readonly ?string $latitude    = null,
        public readonly ?string $longitude   = null,
    ) {}

    public static function fromEloquent(\Imujas9\World\Models\City $model): static
    {
        return new static(
            id:           (int) $model->id,
            state_code:   $model->state_code,
            country_code: $model->country_code,
            state_id:     isset($model->state_id)   ? (int) $model->state_id   : null,
            country_id:   isset($model->country_id) ? (int) $model->country_id : null,
            name:         $model->name     ?? null,
            latitude:     $model->latitude ?? null,
            longitude:    $model->longitude ?? null,
        );
    }

    public static function fromArray(array $data, array $resolvedNames = []): static
    {
        $name  = count($resolvedNames) === 1 ? array_values($resolvedNames)[0] : null;
        $names = count($resolvedNames) > 1   ? $resolvedNames                  : [];

        return new static(
            id:           (int) $data['id'],
            state_code:   $data['state_code'],
            country_code: $data['country_code'],
            state_id:     isset($data['state_id'])   ? (int) $data['state_id']   : null,
            country_id:   isset($data['country_id']) ? (int) $data['country_id'] : null,
            name:         $name ?? (count($resolvedNames) === 0 ? ($data['name'] ?? null) : null),
            names:        $names,
            latitude:     $data['latitude']  ?? null,
            longitude:    $data['longitude'] ?? null,
        );
    }

    public function toArray(): array
    {
        $base = [
            'id'           => $this->id,
            'state_code'   => $this->state_code,
            'country_code' => $this->country_code,
            'state_id'     => $this->state_id,
            'country_id'   => $this->country_id,
            'latitude'     => $this->latitude,
            'longitude'    => $this->longitude,
        ];

        if ($this->names !== []) {
            foreach ($this->names as $lang => $value) {
                $base["name_{$lang}"] = $value;
            }
        } else {
            $base['name'] = $this->name;
        }

        return $base;
    }
}
