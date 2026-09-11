<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\DTO;

readonly class ToolResult implements \JsonSerializable
{
    public function __construct(
        public array $content,
        public array $blocks = [],
        public bool $isError = false,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'content' => array_map(
                fn (ContentItem $item): array => $item->jsonSerialize(),
                $this->content,
            ),
            'blocks' => array_map(
                fn (\JsonSerializable $block): array => $block->jsonSerialize(),
                $this->blocks,
            ),
            'isError' => $this->isError,
        ];
    }
}
