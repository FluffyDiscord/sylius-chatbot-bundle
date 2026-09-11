<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\DTO;

use FluffyDiscord\SyliusChatbotBundle\Enum\FormFieldType;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class FormField implements \JsonSerializable
{
    public function __construct(
        public string $name,
        public string $label,
        public FormFieldType $type,
        public bool $required,
        public ?array $options = null,
    ) {
    }

    public function translated(TranslatorInterface $translator, string $locale): self
    {
        $translatedOptions = null;
        if ($this->options !== null) {
            $translatedOptions = array_map(
                fn (FormFieldOption $option): FormFieldOption => $option->translated($translator, $locale),
                $this->options,
            );
        }

        return new self(
            $this->name,
            $translator->trans($this->label, [], 'messages', $locale),
            $this->type,
            $this->required,
            $translatedOptions,
        );
    }

    public function jsonSerialize(): array
    {
        $serialized = [
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->type->value,
            'required' => $this->required,
        ];

        if ($this->options !== null) {
            $serialized['options'] = array_map(
                fn (FormFieldOption $option): array => $option->jsonSerialize(),
                $this->options,
            );
        }

        return $serialized;
    }
}
