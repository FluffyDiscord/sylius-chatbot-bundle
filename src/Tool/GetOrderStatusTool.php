<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Tool;

use FluffyDiscord\SyliusChatbotBundle\Contract\ChatbotToolInterface;
use FluffyDiscord\SyliusChatbotBundle\DTO\ContentItem;
use FluffyDiscord\SyliusChatbotBundle\DTO\FormDefinition;
use FluffyDiscord\SyliusChatbotBundle\DTO\FormField;
use FluffyDiscord\SyliusChatbotBundle\DTO\ToolCallContext;
use FluffyDiscord\SyliusChatbotBundle\DTO\ToolDefinition;
use FluffyDiscord\SyliusChatbotBundle\DTO\ToolResult;
use FluffyDiscord\SyliusChatbotBundle\DTO\ToolUiHints;
use FluffyDiscord\SyliusChatbotBundle\Enum\FormFieldType;
use FluffyDiscord\SyliusChatbotBundle\Tool\DTO\OrderStatusArguments;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Order\Model\OrderInterface as BaseOrderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class GetOrderStatusTool implements ChatbotToolInterface
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private TranslatorInterface $translator,
    ) {
    }

    public function getDefinition(): ToolDefinition
    {
        return new ToolDefinition(
            'get_order_status',
            'fluffydiscord_sylius_chatbot.tool.get_order_status.description',
            new ToolUiHints(new FormDefinition(
                'get_order_status',
                'fluffydiscord_sylius_chatbot.tool.get_order_status.form.title',
                [
                    new FormField(
                        'orderNumber',
                        'fluffydiscord_sylius_chatbot.tool.get_order_status.form.order_number',
                        FormFieldType::Text,
                        true,
                    ),
                    new FormField(
                        'email',
                        'fluffydiscord_sylius_chatbot.tool.get_order_status.form.email',
                        FormFieldType::Email,
                        true,
                    ),
                ],
                'fluffydiscord_sylius_chatbot.tool.get_order_status.form.submit',
            )),
        );
    }

    public function getArgumentsClass(): string
    {
        return OrderStatusArguments::class;
    }

    public function execute(object $arguments, ToolCallContext $context): ToolResult
    {
        $order = $this->orderRepository->findOneByNumber($arguments->orderNumber);
        $isVisible = $order instanceof OrderInterface && $this->isOwnedBy($order, $arguments->email);
        if (!$isVisible) {
            return new ToolResult([new ContentItem($this->translator->trans(
                'fluffydiscord_sylius_chatbot.tool.get_order_status.not_found',
                ['%number%' => $arguments->orderNumber],
                'messages',
                $context->locale,
            ))]);
        }

        return new ToolResult([new ContentItem($this->translator->trans(
            'fluffydiscord_sylius_chatbot.tool.get_order_status.summary',
            [
                '%number%' => (string) $order->getNumber(),
                '%state%' => (string) $order->getState(),
                '%paymentState%' => (string) $order->getPaymentState(),
                '%shippingState%' => (string) $order->getShippingState(),
                '%items%' => $order->getTotalQuantity(),
                '%total%' => $this->formatTotal($order),
                '%tracking%' => $this->formatTracking($order, $context->locale),
            ],
            'messages',
            $context->locale,
        ))]);
    }

    private function isOwnedBy(OrderInterface $order, string $email): bool
    {
        $isPlaced = $order->getState() !== BaseOrderInterface::STATE_CART;
        if (!$isPlaced) {
            return false;
        }

        $customer = $order->getCustomer();
        if ($customer === null) {
            return false;
        }

        $customerEmail = $customer->getEmail();
        if ($customerEmail === null) {
            return false;
        }

        return strcasecmp($customerEmail, $email) === 0;
    }

    private function formatTotal(OrderInterface $order): string
    {
        return sprintf(
            '%s %s',
            number_format($order->getTotal() / 100, 2, '.', ' '),
            (string) $order->getCurrencyCode(),
        );
    }

    private function formatTracking(OrderInterface $order, string $locale): string
    {
        $trackingCodes = [];
        foreach ($order->getShipments() as $shipment) {
            if (!$shipment instanceof ShipmentInterface) {
                continue;
            }
            $trackingCode = $shipment->getTracking();
            if ($trackingCode !== null && $trackingCode !== '') {
                $trackingCodes[] = $trackingCode;
            }
        }

        if ($trackingCodes === []) {
            return '';
        }

        return $this->translator->trans(
            'fluffydiscord_sylius_chatbot.tool.get_order_status.tracking',
            ['%codes%' => implode(', ', $trackingCodes)],
            'messages',
            $locale,
        );
    }
}
