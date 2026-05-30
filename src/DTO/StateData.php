<?php

namespace Imujas9\World\DTO;

class StateData
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $code,
        public readonly string  $country_code,
        public readonly ?int    $country_id   = null,
        public readonly ?string $name         = null,
        public readonly array   $names        = [],
        public readonly ?string $type         = null,
        public readonly ?string $latitude     = null,
        public readonly ?string $longitude    = null,
    ) {}

    public static function fromEloquent(\Imujas9\World\Models\State $model): static
    {
        return new static(
            id:           (int) $model->id,
            code:         $model->code,
            country_code: $model->country_code,
            country_id:   isset($model->country_id) ? (int) $model->country_id : null,
            name:         $model->name      ?? null,
            type:         $model->type      ?? null,
            latitude:     $model->latitude  ?? null,
            longitude:    $model->longitude ?? null,
        );
    }

    public static function fromArray(array $data, array $resolvedNames = []): static
    {
        $name  = count($resolvedNames) === 1 ? array_values($resolvedNames)[0] : null;
        $names = count($resolvedNames) > 1   ? $resolvedNames                  : [];

        return new static(
            id:           (int) ($data['id'] ?? 0),
            code:         $data['code'],
            country_code: $data['country_code'],
            country_id:   isset($data['country_id']) ? (int) $data['country_id'] : null,
            name:         $name,
            names:        $names,
            type:         $data['type']      ?? null,
            latitude:     $data['latitude']  ?? null,
            longitude:    $data['longitude'] ?? null,
        );
    }

    public function toArray(): array
    {
        $base = [
            'id'           => $this->id,
            'code'         => $this->code,
            'country_code' => $this->country_code,
            'country_id'   => $this->country_id,
            'type'         => $this->type,
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
