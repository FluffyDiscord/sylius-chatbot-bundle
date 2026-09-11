<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\DTO;

use FluffyDiscord\SyliusChatbotBundle\Enum\DocumentKind;

readonly class SourceDocument implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public string $url,
        public string $title,
        public string $text,
        public DocumentKind $kind,
        public array $metadata,
        public string $updatedAt,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'title' => $this->title,
            'text' => $this->text,
            'kind' => $this->kind->value,
            'metadata' => $this->metadata,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
