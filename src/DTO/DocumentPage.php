<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\DTO;

readonly class DocumentPage implements \JsonSerializable
{
    public function __construct(
        public array $documents,
        public ?string $nextCursor = null,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'documents' => array_map(
                fn (SourceDocument $document): array => $document->jsonSerialize(),
                $this->documents,
            ),
            'nextCursor' => $this->nextCursor,
        ];
    }
}
