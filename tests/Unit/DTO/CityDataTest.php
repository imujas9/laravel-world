<?php

namespace Imujas9\World\Tests\Unit\DTO;

use PHPUnit\Framework\TestCase;
use Imujas9\World\DTO\CityData;

class CityDataTest extends TestCase
{
    private array $raw = [
        'id'           => 1,
        'name'         => 'Ahmedabad',
        'state_code'   => 'GJ',
        'country_code' => 'IN',
        'latitude'     => '23.02579430',
        'longitude'    => '72.58727230',
    ];

    public function test_creates_from_array_with_single_lang(): void
    {
        $dto = CityData::fromArray($this->raw, ['en' => 'Ahmedabad']);

        $this->assertSame(1, $dto->id);
        $this->assertSame('Ahmedabad', $dto->name);
        $this->assertSame('GJ', $dto->state_code);
        $this->assertSame('IN', $dto->country_code);
    }

    public function test_falls_back_to_base_name_when_no_translation_given(): void
    {
        $dto = CityData::fromArray($this->raw, []);

        // CityData falls back to the 'name' field from the raw array
        $this->assertSame('Ahmedabad', $dto->name);
    }

    public function test_creates_with_multiple_langs(): void
    {
        $dto = CityData::fromArray($this->raw, ['en' => 'Ahmedabad', 'hi' => 'अहमदाबाद']);

        $this->assertNull($dto->name);
        $this->assertSame(['en' => 'Ahmedabad', 'hi' => 'अहमदाबाद'], $dto->names);
    }

    public function test_to_array_includes_coordinates(): void
    {
        $dto    = CityData::fromArray($this->raw, ['en' => 'Ahmedabad']);
        $result = $dto->toArray();

        $this->assertSame('23.02579430', $result['latitude']);
        $this->assertSame('72.58727230', $result['longitude']);
        $this->assertSame(1, $result['id']);
    }

    public function test_id_is_cast_to_int(): void
    {
        $dto = CityData::fromArray(['id' => '42', 'name' => 'X', 'state_code' => 'S', 'country_code' => 'C'], []);
        $this->assertSame(42, $dto->id);
    }
}
