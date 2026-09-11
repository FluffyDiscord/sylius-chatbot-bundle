<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Contract;

use FluffyDiscord\SyliusChatbotBundle\DTO\ToolCallContext;
use FluffyDiscord\SyliusChatbotBundle\DTO\ToolDefinition;
use FluffyDiscord\SyliusChatbotBundle\DTO\ToolResult;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('fluffydiscord_chatbot.tool')]
interface ChatbotToolInterface
{
    public function getDefinition(): ToolDefinition;

    public function getArgumentsClass(): string;

    public function execute(object $arguments, ToolCallContext $context): ToolResult;
}
