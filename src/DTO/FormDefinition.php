<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\DTO;

use Symfony\Contracts\Translation\TranslatorInterface;

readonly class FormDefinition implements \JsonSerializable
{
    public function __construct(
        public string $formId,
        public string $title,
        public array $fields,
        public string $submitLabel,
    ) {
    }

    public function translated(TranslatorInterface $translator, string $locale): self
    {
        return new self(
            $this->formId,
            $translator->trans($this->title, [], 'messages', $locale),
            array_map(
                fn (FormField $field): FormField => $field->translated($translator, $locale),
                $this->fields,
            ),
            $translator->trans($this->submitLabel, [], 'messages', $locale),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'formId' => $this->formId,
            'title' => $this->title,
            'fields' => array_map(
                fn (FormField $field): array => $field->jsonSerialize(),
                $this->fields,
            ),
            'submitLabel' => $this->submitLabel,
        ];
    }
}
