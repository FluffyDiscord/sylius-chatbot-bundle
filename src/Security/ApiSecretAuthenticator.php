<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

readonly class ApiSecretAuthenticator implements AccessTokenHandlerInterface
{
    public function __construct(
        #[Autowire(param: 'fluffydiscord_sylius_chatbot.api_secret')]
        private string $apiSecret,
    ) {
    }

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        if ($this->apiSecret === '') {
            throw new BadCredentialsException('Chatbot API secret is not configured.');
        }

        $isValid = hash_equals($this->apiSecret, $accessToken);
        if (!$isValid) {
            throw new BadCredentialsException('Invalid chatbot API secret.');
        }

        return new UserBadge(
            ChatbotBackendUser::IDENTIFIER,
            fn (): ChatbotBackendUser => new ChatbotBackendUser(),
        );
    }
}
