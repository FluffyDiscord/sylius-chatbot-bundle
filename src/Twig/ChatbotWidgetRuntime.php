<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Twig;

use FluffyDiscord\SyliusChatbotBundle\Widget\WidgetViewProvider;
use Symfony\Contracts\Service\ResetInterface;
use Twig\Environment;
use Twig\Extension\RuntimeExtensionInterface;

class ChatbotWidgetRuntime implements RuntimeExtensionInterface, ResetInterface
{
    private bool $hasRendered = false;

    public function __construct(
        private readonly WidgetViewProvider $widgetViewProvider,
    ) {
    }

    public function renderWidget(Environment $environment): string
    {
        if ($this->hasRendered) {
            return '';
        }

        $widget = $this->widgetViewProvider->getView();
        if ($widget === null) {
            return '';
        }

        $this->hasRendered = true;

        return $environment->render('@FluffyDiscordSyliusChatbot/shop/_widget.html.twig', ['widget' => $widget]);
    }

    public function reset(): void
    {
        $this->hasRendered = false;
    }
}
