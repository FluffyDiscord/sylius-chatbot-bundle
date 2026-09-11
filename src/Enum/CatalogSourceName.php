<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Enum;

enum CatalogSourceName: string
{
    case Products = 'products';
    case Categories = 'categories';
}
