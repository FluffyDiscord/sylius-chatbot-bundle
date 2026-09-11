<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Exception;

use FluffyDiscord\SyliusChatbotBundle\Enum\ApiErrorCode;
use Symfony\Component\HttpFoundation\Response;

class ArgumentsValidationException extends ChatbotApiException
{
    public function __construct(
        private readonly array $violations,
    ) {
        parent::__construct('Tool arguments are invalid.');
    }

    public function getErrorCode(): ApiErrorCode
    {
        return ApiErrorCode::ValidationFailed;
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_UNPROCESSABLE_ENTITY;
    }

    public function getViolations(): array
    {
        return $this->violations;
    }
}
