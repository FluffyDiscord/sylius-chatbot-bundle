<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\DTO;

use Symfony\Component\Validator\Constraints as Assert;

readonly class ToolCallRequest
{
    public function __construct(
        public array $arguments,
        #[Assert\Valid]
        public ToolCallContext $context,
    ) {
    }
}
