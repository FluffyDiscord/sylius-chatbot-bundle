<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Tests\Unit;

use FluffyDiscord\SyliusChatbotBundle\FluffyDiscordSyliusChatbotBundle;
use FluffyDiscord\SyliusChatbotBundle\Tests\Unit\Fixtures\NamedExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class FluffyDiscordSyliusChatbotBundleTest extends TestCase
{
    public function testTheWidgetIsRegisteredAsATwigHookWhenTheShopHasThem(): void
    {
        $container = $this->createContainer(['sylius_twig_hooks', 'sylius_ui']);

        $this->prependExtension($container);

        $hooks = $container->getExtensionConfig('sylius_twig_hooks');
        $widget = $hooks[0]['hooks']['sylius_shop.base#javascripts']['fluffydiscord_chatbot_widget'];

        self::assertSame('@FluffyDiscordSyliusChatbot/shop/widget.html.twig', $widget['template']);
        self::assertSame($this->getExpectedWidgetContext(), $widget['context']);
        self::assertSame([], $container->getExtensionConfig('sylius_ui'));
    }

    public function testTheWidgetIsRegisteredAsATemplateBlockOnSyliusWithoutTwigHooks(): void
    {
        require_once __DIR__ . '/Fixtures/sylius_1_template_block.php';

        $container = $this->createContainer(['sylius_ui']);

        $this->prependExtension($container);

        $events = $container->getExtensionConfig('sylius_ui');
        $widget = $events[0]['events']['sylius.shop.layout.javascripts']['blocks']['fluffydiscord_chatbot_widget'];

        self::assertSame('@FluffyDiscordSyliusChatbot/shop/widget.html.twig', $widget['template']);
        self::assertSame($this->getExpectedWidgetContext(), $widget['context']);
        self::assertSame([], $container->getExtensionConfig('sylius_twig_hooks'));
    }

    public function testADisabledWidgetIsRegisteredNowhere(): void
    {
        $container = $this->createContainer(['sylius_ui'], ['enabled' => false]);

        $this->prependExtension($container);

        self::assertSame([], $container->getExtensionConfig('sylius_ui'));
        self::assertSame([], $container->getExtensionConfig('sylius_twig_hooks'));
    }

    /**
     * @return array<string, string>
     */
    private function getExpectedWidgetContext(): array
    {
        return [
            'backend_url' => 'https://backend.test',
            'site_key' => 'site-key',
            'widget_cdn_url' => 'https://backend.test/widget/v1/chat.js',
        ];
    }

    /**
     * @param list<string>         $extensionAliases
     * @param array<string, mixed> $widgetConfig
     */
    private function createContainer(array $extensionAliases, array $widgetConfig = []): ContainerBuilder
    {
        $container = new ContainerBuilder();

        foreach ($extensionAliases as $alias) {
            $container->registerExtension(new NamedExtension($alias));
        }

        $container->prependExtensionConfig('fluffy_discord_sylius_chatbot', [
            'backend_url' => 'https://backend.test',
            'widget' => $widgetConfig + ['site_key' => 'site-key'],
        ]);

        return $container;
    }

    private function prependExtension(ContainerBuilder $container): void
    {
        $instanceof = [];
        $configurator = new ContainerConfigurator(
            $container,
            new PhpFileLoader($container, new FileLocator(__DIR__)),
            $instanceof,
            __FILE__,
            __FILE__,
        );

        (new FluffyDiscordSyliusChatbotBundle())->prependExtension($configurator, $container);
    }
}
