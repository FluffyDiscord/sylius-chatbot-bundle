<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Tests\Unit\Registry;

use FluffyDiscord\SyliusChatbotBundle\Registry\ToolRegistry;
use FluffyDiscord\SyliusChatbotBundle\Tests\Unit\Fixtures\AlphaTool;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

class ToolRegistryTest extends TestCase
{
    public function testUnknownNameReturnsNull(): void
    {
        $registry = new ToolRegistry(new ServiceLocator([]));

        self::assertNull($registry->get('unknown'));
    }

    public function testKnownNameReturnsTool(): void
    {
        $tool = new AlphaTool();
        $registry = new ToolRegistry(new ServiceLocator([
            'alpha_tool' => fn (): AlphaTool => $tool,
        ]));

        self::assertSame($tool, $registry->get('alpha_tool'));
        self::assertSame([$tool], iterator_to_array($registry->all(), false));
    }
}
