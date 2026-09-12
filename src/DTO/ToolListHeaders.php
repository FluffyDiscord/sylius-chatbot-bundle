<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\DTO;

use Symfony\Component\Validator\Constraints as Assert;

readonly class ToolListHeaders
{
    public function __construct(
        #[Assert\Locale]
        public ?string $locale = null,
    ) {
    }
}
