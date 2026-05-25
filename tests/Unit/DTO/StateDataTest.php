<?php

namespace Imujas9\World\Tests\Unit\DTO;

use PHPUnit\Framework\TestCase;
use Imujas9\World\DTO\StateData;

class StateDataTest extends TestCase
{
    private array $raw = [
        'id'           => 1,
        'code'         => 'GJ',
        'country_code' => 'IN',
        'country_id'   => 1,
        'type'         => 'State',
        'latitude'     => '22.25865200',
        'longitude'    => '71.19238050',
    ];

    public function test_creates_from_array_with_single_lang(): void
    {
        $dto = StateData::fromArray($this->raw, ['en' => 'Gujarat']);

        $this->assertSame(1, $dto->id);
        $this->assertSame('GJ', $dto->code);
        $this->assertSame('IN', $dto->country_code);
        $this->assertSame(1, $dto->country_id);
        $this->assertSame('State', $dto->type);
        $this->assertSame('Gujarat', $dto->name);
        $this->assertSame([], $dto->names);
    }

    public function test_creates_from_array_with_multiple_langs(): void
    {
        $dto = StateData::fromArray($this->raw, ['en' => 'Gujarat', 'hi' => 'गुजरात']);

        $this->assertNull($dto->name);
        $this->assertSame(['en' => 'Gujarat', 'hi' => 'गुजरात'], $dto->names);
    }

    public function test_to_array_with_single_lang(): void
    {
        $dto    = StateData::fromArray($this->raw, ['en' => 'Gujarat']);
        $result = $dto->toArray();

        $this->assertSame(1, $result['id']);
        $this->assertSame('Gujarat', $result['name']);
        $this->assertSame('GJ', $result['code']);
        $this->assertSame('IN', $result['country_code']);
        $this->assertSame(1, $result['country_id']);
    }

    public function test_to_array_with_multiple_langs(): void
    {
        $dto    = StateData::fromArray($this->raw, ['en' => 'Gujarat', 'hi' => 'गुजरात']);
        $result = $dto->toArray();

        $this->assertArrayNotHasKey('name', $result);
        $this->assertSame('Gujarat', $result['name_en']);
        $this->assertSame('गुजरात', $result['name_hi']);
    }
}
