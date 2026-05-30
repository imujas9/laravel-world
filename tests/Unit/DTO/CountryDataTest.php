<?php

namespace Imujas9\World\Tests\Unit\DTO;

use PHPUnit\Framework\TestCase;
use Imujas9\World\DTO\CountryData;

class CountryDataTest extends TestCase
{
    private array $raw = [
        'id'         => 1,
        'code'       => 'IN',
        'iso3'       => 'IND',
        'phone_code' => '91',
        'currency'   => 'INR',
        'flag'       => '🇮🇳',
        'region'     => 'Asia',
        'subregion'  => 'Southern Asia',
        'capital'    => 'New Delhi',
        'tld'        => '.in',
        'latitude'   => '20.00000000',
        'longitude'  => '77.00000000',
    ];

    public function test_creates_from_array_with_single_lang(): void
    {
        $dto = CountryData::fromArray($this->raw, ['en' => 'India']);

        $this->assertSame(1, $dto->id);
        $this->assertSame('IN', $dto->code);
        $this->assertSame('IND', $dto->iso3);
        $this->assertSame('91', $dto->phone_code);
        $this->assertSame('INR', $dto->currency);
        $this->assertSame('🇮🇳', $dto->flag);
        $this->assertSame('Asia', $dto->region);
        $this->assertSame('India', $dto->name);
        $this->assertSame([], $dto->names);
    }

    public function test_exposes_capital_and_tld(): void
    {
        $dto = CountryData::fromArray($this->raw, ['en' => 'India']);

        $this->assertSame('New Delhi', $dto->capital);
        $this->assertSame('.in', $dto->tld);
    }

    public function test_exposes_latitude_and_longitude(): void
    {
        $dto = CountryData::fromArray($this->raw, ['en' => 'India']);

        $this->assertSame('20.00000000', $dto->latitude);
        $this->assertSame('77.00000000', $dto->longitude);
    }

    public function test_creates_from_array_with_multiple_langs(): void
    {
        $dto = CountryData::fromArray($this->raw, ['en' => 'India', 'hi' => 'भारत']);

        $this->assertNull($dto->name);
        $this->assertSame(['en' => 'India', 'hi' => 'भारत'], $dto->names);
    }

    public function test_creates_from_array_with_no_langs(): void
    {
        $dto = CountryData::fromArray($this->raw, []);

        $this->assertNull($dto->name);
        $this->assertSame([], $dto->names);
    }

    public function test_to_array_with_single_lang(): void
    {
        $dto    = CountryData::fromArray($this->raw, ['en' => 'India']);
        $result = $dto->toArray();

        $this->assertSame(1, $result['id']);
        $this->assertSame('India', $result['name']);
        $this->assertArrayNotHasKey('name_en', $result);
        $this->assertSame('IN', $result['code']);
        $this->assertSame('New Delhi', $result['capital']);
        $this->assertSame('.in', $result['tld']);
        $this->assertSame('20.00000000', $result['latitude']);
        $this->assertSame('77.00000000', $result['longitude']);
    }

    public function test_to_array_with_multiple_langs(): void
    {
        $dto    = CountryData::fromArray($this->raw, ['en' => 'India', 'hi' => 'भारत']);
        $result = $dto->toArray();

        $this->assertArrayNotHasKey('name', $result);
        $this->assertSame('India', $result['name_en']);
        $this->assertSame('भारत', $result['name_hi']);
    }

    public function test_handles_missing_optional_fields(): void
    {
        $dto = CountryData::fromArray(['id' => 99, 'code' => 'XX', 'iso3' => 'XXX', 'phone_code' => '', 'currency' => ''], []);

        $this->assertSame(99, $dto->id);
        $this->assertNull($dto->flag);
        $this->assertNull($dto->region);
        $this->assertNull($dto->subregion);
        $this->assertNull($dto->capital);
        $this->assertNull($dto->tld);
        $this->assertNull($dto->latitude);
        $this->assertNull($dto->longitude);
    }
}
