<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Exception;

use FluffyDiscord\SyliusChatbotBundle\Enum\ApiErrorCode;
use Symfony\Component\HttpFoundation\Response;

class InvalidCursorException extends ChatbotApiException
{
    public function __construct(string $cursor)
    {
        parent::__construct(sprintf('Cursor "%s" cannot be decoded.', $cursor));
    }

    public function getErrorCode(): ApiErrorCode
    {
        return ApiErrorCode::InvalidCursor;
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
