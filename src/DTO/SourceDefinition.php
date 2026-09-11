<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\DTO;

use Symfony\Contracts\Translation\TranslatorInterface;

readonly class SourceDefinition implements \JsonSerializable
{
    public function __construct(
        public string $name,
        public string $description,
        public ?array $locales = null,
    ) {
    }

    public function withLocales(array $locales): self
    {
        return new self($this->name, $this->description, $locales);
    }

    public function translated(TranslatorInterface $translator, string $locale): self
    {
        return new self(
            $this->name,
            $translator->trans($this->description, [], 'messages', $locale),
            $this->locales,
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'locales' => $this->locales ?? [],
        ];
    }
}
