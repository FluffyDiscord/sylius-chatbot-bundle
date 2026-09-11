<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Exception;

use FluffyDiscord\SyliusChatbotBundle\Enum\ApiErrorCode;
use Symfony\Component\HttpFoundation\Response;

class InvalidLocaleException extends ChatbotApiException
{
    public function __construct(string $locale)
    {
        parent::__construct(sprintf('Locale "%s" is not served by this source.', $locale));
    }

    public function getErrorCode(): ApiErrorCode
    {
        return ApiErrorCode::InvalidLocale;
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
