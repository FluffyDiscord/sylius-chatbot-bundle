<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Tests\Unit\Fixtures;

use Symfony\Component\Validator\Constraints as Assert;

readonly class NullableArguments
{
    public function __construct(
        #[Assert\NotBlank]
        public string $query = '',
        public ?string $note = null,
        #[Assert\Choice(choices: ['relevance', 'price'])]
        public string $sort = 'relevance',
    ) {
    }
}
