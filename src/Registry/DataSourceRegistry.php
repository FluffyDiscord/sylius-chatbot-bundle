<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Registry;

use FluffyDiscord\SyliusChatbotBundle\Contract\ChatbotDataSourceInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

readonly class DataSourceRegistry
{
    public function __construct(
        #[AutowireLocator('fluffydiscord_chatbot.source', indexAttribute: 'definition_name')]
        private ServiceLocator $sources,
    ) {
    }

    public function get(string $name): ?ChatbotDataSourceInterface
    {
        $isKnown = $this->sources->has($name);
        if (!$isKnown) {
            return null;
        }

        return $this->sources->get($name);
    }

    public function all(): iterable
    {
        foreach (array_keys($this->sources->getProvidedServices()) as $name) {
            yield $this->sources->get((string) $name);
        }
    }
}
