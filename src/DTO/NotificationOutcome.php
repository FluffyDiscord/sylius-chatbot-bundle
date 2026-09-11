<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\DTO;

readonly class NotificationOutcome
{
    public function __construct(
        public bool $accepted,
        public ?int $retryAfterSeconds = null,
    ) {
    }

    public function isThrottled(): bool
    {
        return $this->retryAfterSeconds !== null;
    }
}
