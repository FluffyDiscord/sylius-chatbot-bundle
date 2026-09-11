<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ChatbotWidgetExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'fluffydiscord_chatbot_widget',
                [ChatbotWidgetRuntime::class, 'renderWidget'],
                ['needs_environment' => true, 'is_safe' => ['html']],
            ),
        ];
    }
}
