<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Controller;

use FluffyDiscord\SyliusChatbotBundle\Channel\ChannelResolver;
use FluffyDiscord\SyliusChatbotBundle\DTO\SourceQuery;
use FluffyDiscord\SyliusChatbotBundle\Exception\SourceNotFoundException;
use FluffyDiscord\SyliusChatbotBundle\Locale\ShopLocaleResolver;
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
        private readonly ShopLocaleResolver $localeResolver,
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

        $servedLocales = $source->getDefinition()->locales ?? $this->localeResolver->getChannelLocales();
        $servedLocale = $this->localeResolver->resolveServedLocaleOrFail($query->locale, $servedLocales);

        $page = $source->getDocuments($query->withLocale($servedLocale));

        return $this->json($page->jsonSerialize());
    }
}
