<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Tool\DTO;

use Symfony\Component\Validator\Constraints as Assert;

readonly class OrderStatusArguments
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 32)]
        public string $orderNumber = '',
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email = '',
    ) {
    }
}
