<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\DTO;

readonly class ChoiceItem implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public string $label,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
        ];
    }
}
