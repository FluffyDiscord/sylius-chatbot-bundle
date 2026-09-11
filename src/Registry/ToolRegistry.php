<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Registry;

use FluffyDiscord\SyliusChatbotBundle\Contract\ChatbotToolInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

readonly class ToolRegistry
{
    public function __construct(
        #[AutowireLocator('fluffydiscord_chatbot.tool', indexAttribute: 'definition_name')]
        private ServiceLocator $tools,
    ) {
    }

    public function get(string $name): ?ChatbotToolInterface
    {
        $isKnown = $this->tools->has($name);
        if (!$isKnown) {
            return null;
        }

        return $this->tools->get($name);
    }

    public function all(): iterable
    {
        foreach (array_keys($this->tools->getProvidedServices()) as $name) {
            yield $this->tools->get((string) $name);
        }
    }
}
