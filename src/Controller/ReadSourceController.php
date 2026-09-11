<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Controller;

use FluffyDiscord\SyliusChatbotBundle\Channel\ChannelResolver;
use FluffyDiscord\SyliusChatbotBundle\DTO\SourceQuery;
use FluffyDiscord\SyliusChatbotBundle\Exception\InvalidLocaleException;
use FluffyDiscord\SyliusChatbotBundle\Exception\SourceNotFoundException;
use FluffyDiscord\SyliusChatbotBundle\Registry\DataSourceRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;

class ReadSourceController extends AbstractController
{
    public function __construct(
        private readonly DataSourceRegistry $dataSourceRegistry,
        private readonly ChannelResolver $channelResolver,
    ) {
    }

    public function __invoke(
        string $name,
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_BAD_REQUEST)] SourceQuery $query,
    ): JsonResponse {
        $source = $this->dataSourceRegistry->get($name);
        if ($source === null) {
            throw new SourceNotFoundException($name);
        }

        $this->channelResolver->setOverrideCode($query->channel);

        $locales = $source->getDefinition()->locales ?? $this->resolveChannelLocales();
        $isServedLocale = in_array($query->locale, $locales, true);
        if (!$isServedLocale) {
            throw new InvalidLocaleException($query->locale);
        }

        $page = $source->getDocuments($query);

        return $this->json($page->jsonSerialize());
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
