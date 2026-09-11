<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Tests\Unit\DependencyInjection;

use FluffyDiscord\SyliusChatbotBundle\FluffyDiscordSyliusChatbotBundle;
use FluffyDiscord\SyliusChatbotBundle\Tests\Unit\Fixtures\TwigHooksExtension;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class WidgetHookPrependTest extends TestCase
{
    public function testRegistersTheHookableWithoutAnyBuildTimeContext(): void
    {
        $container = new ContainerBuilder();
        $container->registerExtension(new TwigHooksExtension());

        $this->prepend($container);

        $prependedConfigs = $container->getExtensionConfig('sylius_twig_hooks');
        self::assertSame([[
            'hooks' => [
                'sylius_shop.base#javascripts' => [
                    'fluffydiscord_chatbot_widget' => [
                        'template' => '@FluffyDiscordSyliusChatbot/shop/widget.html.twig',
                        'priority' => 0,
                    ],
                ],
            ],
        ]], $prependedConfigs);
    }

    #[IgnoreDeprecations]
    public function testShopWithoutTwigHooksIsToldToIncludeTheWidgetManually(): void
    {
        $container = new ContainerBuilder();

        $this->expectUserDeprecationMessage('Since fluffydiscord/sylius-chatbot-bundle 0.3: Sylius 1.x has no twig hooks, so the chatbot widget must be included manually with {{ fluffydiscord_chatbot_widget() }}. Upgrade to Sylius 2.x, where the widget is injected automatically, and remove that call from your layout.');

        $this->prepend($container);

        self::assertSame([], $container->getExtensionConfig('sylius_twig_hooks'));
    }

    private function prepend(ContainerBuilder $container): void
    {
        $instanceof = [];
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__));
        $configurator = new ContainerConfigurator($container, $loader, $instanceof, __FILE__, __FILE__);

        (new FluffyDiscordSyliusChatbotBundle())->prependExtension($configurator, $container);
    }
}
