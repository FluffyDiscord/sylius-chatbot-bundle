<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle;

use BitBag\SyliusCmsPlugin\Entity\Page as BitBagPage;
use MonsieurBiz\SyliusCmsPagePlugin\Entity\Page;
use FluffyDiscord\SyliusChatbotBundle\DataSource\BitBagCmsPagesDataSource;
use FluffyDiscord\SyliusChatbotBundle\DataSource\CmsPagesDataSource;
use FluffyDiscord\SyliusChatbotBundle\DependencyInjection\Compiler\ChatbotDefinitionNamePass;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

class FluffyDiscordSyliusChatbotBundle extends AbstractBundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new ChatbotDefinitionNamePass());
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('api_secret')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('backend_url')->defaultValue('')->end()
                ->scalarNode('ingest_secret')->defaultValue('')->end()
                ->arrayNode('widget')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->end()
                        ->scalarNode('backend_url')
                            ->defaultValue('')
                            ->setDeprecated(
                                'fluffydiscord/sylius-chatbot-bundle',
                                '0.2',
                                'The "%path%.%node%" option is deprecated, configure "fluffy_discord_sylius_chatbot.backend_url" instead.',
                            )
                        ->end()
                        ->scalarNode('site_key')->defaultValue('')->end()
                        ->scalarNode('cdn_url')->defaultValue('')->end()
                        ->arrayNode('channels')
                            ->scalarPrototype()->end()
                            ->defaultValue([])
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    public function prependExtension(ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $hasTwigHooks = $container->hasExtension('sylius_twig_hooks');
        if (!$hasTwigHooks) {
            trigger_deprecation(
                'fluffydiscord/sylius-chatbot-bundle',
                '0.3',
                'Sylius 1.x has no twig hooks, so the chatbot widget must be included manually with {{ fluffydiscord_chatbot_widget() }}. Upgrade to Sylius 2.x, where the widget is injected automatically, and remove that call from your layout.',
            );

            return;
        }

        $container->prependExtensionConfig('sylius_twig_hooks', [
            'hooks' => [
                'sylius_shop.base#javascripts' => [
                    'fluffydiscord_chatbot_widget' => [
                        'template' => '@FluffyDiscordSyliusChatbot/shop/widget.html.twig',
                        'priority' => 0,
                    ],
                ],
            ],
        ]);
    }

    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $backendUrl = $this->resolveBackendUrl($config);

        $configurator->parameters()
            ->set('fluffydiscord_sylius_chatbot.api_secret', $config['api_secret'])
            ->set('fluffydiscord_sylius_chatbot.backend_url', $backendUrl)
            ->set('fluffydiscord_sylius_chatbot.ingest_secret', $config['ingest_secret'])
            ->set('fluffydiscord_sylius_chatbot.widget.enabled', $config['widget']['enabled'])
            ->set('fluffydiscord_sylius_chatbot.widget.backend_url', $backendUrl)
            ->set('fluffydiscord_sylius_chatbot.widget.site_key', $config['widget']['site_key'])
            ->set('fluffydiscord_sylius_chatbot.widget.cdn_url', $config['widget']['cdn_url'])
            ->set('fluffydiscord_sylius_chatbot.widget.channels', $config['widget']['channels']);

        $configurator->import(__DIR__ . '/../config/services.php');

        $isCmsPagePluginInstalled = class_exists(Page::class);
        if ($isCmsPagePluginInstalled) {
            $configurator->services()
                ->set(CmsPagesDataSource::class)
                ->autowire()
                ->autoconfigure()
                ->arg('$pageRepository', service('monsieurbiz_cms_page.repository.page'));
        }

        $isBitBagCmsPluginInstalled = class_exists(BitBagPage::class);
        if ($isBitBagCmsPluginInstalled) {
            $configurator->services()
                ->set(BitBagCmsPagesDataSource::class)
                ->autowire()
                ->autoconfigure()
                ->arg('$pageRepository', service('bitbag_sylius_cms_plugin.repository.page'));
        }
    }

    private function resolveBackendUrl(array $config): string
    {
        $rootBackendUrl = (string) ($config['backend_url'] ?? '');
        if ($rootBackendUrl !== '') {
            return $rootBackendUrl;
        }

        return (string) ($config['widget']['backend_url'] ?? '');
    }
}
