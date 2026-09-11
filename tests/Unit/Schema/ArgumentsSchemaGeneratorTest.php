<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Tests\Unit\Schema;

use FluffyDiscord\SyliusChatbotBundle\Schema\ArgumentsSchemaGenerator;
use FluffyDiscord\SyliusChatbotBundle\Tests\Unit\Fixtures\NullableArguments;
use FluffyDiscord\SyliusChatbotBundle\Tool\DTO\OrderStatusArguments;
use FluffyDiscord\SyliusChatbotBundle\Tool\DTO\ProductAvailabilityArguments;
use PHPUnit\Framework\TestCase;

class ArgumentsSchemaGeneratorTest extends TestCase
{
    private ArgumentsSchemaGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new ArgumentsSchemaGenerator();
    }

    public function testOrderStatusArgumentsSchema(): void
    {
        $schema = $this->generator->generate(OrderStatusArguments::class);

        self::assertSame('object', $schema['type']);
        self::assertSame(['orderNumber', 'email'], $schema['required']);
        self::assertSame('string', $schema['properties']['orderNumber']['type']);
        self::assertSame(32, $schema['properties']['orderNumber']['maxLength']);
        self::assertSame('email', $schema['properties']['email']['format']);
        self::assertFalse($schema['additionalProperties']);
    }

    public function testNullablePropertyIsNotRequired(): void
    {
        $schema = $this->generator->generate(NullableArguments::class);

        self::assertSame(['query'], $schema['required']);
        self::assertArrayHasKey('note', $schema['properties']);
        self::assertSame(['relevance', 'price'], $schema['properties']['sort']['enum']);
    }

    public function testArrayPropertySchema(): void
    {
        $schema = $this->generator->generate(ProductAvailabilityArguments::class);

        self::assertSame('array', $schema['properties']['codes']['type']);
        self::assertSame('string', $schema['properties']['codes']['items']['type']);
        self::assertSame(['codes'], $schema['required']);
    }
}
