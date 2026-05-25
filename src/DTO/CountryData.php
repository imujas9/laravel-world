<?php

namespace Imujas9\World\DTO;

class CountryData
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $code,
        public readonly string  $iso3,
        public readonly string  $phone_code,
        public readonly string  $currency,
        public readonly ?string $flag       = null,
        public readonly ?string $region     = null,
        public readonly ?string $subregion  = null,
        public readonly ?string $name       = null,
        public readonly array   $names      = [],
    ) {}

    public static function fromEloquent(\Imujas9\World\Models\Country $model): static
    {
        return new static(
            id:         (int) $model->id,
            code:       $model->code,
            iso3:       $model->iso3       ?? '',
            phone_code: $model->phone_code ?? '',
            currency:   $model->currency   ?? '',
            flag:       $model->flag       ?? null,
            region:     $model->region     ?? null,
            subregion:  $model->subregion  ?? null,
            name:       $model->name       ?? null,
        );
    }

    public static function fromArray(array $data, array $resolvedNames = []): static
    {
        $name  = count($resolvedNames) === 1 ? array_values($resolvedNames)[0] : null;
        $names = count($resolvedNames) > 1   ? $resolvedNames                  : [];

        return new static(
            id:         (int) ($data['id'] ?? 0),
            code:       $data['code'],
            iso3:       $data['iso3']       ?? '',
            phone_code: $data['phone_code'] ?? '',
            currency:   $data['currency']   ?? '',
            flag:       $data['flag']       ?? null,
            region:     $data['region']     ?? null,
            subregion:  $data['subregion']  ?? null,
            name:       $name,
            names:      $names,
        );
    }

    public function toArray(): array
    {
        $base = [
            'id'         => $this->id,
            'code'       => $this->code,
            'iso3'       => $this->iso3,
            'phone_code' => $this->phone_code,
            'currency'   => $this->currency,
            'flag'       => $this->flag,
            'region'     => $this->region,
            'subregion'  => $this->subregion,
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
