<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Exception;

use FluffyDiscord\SyliusChatbotBundle\Enum\ApiErrorCode;
use Symfony\Component\HttpFoundation\Response;

class ForbiddenUserAgentException extends ChatbotApiException
{
    public function __construct(string $userAgent)
    {
        $loggableUserAgent = mb_substr($userAgent, 0, $this->getMaxLoggedUserAgentLength());

        parent::__construct(sprintf('User agent "%s" is not the chatbot backend.', $loggableUserAgent));
    }

    private function getMaxLoggedUserAgentLength(): int
    {
        return 64;
    }

    public function getErrorCode(): ApiErrorCode
    {
        return ApiErrorCode::ForbiddenUserAgent;
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_FORBIDDEN;
    }
}
