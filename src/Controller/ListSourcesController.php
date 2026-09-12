<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Controller;

use FluffyDiscord\SyliusChatbotBundle\Locale\ShopLocaleResolver;
use FluffyDiscord\SyliusChatbotBundle\Registry\DataSourceRegistry;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

class ListSourcesController extends AbstractController
{
    public function __construct(
        private readonly DataSourceRegistry $dataSourceRegistry,
        private readonly TranslatorInterface $translator,
        private readonly LocaleContextInterface $localeContext,
        private readonly ShopLocaleResolver $localeResolver,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $locale = $this->localeContext->getLocaleCode();
        $channelLocales = $this->localeResolver->getChannelLocales();

        $sources = [];
        foreach ($this->dataSourceRegistry->all() as $source) {
            $definition = $source->getDefinition();
            $definition = $definition
                ->withLocales($definition->locales ?? $channelLocales)
                ->translated($this->translator, $locale);
            $sources[] = $definition->jsonSerialize();
        }

        return $this->json(['sources' => $sources]);
    }
}
