<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\DTO;

use Symfony\Contracts\Translation\TranslatorInterface;

readonly class FormFieldOption implements \JsonSerializable
{
    public function __construct(
        public string $value,
        public string $label,
    ) {
    }

    public function translated(TranslatorInterface $translator, string $locale): self
    {
        return new self($this->value, $translator->trans($this->label, [], 'messages', $locale));
    }

    public function jsonSerialize(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label,
        ];
    }
}
