<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Tests\Unit\Command;

use FluffyDiscord\SyliusChatbotBundle\Channel\ChannelResolver;
use FluffyDiscord\SyliusChatbotBundle\Command\NotifyAllCommand;
use FluffyDiscord\SyliusChatbotBundle\Contract\ChatbotDataSourceInterface;
use FluffyDiscord\SyliusChatbotBundle\DTO\DocumentPage;
use FluffyDiscord\SyliusChatbotBundle\DTO\NotificationOutcome;
use FluffyDiscord\SyliusChatbotBundle\DTO\SourceDefinition;
use FluffyDiscord\SyliusChatbotBundle\DTO\SourceDocument;
use FluffyDiscord\SyliusChatbotBundle\Enum\CatalogSourceName;
use FluffyDiscord\SyliusChatbotBundle\Enum\DocumentKind;
use FluffyDiscord\SyliusChatbotBundle\Ingest\CatalogChangeNotifier;
use FluffyDiscord\SyliusChatbotBundle\Registry\DataSourceRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class NotifyAllCommandTest extends TestCase
{
    public function testUnknownSourceIsRejected(): void
    {
        $tester = new CommandTester($this->createCommand());

        $exitCode = $tester->execute(['--source' => 'unknown']);

        self::assertSame(Command::INVALID, $exitCode);
        self::assertStringContainsString('unknown', $tester->getDisplay());
    }

    public function testUnconfiguredNotifierStopsBeforeAnyChannelIsResolved(): void
    {
        $notifier = $this->createStub(CatalogChangeNotifier::class);
        $notifier->method('getMissingConfigurationKeys')->willReturn(['backend_url']);

        $channelResolver = $this->createMock(ChannelResolver::class);
        $channelResolver->expects(self::never())->method('setOverrideCode');

        $tester = new CommandTester($this->createCommand($notifier, $channelResolver));

        $exitCode = $tester->execute(['--source' => 'categories', '--channel' => 'main-channel']);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('backend_url', $tester->getDisplay());
    }

    public function testAnnouncesTheSourceDocumentsOfTheRequestedChannel(): void
    {
        $notifier = $this->createMock(CatalogChangeNotifier::class);
        $notifier->method('getMissingConfigurationKeys')->willReturn([]);
        $notifier->method('getMaxExternalIdsPerRequest')->willReturn(500);
        $notifier->expects(self::once())
            ->method('notify')
            ->with(CatalogSourceName::Categories, 'cs_CZ', ['taxon-1', 'taxon-2'])
            ->willReturn(new NotificationOutcome(accepted: true));

        $channelResolver = $this->createMock(ChannelResolver::class);
        $channelResolver->expects(self::once())->method('setOverrideCode')->with('main-channel');

        $tester = new CommandTester($this->createCommand($notifier, $channelResolver, $this->createCategoriesSource()));

        $exitCode = $tester->execute(['--source' => 'categories', '--locale' => 'cs_CZ', '--channel' => 'main-channel']);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('2 ids announced', $tester->getDisplay());
    }

    private function createCategoriesSource(): ChatbotDataSourceInterface
    {
        $documents = [
            new SourceDocument('taxon-1', 'https://shop.example/t1', 'T1', 'text', DocumentKind::Category, [], '2026-09-01T00:00:00+00:00'),
            new SourceDocument('taxon-2', 'https://shop.example/t2', 'T2', 'text', DocumentKind::Category, [], '2026-09-01T00:00:00+00:00'),
        ];

        $source = $this->createStub(ChatbotDataSourceInterface::class);
        $source->method('getDefinition')->willReturn(new SourceDefinition('categories', 'Categories', ['cs_CZ']));
        $source->method('getDocuments')->willReturn(new DocumentPage($documents, null));

        return $source;
    }

    private function createCommand(
        ?CatalogChangeNotifier $notifier = null,
        ?ChannelResolver $channelResolver = null,
        ?ChatbotDataSourceInterface $dataSource = null,
    ): NotifyAllCommand {
        $registry = $this->createStub(DataSourceRegistry::class);
        $registry->method('get')->willReturn($dataSource);

        return new NotifyAllCommand(
            $registry,
            $notifier ?? $this->createStub(CatalogChangeNotifier::class),
            $channelResolver ?? $this->createStub(ChannelResolver::class),
        );
    }
}
