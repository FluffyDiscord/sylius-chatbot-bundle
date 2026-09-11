<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Controller;

use FluffyDiscord\SyliusChatbotBundle\Channel\ChannelResolver;
use FluffyDiscord\SyliusChatbotBundle\Registry\DataSourceRegistry;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

class ListSourcesController extends AbstractController
{
    public function __construct(
        private readonly DataSourceRegistry $dataSourceRegistry,
        private readonly ChannelResolver $channelResolver,
        private readonly TranslatorInterface $translator,
        private readonly LocaleContextInterface $localeContext,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $locale = $this->localeContext->getLocaleCode();

        $sources = [];
        foreach ($this->dataSourceRegistry->all() as $source) {
            $definition = $source->getDefinition();
            $definition = $definition
                ->withLocales($definition->locales ?? $this->resolveChannelLocales())
                ->translated($this->translator, $locale);
            $sources[] = $definition->jsonSerialize();
        }

        return $this->json(['sources' => $sources]);
    }

    private function resolveChannelLocales(): array
    {
        $locales = [];
        foreach ($this->channelResolver->getChannel()->getLocales() as $channelLocale) {
            $localeCode = $channelLocale->getCode();
            if ($localeCode !== null) {
                $locales[] = $localeCode;
            }
        }

        return $locales;
    }
}
