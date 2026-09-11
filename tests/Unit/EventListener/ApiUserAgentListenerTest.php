<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Tests\Unit\EventListener;

use FluffyDiscord\SyliusChatbotBundle\Enum\ApiErrorCode;
use FluffyDiscord\SyliusChatbotBundle\EventListener\ApiUserAgentListener;
use FluffyDiscord\SyliusChatbotBundle\Exception\ForbiddenUserAgentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ApiUserAgentListenerTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function acceptedUserAgents(): iterable
    {
        yield 'backend' => ['AiChatbot/1.0'];
        yield 'newer backend version' => ['AiChatbot/9.9'];
        yield 'contact url comment' => ['AiChatbot/1.1 (+https://example.com/bot)'];
        yield 'no version' => ['AiChatbot/'];
    }

    /**
     * @return iterable<string, array{?string}>
     */
    public static function rejectedUserAgents(): iterable
    {
        yield 'foreign client' => ['Scanner/1.0'];
        yield 'lookalike product' => ['AiChatbotX/1.0'];
        yield 'wrong case' => ['aichatbot/1.0'];
        yield 'product token without the slash' => ['AiChatbot'];
        yield 'missing header' => [null];
    }

    #[DataProvider('acceptedUserAgents')]
    public function testBackendRequestsPassThrough(string $userAgent): void
    {
        $event = $this->createEvent('/chatbot/v1/tools', $userAgent);

        (new ApiUserAgentListener())($event);

        self::assertNull($event->getResponse());
    }

    public function testThePrefixItselfIsGuarded(): void
    {
        $accepted = $this->createEvent('/chatbot/v1', 'AiChatbot/1.0');
        (new ApiUserAgentListener())($accepted);
        self::assertNull($accepted->getResponse());

        $this->expectException(ForbiddenUserAgentException::class);

        (new ApiUserAgentListener())($this->createEvent('/chatbot/v1', 'Scanner/1.0'));
    }

    #[DataProvider('rejectedUserAgents')]
    public function testForeignRequestsAreRejected(?string $userAgent): void
    {
        $event = $this->createEvent('/chatbot/v1/tools', $userAgent);

        try {
            (new ApiUserAgentListener())($event);
            self::fail('The listener accepted a foreign user agent.');
        } catch (ForbiddenUserAgentException $exception) {
            self::assertSame(Response::HTTP_FORBIDDEN, $exception->getStatusCode());
            self::assertSame(ApiErrorCode::ForbiddenUserAgent, $exception->getErrorCode());
            self::assertNull($event->getResponse());
        }
    }

    public function testTheRejectedUserAgentIsTruncatedBeforeItIsLogged(): void
    {
        $event = $this->createEvent('/chatbot/v1/tools', str_repeat('A', 4096));

        $this->expectExceptionMessage('User agent "'.str_repeat('A', 64).'" is not the chatbot backend.');

        (new ApiUserAgentListener())($event);
    }

    public function testShopRoutesSharingThePrefixAreNotTouched(): void
    {
        $event = $this->createEvent('/chatbot/v1x', 'Mozilla/5.0');

        (new ApiUserAgentListener())($event);

        self::assertNull($event->getResponse());
    }

    public function testOtherShopRoutesAreNotTouched(): void
    {
        $event = $this->createEvent('/zazitek/plachteni', 'Mozilla/5.0');

        (new ApiUserAgentListener())($event);

        self::assertNull($event->getResponse());
    }

    public function testSubRequestsAreNotChecked(): void
    {
        $event = $this->createEvent('/chatbot/v1/tools', 'Mozilla/5.0', HttpKernelInterface::SUB_REQUEST);

        (new ApiUserAgentListener())($event);

        self::assertNull($event->getResponse());
    }

    private function createEvent(
        string $path,
        ?string $userAgent,
        int $requestType = HttpKernelInterface::MAIN_REQUEST,
    ): RequestEvent {
        $request = Request::create($path, 'GET', [], [], [], ['HTTP_USER_AGENT' => $userAgent ?? '']);
        if ($userAgent === null) {
            $request->headers->remove('User-Agent');
        }

        return new RequestEvent($this->createStub(HttpKernelInterface::class), $request, $requestType);
    }
}
