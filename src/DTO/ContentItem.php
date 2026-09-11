<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\DTO;

readonly class ContentItem implements \JsonSerializable
{
    public function __construct(
        public string $text,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'type' => 'text',
            'text' => $this->text,
        ];
    }
}
