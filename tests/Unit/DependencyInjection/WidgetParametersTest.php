<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Tests\Unit\DependencyInjection;

use FluffyDiscord\SyliusChatbotBundle\FluffyDiscordSyliusChatbotBundle;
use FluffyDiscord\SyliusChatbotBundle\Widget\WidgetViewProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class WidgetParametersTest extends TestCase
{
    public function testEveryWidgetParameterTheProviderAutowiresIsDefined(): void
    {
        $container = $this->loadExtension();

        foreach ($this->getAutowiredParameterNames() as $parameterName) {
            self::assertTrue(
                $container->hasParameter($parameterName),
                sprintf('WidgetViewProvider autowires "%s", which loadExtension() never sets.', $parameterName),
            );
        }
    }

    public function testChannelsParameterKeepsTheConfiguredCodes(): void
    {
        $container = $this->loadExtension(['widget' => ['channels' => ['main-channel']]]);

        self::assertSame(['main-channel'], $container->getParameter('fluffydiscord_sylius_chatbot.widget.channels'));
    }

    public function testChannelsDefaultToEveryChannel(): void
    {
        $container = $this->loadExtension();

        self::assertSame([], $container->getParameter('fluffydiscord_sylius_chatbot.widget.channels'));
    }

    /**
     * @return list<string>
     */
    private function getAutowiredParameterNames(): array
    {
        $parameterNames = [];
        $constructor = (new ReflectionClass(WidgetViewProvider::class))->getConstructor();

        foreach ($constructor->getParameters() as $parameter) {
            foreach ($parameter->getAttributes(Autowire::class) as $attribute) {
                $parameterName = $attribute->getArguments()['param'] ?? null;
                if (is_string($parameterName)) {
                    $parameterNames[] = $parameterName;
                }
            }
        }

        return $parameterNames;
    }

    private function loadExtension(array $config = []): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.debug', false);

        $instanceof = [];
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__));
        $configurator = new ContainerConfigurator($container, $loader, $instanceof, __FILE__, __FILE__);

        $bundle = new FluffyDiscordSyliusChatbotBundle();
        $mergedConfig = array_replace_recursive([
            'api_secret' => 'secret',
            'backend_url' => 'https://chatbot.example.com',
            'ingest_secret' => 'ingest',
            'widget' => ['enabled' => true, 'backend_url' => '', 'site_key' => 'pk_site', 'cdn_url' => '', 'channels' => []],
        ], $config);

        $bundle->loadExtension($mergedConfig, $configurator, $container);

        return $container;
    }
}
