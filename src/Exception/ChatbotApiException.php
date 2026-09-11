<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Exception;

use FluffyDiscord\SyliusChatbotBundle\Enum\ApiErrorCode;

abstract class ChatbotApiException extends \RuntimeException
{
    abstract public function getErrorCode(): ApiErrorCode;

    abstract public function getStatusCode(): int;

    public function getViolations(): array
    {
        return [];
    }
}
