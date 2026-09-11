<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\DTO;

readonly class ProductItem implements \JsonSerializable
{
    public function __construct(
        public string  $code,
        public string  $productCode,
        public string  $name,
        public string  $url,
        public int     $priceMinor,
        public string  $currency,
        public ?string $imageUrl,
        public bool    $inStock,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'code' => $this->code,
            'productCode' => $this->productCode,
            'name' => $this->name,
            'url' => $this->url,
            'priceMinor' => $this->priceMinor,
            'currency' => $this->currency,
            'imageUrl' => $this->imageUrl,
            'inStock' => $this->inStock,
        ];
    }
}
