<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\DTO;

use Symfony\Component\Validator\Constraints as Assert;

readonly class ToolCallContext
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $conversationId,
        #[Assert\NotBlank]
        #[Assert\Regex('/^[a-z]{2,3}(_[A-Z]{2})?$/')]
        public string $locale,
        public ?string $channelCode = null,
    ) {
    }
}
