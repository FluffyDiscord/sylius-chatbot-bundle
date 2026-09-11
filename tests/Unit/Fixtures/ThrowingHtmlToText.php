<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Tests\Unit\Fixtures;

use FluffyDiscord\SyliusChatbotBundle\Text\HtmlToText;

readonly class ThrowingHtmlToText extends HtmlToText
{
    public function convert(string $html): string
    {
        throw new \RuntimeException('Conversion failed.');
    }
}
