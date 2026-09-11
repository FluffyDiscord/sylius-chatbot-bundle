<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Tests\Unit\Fixtures;

use FluffyDiscord\SyliusChatbotBundle\Contract\ChatbotToolInterface;
use FluffyDiscord\SyliusChatbotBundle\DTO\ToolCallContext;
use FluffyDiscord\SyliusChatbotBundle\DTO\ToolDefinition;
use FluffyDiscord\SyliusChatbotBundle\DTO\ToolResult;

class AlphaTool implements ChatbotToolInterface
{
    public function getDefinition(): ToolDefinition
    {
        return new ToolDefinition('alpha_tool', 'fixture.alpha_tool');
    }

    public function getArgumentsClass(): string
    {
        return NullableArguments::class;
    }

    public function execute(object $arguments, ToolCallContext $context): ToolResult
    {
        return new ToolResult([]);
    }
}
