<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\DTO;

readonly class WidgetView
{
    public function __construct(
        public string $scriptUrl,
        public string $siteKey,
        public string $backendUrl,
    ) {
    }
}
