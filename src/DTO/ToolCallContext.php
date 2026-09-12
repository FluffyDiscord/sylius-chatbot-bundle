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
        #[Assert\Locale]
        public string $locale,
        public ?string $channelCode = null,
    ) {
    }

    public function withLocale(string $locale): self
    {
        return new self($this->conversationId, $locale, $this->channelCode);
    }
}
