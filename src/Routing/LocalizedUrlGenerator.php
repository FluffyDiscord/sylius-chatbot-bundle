<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Routing;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

readonly class LocalizedUrlGenerator
{
    public function __construct(
        private RouterInterface $router,
    ) {
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function generateAbsoluteUrl(string $routeName, array $parameters, string $locale): string
    {
        $context = $this->router->getContext();
        $contextParameters = $context->getParameters();

        $context->setParameter($this->getLocaleParameterName(), $locale);

        try {
            return $this->router->generate($routeName, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
        } finally {
            $context->setParameters($contextParameters);
        }
    }

    private function getLocaleParameterName(): string
    {
        return '_locale';
    }
}
