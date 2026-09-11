<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Exception;

use FluffyDiscord\SyliusChatbotBundle\Enum\ApiErrorCode;
use Symfony\Component\HttpFoundation\Response;

class SourceNotFoundException extends ChatbotApiException
{
    public function __construct(string $name)
    {
        parent::__construct(sprintf('Data source "%s" is not registered.', $name));
    }

    public function getErrorCode(): ApiErrorCode
    {
        return ApiErrorCode::SourceNotFound;
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
