<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Product;

use FluffyDiscord\SyliusChatbotBundle\Contract\ProductIndexabilityInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

readonly class ProductIndexability implements ProductIndexabilityInterface
{
    public function __construct(
        private VariantPriceResolver $variantPriceResolver,
    ) {
    }

    public function isIndexable(ProductVariantInterface $variant, ChannelInterface $channel): bool
    {
        $isVariantEnabled = $variant->isEnabled();
        if (!$isVariantEnabled) {
            return false;
        }

        $product = $variant->getProduct();
        if (!$product instanceof ProductInterface) {
            return false;
        }

        $isProductEnabled = $product->isEnabled();
        if (!$isProductEnabled) {
            return false;
        }

        $isInChannel = $product->hasChannel($channel);
        if (!$isInChannel) {
            return false;
        }

        $priceMinor = $this->variantPriceResolver->findPriceMinor($variant, $channel);

        return $priceMinor !== null;
    }
}
