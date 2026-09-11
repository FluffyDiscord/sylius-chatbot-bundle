<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\DTO;

use Symfony\Component\Validator\Constraints as Assert;

readonly class ToolListHeaders
{
    public function __construct(
        #[Assert\Regex('/^[a-z]{2,3}(_[A-Z]{2})?$/')]
        public ?string $locale = null,
    ) {
    }
}
