<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\EventListener;

use FluffyDiscord\SyliusChatbotBundle\Exception\ForbiddenUserAgentException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

#[AsEventListener(event: 'kernel.request', priority: 12)]
readonly class ApiUserAgentListener
{
    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $isChatbotRequest = $this->isChatbotPath($event->getRequest()->getPathInfo());
        if (!$isChatbotRequest) {
            return;
        }

        $userAgent = (string) $event->getRequest()->headers->get('User-Agent');
        $isBackendUserAgent = str_starts_with($userAgent, $this->getRequiredUserAgentPrefix());
        if (!$isBackendUserAgent) {
            throw new ForbiddenUserAgentException($userAgent);
        }
    }

    private function getRequiredUserAgentPrefix(): string
    {
        return 'AiChatbot/';
    }

    private function getPathPrefix(): string
    {
        return '/chatbot/v1';
    }

    private function isChatbotPath(string $path): bool
    {
        $prefix = $this->getPathPrefix();

        return $path === $prefix || str_starts_with($path, $prefix . '/');
    }
}
