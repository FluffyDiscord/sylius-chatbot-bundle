<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Tests\Unit\Widget;

use FluffyDiscord\SyliusChatbotBundle\Widget\WidgetViewProvider;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Core\Model\ChannelInterface;

class WidgetViewProviderTest extends TestCase
{
    public function testUsesTheConfiguredCdnUrl(): void
    {
        $provider = $this->createProvider(cdnUrl: 'https://zone.b-cdn.net/widget/v1/chat.js');

        $view = $provider->getView();

        self::assertNotNull($view);
        self::assertSame('https://zone.b-cdn.net/widget/v1/chat.js', $view->scriptUrl);
        self::assertSame('pk_site', $view->siteKey);
        self::assertSame('https://chatbot.example.com', $view->backendUrl);
    }

    public function testFallsBackToTheBackendServedScript(): void
    {
        $provider = $this->createProvider();

        $view = $provider->getView();

        self::assertNotNull($view);
        self::assertSame('https://chatbot.example.com/widget/v1/chat.js', $view->scriptUrl);
    }

    public function testDisabledWidgetRendersNothing(): void
    {
        $provider = $this->createProvider(enabled: false);

        self::assertNull($provider->getView());
    }

    public function testEmptySiteKeyRendersNothing(): void
    {
        $provider = $this->createProvider(siteKey: '');

        self::assertNull($provider->getView());
    }

    public function testEmptyBackendUrlRendersNothing(): void
    {
        $provider = $this->createProvider(backendUrl: '');

        self::assertNull($provider->getView());
    }

    public function testChannelOutsideTheAllowListRendersNothing(): void
    {
        $provider = $this->createProvider(channels: ['other-channel']);

        self::assertNull($provider->getView());
    }

    public function testChannelInsideTheAllowListRenders(): void
    {
        $provider = $this->createProvider(channels: ['other-channel', 'main-channel']);

        self::assertNotNull($provider->getView());
    }

    public function testUnresolvableChannelRendersNothing(): void
    {
        $channelContext = $this->createStub(ChannelContextInterface::class);
        $channelContext->method('getChannel')->willThrowException(new ChannelNotFoundException());

        $provider = new WidgetViewProvider($channelContext, true, 'pk_site', '', [], 'https://chatbot.example.com');

        self::assertNull($provider->getView());
    }

    /**
     * @param list<string> $channels
     */
    private function createProvider(
        bool $enabled = true,
        string $siteKey = 'pk_site',
        string $cdnUrl = '',
        array $channels = [],
        string $backendUrl = 'https://chatbot.example.com',
    ): WidgetViewProvider {
        $channel = $this->createStub(ChannelInterface::class);
        $channel->method('getCode')->willReturn('main-channel');

        $channelContext = $this->createStub(ChannelContextInterface::class);
        $channelContext->method('getChannel')->willReturn($channel);

        return new WidgetViewProvider($channelContext, $enabled, $siteKey, $cdnUrl, $channels, $backendUrl);
    }
}
