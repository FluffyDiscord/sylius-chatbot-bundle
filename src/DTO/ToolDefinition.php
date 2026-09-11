<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\DTO;

use Symfony\Contracts\Translation\TranslatorInterface;

readonly class ToolDefinition implements \JsonSerializable
{
    public function __construct(
        public string $name,
        public string $description,
        public ?ToolUiHints $ui = null,
        public ?array $inputSchema = null,
    ) {
    }

    public function withInputSchema(array $inputSchema): self
    {
        return new self($this->name, $this->description, $this->ui, $inputSchema);
    }

    public function translated(TranslatorInterface $translator, string $locale): self
    {
        return new self(
            $this->name,
            $translator->trans($this->description, [], 'messages', $locale),
            $this->ui?->translated($translator, $locale),
            $this->inputSchema,
        );
    }

    public function jsonSerialize(): array
    {
        $serialized = [
            'name' => $this->name,
            'description' => $this->description,
            'inputSchema' => $this->inputSchema ?? ['type' => 'object', 'additionalProperties' => false],
        ];

        if ($this->ui !== null) {
            $serialized['ui'] = $this->ui->jsonSerialize();
        }

        return $serialized;
    }
}
