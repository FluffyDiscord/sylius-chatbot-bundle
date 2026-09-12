<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Locale;

use FluffyDiscord\SyliusChatbotBundle\Channel\ChannelResolver;
use FluffyDiscord\SyliusChatbotBundle\Exception\InvalidLocaleException;
use Symfony\Component\Intl\Locales;

class ShopLocaleResolver
{
    public function __construct(
        private readonly ChannelResolver $channelResolver,
    ) {
    }

    /**
     * @param list<string> $servedLocales
     *
     * @throws InvalidLocaleException
     */
    public function resolveServedLocaleOrFail(string $requested, array $servedLocales): string
    {
        $servedLocale = $this->resolveServedLocale($requested, $servedLocales);

        if ($servedLocale === null) {
            throw new InvalidLocaleException($requested);
        }

        return $servedLocale;
    }

    public function resolveForChannel(string $requested): ?string
    {
        return $this->resolveServedLocale($requested, $this->getChannelLocales());
    }

    /**
     * @return list<string>
     */
    public function getChannelLocales(): array
    {
        $locales = [];

        foreach ($this->channelResolver->getChannel()->getLocales() as $channelLocale) {
            $localeCode = $channelLocale->getCode();

            if ($localeCode === null || $localeCode === '') {
                continue;
            }

            $locales[] = $localeCode;
        }

        return $locales;
    }

    /**
     * @param list<string> $servedLocales
     */
    private function resolveServedLocale(string $requested, array $servedLocales): ?string
    {
        $requestedLocale = $this->getKnownLocale($requested);

        if ($requestedLocale === null) {
            return null;
        }

        $sameLocale = $this->findSameLocale($requestedLocale, $servedLocales);

        if ($sameLocale !== null) {
            return $sameLocale;
        }

        return $this->findSameLanguage($requestedLocale, $servedLocales);
    }

    /**
     * @param list<string> $servedLocales
     */
    private function findSameLocale(string $requestedLocale, array $servedLocales): ?string
    {
        foreach ($servedLocales as $servedLocale) {
            $isSameLocale = $this->getCanonicalLocale($servedLocale) === $requestedLocale;

            if ($isSameLocale) {
                return $servedLocale;
            }
        }

        return null;
    }

    /**
     * @param list<string> $servedLocales
     */
    private function findSameLanguage(string $requestedLocale, array $servedLocales): ?string
    {
        $requestedLanguage = $this->getLanguage($requestedLocale);

        foreach ($servedLocales as $servedLocale) {
            $isSameLanguage = $this->getLanguage($servedLocale) === $requestedLanguage;

            if ($isSameLanguage) {
                return $servedLocale;
            }
        }

        return null;
    }

    private function getKnownLocale(string $locale): ?string
    {
        $canonicalLocale = $this->getCanonicalLocale($locale);
        $isKnown = Locales::exists($canonicalLocale);

        if (!$isKnown) {
            return null;
        }

        return $canonicalLocale;
    }

    private function getLanguage(string $locale): string
    {
        $canonicalLocale = $this->getCanonicalLocale($locale);

        if ($canonicalLocale === '') {
            return '';
        }

        return (string) \Locale::getPrimaryLanguage($canonicalLocale);
    }

    private function getCanonicalLocale(string $locale): string
    {
        if ($locale === '') {
            return '';
        }

        return (string) \Locale::canonicalize($locale);
    }
}
