<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Tests\Unit\Template;

use FluffyDiscord\SyliusChatbotBundle\Tests\Unit\Fixtures\AppVariableDouble;
use FluffyDiscord\SyliusChatbotBundle\Tests\Unit\Fixtures\HookableMetadataDouble;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class WidgetTemplateTest extends TestCase
{
    public function testTheWidgetRendersFromTheTwigHookMetadata(): void
    {
        $rendered = $this->render(['hookable_metadata' => new HookableMetadataDouble($this->getWidgetContext())]);

        self::assertSame($this->getExpectedMarkup(), $rendered);
    }

    public function testTheWidgetRendersFromTemplateBlockVariables(): void
    {
        $rendered = $this->render($this->getWidgetContext());

        self::assertSame($this->getExpectedMarkup(), $rendered);
    }

    /**
     * @return array<string, string>
     */
    private function getWidgetContext(): array
    {
        return [
            'backend_url' => 'https://backend.test',
            'site_key' => 'site-key',
            'widget_cdn_url' => 'https://cdn.test/chat.js',
        ];
    }

    private function getExpectedMarkup(): string
    {
        return '<script src="https://cdn.test/chat.js" defer></script>' . "\n"
            . '<ai-chat-widget site-key="site-key" locale="cs_CZ" backend-url="https://backend.test"></ai-chat-widget>';
    }

    /**
     * @param array<string, mixed> $context
     */
    private function render(array $context): string
    {
        $twig = new Environment(new FilesystemLoader(__DIR__ . '/../../../templates'));
        $twig->addGlobal('app', new AppVariableDouble('cs_CZ'));

        return trim($twig->render('shop/widget.html.twig', $context));
    }
}
