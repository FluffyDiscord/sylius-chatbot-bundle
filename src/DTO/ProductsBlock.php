<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\DTO;

readonly class ProductsBlock implements \JsonSerializable
{
    public function __construct(
        public array $items,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'type' => 'products',
            'items' => array_map(
                fn (ProductItem $item): array => $item->jsonSerialize(),
                $this->items,
            ),
        ];
    }
}
