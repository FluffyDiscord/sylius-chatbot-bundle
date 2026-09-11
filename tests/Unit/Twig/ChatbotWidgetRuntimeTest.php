<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Tests\Unit\Twig;

use FluffyDiscord\SyliusChatbotBundle\DTO\WidgetView;
use FluffyDiscord\SyliusChatbotBundle\Twig\ChatbotWidgetExtension;
use FluffyDiscord\SyliusChatbotBundle\Twig\ChatbotWidgetRuntime;
use FluffyDiscord\SyliusChatbotBundle\Widget\WidgetViewProvider;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

class ChatbotWidgetRuntimeTest extends TestCase
{
    public function testRendersTheWidgetMarkupOnce(): void
    {
        $runtime = new ChatbotWidgetRuntime($this->createProvider(new WidgetView(
            'https://zone.b-cdn.net/widget/v1/chat.js',
            'pk_site',
            'https://chatbot.example.com',
        )));
        $environment = $this->createEnvironment($runtime);

        $markup = $environment->render('page.html.twig');

        self::assertStringContainsString('<script src="https://zone.b-cdn.net/widget/v1/chat.js" defer></script>', $markup);
        self::assertStringContainsString('site-key="pk_site"', $markup);
        self::assertStringContainsString('locale="cs_CZ"', $markup);
        self::assertStringContainsString('backend-url="https://chatbot.example.com"', $markup);
        self::assertSame(1, substr_count($markup, '<ai-chat-widget'));
    }

    public function testSecondCallInTheSameRequestRendersNothing(): void
    {
        $runtime = new ChatbotWidgetRuntime($this->createProvider(new WidgetView('https://cdn/chat.js', 'pk_site', 'https://backend')));
        $environment = $this->createEnvironment($runtime);

        $markup = $environment->render('twice.html.twig');
        self::assertSame(1, substr_count($markup, '<ai-chat-widget'));

        $runtime->reset();
        $markupAfterReset = $environment->render('page.html.twig');
        self::assertStringContainsString('<ai-chat-widget', $markupAfterReset);
    }

    public function testNoViewRendersNothing(): void
    {
        $runtime = new ChatbotWidgetRuntime($this->createProvider(null));
        $environment = $this->createEnvironment($runtime);

        self::assertSame('', $environment->render('page.html.twig'));
    }

    private function createProvider(?WidgetView $view): WidgetViewProvider
    {
        $provider = $this->createStub(WidgetViewProvider::class);
        $provider->method('getView')->willReturn($view);

        return $provider;
    }

    private function createEnvironment(ChatbotWidgetRuntime $runtime): Environment
    {
        $loader = new ArrayLoader([
            'page.html.twig' => '{{ fluffydiscord_chatbot_widget() }}',
            'twice.html.twig' => '{{ fluffydiscord_chatbot_widget() }}{{ fluffydiscord_chatbot_widget() }}',
            '@FluffyDiscordSyliusChatbot/shop/_widget.html.twig' => file_get_contents(__DIR__ . '/../../../templates/shop/_widget.html.twig'),
        ]);

        $environment = new Environment($loader);
        $environment->addExtension(new ChatbotWidgetExtension());
        $environment->addGlobal('app', new class {
            public string $locale = 'cs_CZ';
        });
        $environment->addRuntimeLoader(new class ($runtime) implements RuntimeLoaderInterface {
            public function __construct(private readonly ChatbotWidgetRuntime $runtime)
            {
            }

            public function load(string $class): ?ChatbotWidgetRuntime
            {
                return $class === ChatbotWidgetRuntime::class ? $this->runtime : null;
            }
        });

        return $environment;
    }
}
