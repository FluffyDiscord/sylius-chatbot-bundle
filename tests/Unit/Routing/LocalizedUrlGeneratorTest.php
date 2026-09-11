<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Tests\Unit\Routing;

use FluffyDiscord\SyliusChatbotBundle\Routing\LocalizedUrlGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

class LocalizedUrlGeneratorTest extends TestCase
{
    public function testLocaleTravelsInTheContextAndNotInTheParameters(): void
    {
        $context = new RequestContext();
        $router = $this->createMock(RouterInterface::class);
        $router->method('getContext')->willReturn($context);
        $router->expects(self::once())
            ->method('generate')
            ->with('sylius_shop_product_show', ['slug' => 'zazitek'], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturnCallback(static function () use ($context): string {
                self::assertSame('cs_CZ', $context->getParameter('_locale'));

                return 'https://shop.example/zazitek';
            });

        $generator = new LocalizedUrlGenerator($router);
        $url = $generator->generateAbsoluteUrl('sylius_shop_product_show', ['slug' => 'zazitek'], 'cs_CZ');

        self::assertSame('https://shop.example/zazitek', $url);
        self::assertNull($context->getParameter('_locale'));
    }

    public function testAPreviousContextLocaleIsRestored(): void
    {
        $context = new RequestContext();
        $context->setParameter('_locale', 'en_US');

        $router = $this->createStub(RouterInterface::class);
        $router->method('getContext')->willReturn($context);
        $router->method('generate')->willReturn('https://shop.example/zazitek');

        $generator = new LocalizedUrlGenerator($router);
        $generator->generateAbsoluteUrl('sylius_shop_product_show', ['slug' => 'zazitek'], 'cs_CZ');

        self::assertSame('en_US', $context->getParameter('_locale'));
    }

    public function testAFailedGenerationStillRestoresTheContext(): void
    {
        $context = new RequestContext();
        $router = $this->createStub(RouterInterface::class);
        $router->method('getContext')->willReturn($context);
        $router->method('generate')->willThrowException(new RouteNotFoundException('no such route'));

        $generator = new LocalizedUrlGenerator($router);

        $this->expectException(RouteNotFoundException::class);

        try {
            $generator->generateAbsoluteUrl('missing_route', [], 'cs_CZ');
        } finally {
            self::assertNull($context->getParameter('_locale'));
        }
    }
}
