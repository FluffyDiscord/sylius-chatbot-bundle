<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Controller;

use FluffyDiscord\SyliusChatbotBundle\DTO\ToolListHeaders;
use FluffyDiscord\SyliusChatbotBundle\Locale\ShopLocaleResolver;
use FluffyDiscord\SyliusChatbotBundle\Registry\ToolRegistry;
use FluffyDiscord\SyliusChatbotBundle\Schema\ArgumentsSchemaGenerator;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

class ListToolsController extends AbstractController
{
    public function __construct(
        private readonly ToolRegistry $toolRegistry,
        private readonly ArgumentsSchemaGenerator $schemaGenerator,
        private readonly TranslatorInterface $translator,
        private readonly LocaleContextInterface $localeContext,
        private readonly ShopLocaleResolver $localeResolver,
    ) {
    }

    public function __invoke(ToolListHeaders $headers): JsonResponse
    {
        $locale = $this->resolveLocale($headers->locale);

        $tools = [];
        foreach ($this->toolRegistry->all() as $tool) {
            $definition = $tool->getDefinition()
                ->withInputSchema($this->schemaGenerator->generate($tool->getArgumentsClass()))
                ->translated($this->translator, $locale);
            $tools[] = $definition->jsonSerialize();
        }

        return $this->json(['tools' => $tools]);
    }

    private function resolveLocale(?string $requested): string
    {
        $contextLocale = $this->localeContext->getLocaleCode();

        if ($requested === null) {
            return $contextLocale;
        }

        $servedLocale = $this->localeResolver->resolveForChannel($requested);

        return $servedLocale ?? $contextLocale;
    }
}
