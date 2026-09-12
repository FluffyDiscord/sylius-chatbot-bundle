<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Tests\Unit\Fixtures;

use FluffyDiscord\SyliusChatbotBundle\DTO\NotificationOutcome;
use FluffyDiscord\SyliusChatbotBundle\Enum\CatalogSourceName;
use FluffyDiscord\SyliusChatbotBundle\Ingest\CatalogChangeNotifier;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;

class RecordingCatalogChangeNotifier extends CatalogChangeNotifier
{
    /** @var list<array{0: CatalogSourceName, 1: string, 2: string}> */
    public array $collectedChanges = [];

    /** @var list<array{0: CatalogSourceName, 1: string, 2: list<string>}> */
    public array $notifications = [];

    public function __construct()
    {
        parent::__construct(new MockHttpClient(), new NullLogger(), 'https://backend.test', 'secret', 'site-key', 'test');
    }

    public function collect(CatalogSourceName $source, string $locale, string $externalId): void
    {
        $this->collectedChanges[] = [$source, $locale, $externalId];
    }

    public function notify(CatalogSourceName $source, string $locale, array $externalIds): NotificationOutcome
    {
        $this->notifications[] = [$source, $locale, $externalIds];

        return new NotificationOutcome(true);
    }
}
