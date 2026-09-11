<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\DTO;

use Symfony\Component\Validator\Constraints as Assert;

readonly class SourceQuery
{
    /**
     * @param ?list<string> $ids
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Regex('/^[a-z]{2,3}(_[A-Z]{2})?$/')]
        public string $locale = '',

        #[Assert\Length(max: 255)]
        public ?string $channel = null,

        #[Assert\Length(max: 255)]
        public ?string $cursor = null,

        #[Assert\Count(max: 500)]
        #[Assert\Unique]
        #[Assert\All([
            new Assert\Type('string'),
            new Assert\NotBlank(),
            new Assert\Length(max: 255),
        ])]
        public ?array $ids = null,
    ) {
    }

    public function hasIds(): bool
    {
        return $this->ids !== null && $this->ids !== [];
    }
}
