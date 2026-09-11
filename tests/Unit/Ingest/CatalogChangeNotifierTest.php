<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Tests\Unit\Ingest;

use FluffyDiscord\SyliusChatbotBundle\Enum\CatalogSourceName;
use FluffyDiscord\SyliusChatbotBundle\Ingest\CatalogChangeNotifier;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class CatalogChangeNotifierTest extends TestCase
{
    /** @var list<array{url: string, options: array}> */
    private array $capturedRequests = [];

    private function createNotifier(
        array $responses,
        string $backendUrl = 'https://backend.example',
        string $environment = 'prod',
    ): CatalogChangeNotifier {
        $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$responses): MockResponse {
            $this->capturedRequests[] = ['url' => $url, 'options' => $options];

            return array_shift($responses) ?? new MockResponse('', ['http_code' => 202]);
        });

        return new CatalogChangeNotifier(
            $client,
            new NullLogger(),
            $backendUrl,
            'ingest-secret',
            'site-key',
            $environment,
        );
    }

    public function testFlushPostsOnePayloadPerSourceAndLocale(): void
    {
        $notifier = $this->createNotifier([]);

        $notifier->collect(CatalogSourceName::Products, 'cs_CZ', 'T-SHIRT-01');
        $notifier->collect(CatalogSourceName::Products, 'cs_CZ', 'T-SHIRT-01');
        $notifier->collect(CatalogSourceName::Products, 'en_US', 'T-SHIRT-01');
        $notifier->collect(CatalogSourceName::Categories, 'cs_CZ', 'T_SHIRTS');
        $notifier->flush();

        self::assertCount(3, $this->capturedRequests);
        $firstRequest = $this->capturedRequests[0];
        self::assertSame('https://backend.example/api/v1/catalog/changes', $firstRequest['url']);
        self::assertSame(
            ['source' => 'products', 'locale' => 'cs_CZ', 'externalIds' => ['T-SHIRT-01']],
            json_decode($firstRequest['options']['body'], true),
        );
        self::assertContains('Authorization: Bearer site-key.ingest-secret', $firstRequest['options']['headers']);
    }

    public function testEveryRequestCarriesTheBoundedTimeouts(): void
    {
        $notifier = $this->createNotifier([]);

        $notifier->collect(CatalogSourceName::Products, 'cs_CZ', 'T-SHIRT-01');
        $notifier->collect(CatalogSourceName::Categories, 'en_US', 'T_SHIRTS');
        $notifier->flush();
        $notifier->notify(CatalogSourceName::Products, 'sk_SK', ['T-SHIRT-01']);

        self::assertCount(3, $this->capturedRequests);
        foreach ($this->capturedRequests as $request) {
            self::assertSame(2.0, $request['options']['timeout']);
            self::assertSame(5.0, $request['options']['max_duration']);
        }
    }

    public function testAnUnconfiguredNotifierNeverBuildsARequest(): void
    {
        $notifier = $this->createNotifier([], '');

        $notifier->collect(CatalogSourceName::Products, 'cs_CZ', 'T-SHIRT-01');
        $notifier->flush();
        $outcome = $notifier->notify(CatalogSourceName::Products, 'cs_CZ', ['T-SHIRT-01']);

        self::assertFalse($outcome->accepted);
        self::assertSame([], $this->capturedRequests);
    }

    public function testMissingConfigurationKeysAreNamed(): void
    {
        $notifier = $this->createNotifier([], '');

        self::assertSame(['backend_url'], $notifier->getMissingConfigurationKeys());
    }

    public function testCollectedIdsAreDroppedPastTheCeiling(): void
    {
        $notifier = $this->createNotifier([]);

        $ceiling = $notifier->getMaxPendingExternalIds();
        for ($index = 0; $index < $ceiling + 10; ++$index) {
            $notifier->collect(CatalogSourceName::Products, 'cs_CZ', 'CODE-' . $index);
        }
        $notifier->flush();

        $announcedIdCount = 0;
        foreach ($this->capturedRequests as $request) {
            $body = json_decode($request['options']['body'], true);
            $announcedIdCount += count($body['externalIds']);
        }

        self::assertSame($ceiling, $announcedIdCount);
    }

    public function testFlushChunksIdsAtFiveHundredPerRequest(): void
    {
        $notifier = $this->createNotifier([]);

        for ($index = 0; $index < 501; ++$index) {
            $notifier->collect(CatalogSourceName::Products, 'cs_CZ', 'CODE-' . $index);
        }
        $notifier->flush();

        self::assertCount(2, $this->capturedRequests);
        $firstBody = json_decode($this->capturedRequests[0]['options']['body'], true);
        $secondBody = json_decode($this->capturedRequests[1]['options']['body'], true);
        self::assertCount(500, $firstBody['externalIds']);
        self::assertCount(1, $secondBody['externalIds']);
    }

    public function testFlushClearsCollectedChanges(): void
    {
        $notifier = $this->createNotifier([]);

        $notifier->collect(CatalogSourceName::Products, 'cs_CZ', 'T-SHIRT-01');
        $notifier->flush();
        $notifier->flush();

        self::assertCount(1, $this->capturedRequests);
    }

    public function testThrottlingIsReportedWithRetryAfter(): void
    {
        $notifier = $this->createNotifier([
            new MockResponse('', ['http_code' => 429, 'response_headers' => ['Retry-After' => '300']]),
        ]);

        $outcome = $notifier->notify(CatalogSourceName::Products, 'cs_CZ', ['T-SHIRT-01']);

        self::assertFalse($outcome->accepted);
        self::assertTrue($outcome->isThrottled());
        self::assertSame(300, $outcome->retryAfterSeconds);
    }

    public function testTransportFailureIsSwallowed(): void
    {
        $notifier = $this->createNotifier([new MockResponse('', ['error' => 'connection refused'])]);

        $outcome = $notifier->notify(CatalogSourceName::Products, 'cs_CZ', ['T-SHIRT-01']);

        self::assertFalse($outcome->accepted);
        self::assertFalse($outcome->isThrottled());
    }

    public function testNonHttpsBackendIsRefusedOutsideDev(): void
    {
        $notifier = $this->createNotifier([], 'http://backend.example');

        $outcome = $notifier->notify(CatalogSourceName::Products, 'cs_CZ', ['T-SHIRT-01']);

        self::assertFalse($outcome->accepted);
        self::assertSame([], $this->capturedRequests);
    }

    public function testNonHttpsBackendIsAllowedInDev(): void
    {
        $notifier = $this->createNotifier([], 'http://backend.example', 'dev');

        $outcome = $notifier->notify(CatalogSourceName::Products, 'cs_CZ', ['T-SHIRT-01']);

        self::assertTrue($outcome->accepted);
        self::assertCount(1, $this->capturedRequests);
    }
}
