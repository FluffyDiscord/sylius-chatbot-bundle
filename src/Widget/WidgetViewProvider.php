<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Widget;

use FluffyDiscord\SyliusChatbotBundle\DTO\WidgetView;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class WidgetViewProvider
{
    /**
     * @param list<string> $channels
     */
    public function __construct(
        private ChannelContextInterface $channelContext,

        #[Autowire(param: 'fluffydiscord_sylius_chatbot.widget.enabled')]
        private bool $enabled,

        #[Autowire(param: 'fluffydiscord_sylius_chatbot.widget.site_key')]
        private string $siteKey,

        #[Autowire(param: 'fluffydiscord_sylius_chatbot.widget.cdn_url')]
        private string $cdnUrl,

        #[Autowire(param: 'fluffydiscord_sylius_chatbot.widget.channels')]
        private array $channels,

        #[Autowire(param: 'fluffydiscord_sylius_chatbot.backend_url')]
        private string $backendUrl,
    ) {
    }

    public function getView(): ?WidgetView
    {
        if (!$this->enabled) {
            return null;
        }

        $hasSiteKey = $this->siteKey !== '';
        $hasBackendUrl = $this->backendUrl !== '';
        if (!$hasSiteKey || !$hasBackendUrl) {
            return null;
        }

        $channelCode = $this->getCurrentChannelCode();
        if ($channelCode === null) {
            return null;
        }

        $isChannelAllowed = $this->channels === [] || in_array($channelCode, $this->channels, true);
        if (!$isChannelAllowed) {
            return null;
        }

        return new WidgetView($this->getScriptUrl(), $this->siteKey, $this->backendUrl);
    }

    private function getScriptUrl(): string
    {
        if ($this->cdnUrl !== '') {
            return $this->cdnUrl;
        }

        return $this->backendUrl . '/widget/v1/chat.js';
    }

    private function getCurrentChannelCode(): ?string
    {
        try {
            $channel = $this->channelContext->getChannel();
        } catch (ChannelNotFoundException) {
            return null;
        }

        return $channel->getCode();
    }
}
