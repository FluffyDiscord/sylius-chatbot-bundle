<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Exception;

use FluffyDiscord\SyliusChatbotBundle\Enum\ApiErrorCode;
use Symfony\Component\HttpFoundation\Response;

class InvalidChannelException extends ChatbotApiException
{
    public function __construct(?string $channelCode = null, ?\Throwable $previous = null)
    {
        $message = 'No channel could be resolved for the request.';
        if ($channelCode !== null && $channelCode !== '') {
            $message = sprintf('Channel "%s" does not exist.', $channelCode);
        }

        parent::__construct($message, 0, $previous);
    }

    public function getErrorCode(): ApiErrorCode
    {
        return ApiErrorCode::InvalidChannel;
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
