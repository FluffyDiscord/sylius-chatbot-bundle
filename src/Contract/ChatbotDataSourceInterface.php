<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Contract;

use FluffyDiscord\SyliusChatbotBundle\DTO\DocumentPage;
use FluffyDiscord\SyliusChatbotBundle\DTO\SourceDefinition;
use FluffyDiscord\SyliusChatbotBundle\DTO\SourceQuery;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('fluffydiscord_chatbot.source')]
interface ChatbotDataSourceInterface
{
    public function getDefinition(): SourceDefinition;

    public function getDocuments(SourceQuery $query): DocumentPage;
}
