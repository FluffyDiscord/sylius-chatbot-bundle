<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Tests\Unit\EventListener;

use FluffyDiscord\SyliusChatbotBundle\EventListener\ApiExceptionListener;
use FluffyDiscord\SyliusChatbotBundle\Exception\ForbiddenUserAgentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ApiExceptionListenerTest extends TestCase
{
    public function testChatbotExceptionsBecomeTheTranslatedErrorEnvelope(): void
    {
        $event = $this->createEvent('/chatbot/v1/tools', new ForbiddenUserAgentException('Scanner/1.0'));

        $this->createListener()($event);

        $response = $event->getResponse();
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        self::assertSame('forbidden_user_agent', $payload['error']['code']);
        self::assertSame('translated:fluffydiscord_sylius_chatbot.error.forbidden_user_agent', $payload['error']['message']);
        self::assertSame([], $payload['error']['violations']);
    }

    public function testShopRoutesSharingThePrefixKeepTheShopErrorPage(): void
    {
        $event = $this->createEvent('/chatbot/v1x', new NotFoundHttpException());

        $this->createListener()($event);

        self::assertNull($event->getResponse());
    }

    private function createListener(): ApiExceptionListener
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $key): string => 'translated:' . $key);

        return new ApiExceptionListener($translator);
    }

    private function createEvent(string $path, \Throwable $throwable): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create($path),
            HttpKernelInterface::MAIN_REQUEST,
            $throwable,
        );
    }
}
