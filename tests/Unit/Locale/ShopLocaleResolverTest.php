<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Tests\Unit\Locale;

use Doctrine\Common\Collections\ArrayCollection;
use FluffyDiscord\SyliusChatbotBundle\Channel\ChannelResolver;
use FluffyDiscord\SyliusChatbotBundle\Exception\InvalidLocaleException;
use FluffyDiscord\SyliusChatbotBundle\Locale\ShopLocaleResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\Locale;

class ShopLocaleResolverTest extends TestCase
{
    #[DataProvider('provideRequestedLocales')]
    public function testServedLocaleKeepsTheShopSpelling(string $requested, ?string $expected): void
    {
        $resolver = $this->createResolver(['cs_CZ', 'en_US']);

        self::assertSame($expected, $resolver->resolveForChannel($requested));
    }

    /**
     * @return iterable<string, array{string, ?string}>
     */
    public static function provideRequestedLocales(): iterable
    {
        yield 'language only' => ['cs', 'cs_CZ'];
        yield 'exact' => ['cs_CZ', 'cs_CZ'];
        yield 'hyphen separated' => ['en-GB', 'en_US'];
        yield 'lower cased region' => ['cs_cz', 'cs_CZ'];
        yield 'upper cased language' => ['CS-cz', 'cs_CZ'];
        yield 'second locale' => ['en', 'en_US'];
        yield 'unserved language' => ['de', null];
        yield 'unknown region' => ['cs_ZZ', null];
        yield 'unknown language' => ['zz_ZZ', null];
        yield 'empty' => ['', null];
        yield 'accept language list' => ['cs-CZ,cs;q=0.9', null];
    }

    public function testChannelLocalesAreTheFallbackVocabulary(): void
    {
        $resolver = $this->createResolver(['cs_CZ', '']);

        self::assertSame(['cs_CZ'], $resolver->getChannelLocales());
        self::assertSame('cs_CZ', $resolver->resolveForChannel('cs'));
        self::assertNull($resolver->resolveForChannel('de_AT'));
    }

    public function testExactSpellingWinsOverALaterLanguageMatch(): void
    {
        $resolver = $this->createResolver(['de_DE', 'de_AT']);

        self::assertSame('de_AT', $resolver->resolveForChannel('de_AT'));
    }

    public function testTheSourceLocalesReplaceTheChannelOnes(): void
    {
        $resolver = $this->createResolver(['cs_CZ']);

        self::assertSame('en_US', $resolver->resolveServedLocaleOrFail('en-GB', ['en_US']));
    }

    public function testAServedLocaleIsStillMatchedWhenIcuDoesNotKnowItsRegion(): void
    {
        $resolver = $this->createResolver(['cs_CZ']);

        self::assertSame('en_XX', $resolver->resolveServedLocaleOrFail('en_US', ['en_XX']));
    }

    #[DataProvider('provideUnservedLocales')]
    public function testAnUnservedLocaleFails(string $requested, array $servedLocales): void
    {
        $resolver = $this->createResolver(['cs_CZ']);

        $this->expectException(InvalidLocaleException::class);
        $this->expectExceptionMessage(sprintf('Locale "%s" is not served.', $requested));

        $resolver->resolveServedLocaleOrFail($requested, $servedLocales);
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function provideUnservedLocales(): iterable
    {
        yield 'another language' => ['de_AT', ['cs_CZ']];
        yield 'nothing served' => ['cs_CZ', []];
        yield 'blank served locale' => ['en_US', ['']];
    }

    /**
     * @param list<string> $channelLocales
     */
    private function createResolver(array $channelLocales): ShopLocaleResolver
    {
        $locales = [];

        foreach ($channelLocales as $code) {
            $locale = new Locale();
            $locale->setCode($code);
            $locales[] = $locale;
        }

        $channel = $this->createStub(ChannelInterface::class);
        $channel->method('getLocales')->willReturn(new ArrayCollection($locales));

        $channelResolver = $this->createStub(ChannelResolver::class);
        $channelResolver->method('getChannel')->willReturn($channel);

        return new ShopLocaleResolver($channelResolver);
    }
}
