<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Tests\Unit\Command;

use Doctrine\Common\Collections\ArrayCollection;
use FluffyDiscord\SyliusChatbotBundle\Channel\ChannelResolver;
use FluffyDiscord\SyliusChatbotBundle\Command\NotifyAllCommand;
use FluffyDiscord\SyliusChatbotBundle\Locale\ShopLocaleResolver;
use FluffyDiscord\SyliusChatbotBundle\Registry\DataSourceRegistry;
use FluffyDiscord\SyliusChatbotBundle\Tests\Unit\Fixtures\RecordingCatalogChangeNotifier;
use FluffyDiscord\SyliusChatbotBundle\Tests\Unit\Fixtures\RecordingDataSource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\Locale;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ServiceLocator;

class NotifyAllCommandTest extends TestCase
{
    #[DataProvider('provideRequestedLocales')]
    public function testTheRequestedLocaleIsAnnouncedInTheShopSpelling(?string $requested, array $expected): void
    {
        $source = new RecordingDataSource('products');
        $command = $this->createCommand($source, ['cs_CZ', 'en_US']);

        $exitCode = $command->__invoke($this->createStyle(), 'products', $requested);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertSame($expected, $source->queriedLocales);
    }

    /**
     * @return iterable<string, array{?string, list<string>}>
     */
    public static function provideRequestedLocales(): iterable
    {
        yield 'every channel locale' => [null, ['cs_CZ', 'en_US']];
        yield 'empty option' => ['', ['cs_CZ', 'en_US']];
        yield 'language only' => ['cs', ['cs_CZ']];
        yield 'hyphen separated' => ['cs-CZ', ['cs_CZ']];
        yield 'lower cased region' => ['cs_cz', ['cs_CZ']];
    }

    public function testAnUnservedLocaleAnnouncesNothing(): void
    {
        $source = new RecordingDataSource('products');
        $command = $this->createCommand($source, ['cs_CZ']);
        $output = new BufferedOutput();

        $exitCode = $command->__invoke($this->createStyle($output), 'products', 'de_AT');

        self::assertSame(Command::INVALID, $exitCode);
        self::assertSame([], $source->queriedLocales);
        self::assertStringContainsString('Locale "de_AT" is not served.', $output->fetch());
    }

    public function testASourceWithoutOwnLocalesFallsBackToTheChannel(): void
    {
        $source = new RecordingDataSource('products', []);
        $command = $this->createCommand($source, ['cs_CZ', 'en_US']);

        $exitCode = $command->__invoke($this->createStyle(), 'products');

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertSame(['cs_CZ', 'en_US'], $source->queriedLocales);
    }

    public function testTheSourceLocalesWinOverTheChannelOnes(): void
    {
        $source = new RecordingDataSource('products', ['en_US']);
        $command = $this->createCommand($source, ['cs_CZ', 'en_US']);

        $exitCode = $command->__invoke($this->createStyle(), 'products');

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertSame(['en_US'], $source->queriedLocales);
    }

    /**
     * @param list<string> $channelLocales
     */
    private function createCommand(RecordingDataSource $source, array $channelLocales): NotifyAllCommand
    {
        $registry = new DataSourceRegistry(new ServiceLocator([
            'products' => fn (): RecordingDataSource => $source,
        ]));
        $channelResolver = $this->createChannelResolver($channelLocales);

        return new NotifyAllCommand(
            $registry,
            new RecordingCatalogChangeNotifier(),
            $channelResolver,
            new ShopLocaleResolver($channelResolver),
        );
    }

    /**
     * @param list<string> $channelLocales
     */
    private function createChannelResolver(array $channelLocales): ChannelResolver
    {
        $locales = [];

        foreach ($channelLocales as $code) {
            $locale = new Locale();
            $locale->setCode($code);
            $locales[] = $locale;
        }

        $channel = $this->createStub(ChannelInterface::class);
        $channel->method('getLocales')->willReturn(new ArrayCollection($locales));

        $channelResolver = $this->createStub(ChannelResolver::class);
        $channelResolver->method('getChannel')->willReturn($channel);

        return $channelResolver;
    }

    private function createStyle(?BufferedOutput $output = null): SymfonyStyle
    {
        return new SymfonyStyle(new ArrayInput([]), $output ?? new BufferedOutput());
    }
}
