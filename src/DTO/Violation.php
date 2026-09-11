<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\DTO;

readonly class Violation implements \JsonSerializable
{
    public function __construct(
        public string $path,
        public string $message,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'path' => $this->path,
            'message' => $this->message,
        ];
    }
}
