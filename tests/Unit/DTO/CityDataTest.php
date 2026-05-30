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

        $this->assertSame('Ahmedabad', $dto->name);
    }

    public function test_creates_with_multiple_langs(): void
    {
        $dto = CityData::fromArray($this->raw, ['en' => 'Ahmedabad', 'hi' => 'अहमदाबाद']);

        $this->assertNull($dto->name);
        $this->assertSame(['en' => 'Ahmedabad', 'hi' => 'अहमदाबाद'], $dto->names);
    }

    public function test_state_id_and_country_id_are_null_when_absent(): void
    {
        $dto = CityData::fromArray($this->raw, []);

        $this->assertNull($dto->state_id);
        $this->assertNull($dto->country_id);
    }

    public function test_state_id_and_country_id_populated_when_present(): void
    {
        $dto = CityData::fromArray(array_merge($this->raw, ['state_id' => 5, 'country_id' => 101]), []);

        $this->assertSame(5, $dto->state_id);
        $this->assertSame(101, $dto->country_id);
    }

    public function test_state_id_and_country_id_cast_to_int(): void
    {
        $dto = CityData::fromArray(array_merge($this->raw, ['state_id' => '7', 'country_id' => '42']), []);

        $this->assertSame(7, $dto->state_id);
        $this->assertSame(42, $dto->country_id);
    }

    public function test_to_array_includes_coordinates(): void
    {
        $dto    = CityData::fromArray($this->raw, ['en' => 'Ahmedabad']);
        $result = $dto->toArray();

        $this->assertSame('23.02579430', $result['latitude']);
        $this->assertSame('72.58727230', $result['longitude']);
        $this->assertSame(1, $result['id']);
    }

    public function test_to_array_includes_state_id_and_country_id(): void
    {
        $dto    = CityData::fromArray(array_merge($this->raw, ['state_id' => 5, 'country_id' => 101]), []);
        $result = $dto->toArray();

        $this->assertSame(5, $result['state_id']);
        $this->assertSame(101, $result['country_id']);
    }

    public function test_id_is_cast_to_int(): void
    {
        $dto = CityData::fromArray(['id' => '42', 'name' => 'X', 'state_code' => 'S', 'country_code' => 'C'], []);
        $this->assertSame(42, $dto->id);
    }
}
