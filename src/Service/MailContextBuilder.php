<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Service;

use Hug\MailCockpit\Exception\MailCockpitException;
use Shopware\Core\Checkout\Customer\CustomerCollection;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class MailContextBuilder
{
    /**
     * Association set mirrored from the core OrderStorer::loadOrder() so our
     * free mails see the same `order` structure as flow-triggered mails.
     */
    private const ORDER_ASSOCIATIONS = [
        'orderCustomer.salutation',
        'lineItems.cover',
        'lineItems.downloads.media',
        'deliveries.shippingMethod',
        'deliveries.shippingOrderAddress.country',
        'deliveries.shippingOrderAddress.countryState',
        'deliveries.stateMachineState',
        'stateMachineState',
        'transactions.stateMachineState',
        'transactions.paymentMethod',
        'currency',
        'addresses.country',
        'addresses.countryState',
        'tags',
        'documents',
        // MailService loads the sales channel with domains for $templateData —
        // templates build storefront links from salesChannel.domains.
        'salesChannel.domains',
    ];

    private const CUSTOMER_ASSOCIATIONS = [
        'salutation',
        'group',
        'salesChannel.domains',
        'defaultBillingAddress.country',
        'defaultBillingAddress.countryState',
        'defaultShippingAddress.country',
        'defaultShippingAddress.countryState',
    ];

    /**
     * Template-data keys that are internal entity plumbing, not mail variables.
     */
    private const INTERNAL_VARIABLE_KEYS = ['extensions', '_uniqueIdentifier', 'translated', 'versionId'];

    /**
     * @param EntityRepository<OrderCollection> $orderRepository
     * @param EntityRepository<CustomerCollection> $customerRepository
     */
    public function __construct(
        private readonly EntityRepository $orderRepository,
        private readonly EntityRepository $customerRepository,
    ) {
    }

    public function buildOrderContext(string $orderId, Context $context): MailContext
    {
        $order = $this->orderRepository->search(new Criteria([$orderId]), $context)->first();

        if (!$order instanceof OrderEntity) {
            throw MailCockpitException::orderNotFound($orderId);
        }

        // Language chain with the order language first — same pattern as the
        // core OrderStateChangeEventListener::getContext(). The admin language
        // must never leak into the mail.
        $orderContext = new Context(
            $context->getSource(),
            $order->getRuleIds() ?? [],
            $order->getCurrencyId(),
            array_values(array_unique(array_merge([$order->getLanguageId()], $context->getLanguageIdChain()))),
            $context->getVersionId(),
            $order->getCurrencyFactor(),
            true,
            $order->getTaxStatus() ?? $order->getPrice()->getTaxStatus(),
            $order->getItemRounding() ?? $context->getRounding(),
        );
        $orderContext->addState(...$context->getStates());
        $orderContext->addExtensions($context->getExtensions());

        $criteria = new Criteria([$orderId]);
        foreach (self::ORDER_ASSOCIATIONS as $association) {
            $criteria->addAssociation($association);
        }
        $criteria->getAssociation('transactions')->addSorting(new FieldSorting('createdAt'));

        $fullOrder = $this->orderRepository->search($criteria, $orderContext)->first();

        if (!$fullOrder instanceof OrderEntity) {
            throw MailCockpitException::orderNotFound($orderId);
        }

        $orderCustomer = $fullOrder->getOrderCustomer();

        return new MailContext(
            // salesChannel/salesChannelId mirror what MailService injects into
            // the template data at send time — templates rely on them.
            templateData: [
                'order' => $fullOrder,
                'salesChannel' => $fullOrder->getSalesChannel(),
                'salesChannelId' => $order->getSalesChannelId(),
            ],
            context: $orderContext,
            salesChannelId: $order->getSalesChannelId(),
            languageId: $order->getLanguageId(),
            recipientEmail: $orderCustomer?->getEmail(),
            recipientName: $orderCustomer !== null
                ? trim($orderCustomer->getFirstName() . ' ' . $orderCustomer->getLastName())
                : null,
        );
    }

    public function buildCustomerContext(string $customerId, Context $context): MailContext
    {
        $customer = $this->customerRepository->search(new Criteria([$customerId]), $context)->first();

        if (!$customer instanceof CustomerEntity) {
            throw MailCockpitException::customerNotFound($customerId);
        }

        $customerContext = new Context(
            $context->getSource(),
            [],
            $context->getCurrencyId(),
            array_values(array_unique(array_merge([$customer->getLanguageId()], $context->getLanguageIdChain()))),
            $context->getVersionId(),
            $context->getCurrencyFactor(),
            true,
            $context->getTaxState(),
            $context->getRounding(),
        );
        $customerContext->addState(...$context->getStates());
        $customerContext->addExtensions($context->getExtensions());

        $criteria = new Criteria([$customerId]);
        foreach (self::CUSTOMER_ASSOCIATIONS as $association) {
            $criteria->addAssociation($association);
        }

        $fullCustomer = $this->customerRepository->search($criteria, $customerContext)->first();

        if (!$fullCustomer instanceof CustomerEntity) {
            throw MailCockpitException::customerNotFound($customerId);
        }

        return new MailContext(
            templateData: [
                'customer' => $fullCustomer,
                'salesChannel' => $fullCustomer->getSalesChannel(),
                'salesChannelId' => $customer->getSalesChannelId(),
            ],
            context: $customerContext,
            salesChannelId: $customer->getSalesChannelId(),
            languageId: $customer->getLanguageId(),
            recipientEmail: $fullCustomer->getEmail(),
            recipientName: trim($fullCustomer->getFirstName() . ' ' . $fullCustomer->getLastName()),
        );
    }

    /**
     * Variable keys for the picker, derived from the actually built template
     * data (konzept.md §2) — never hardcoded. Scalar properties carry their
     * rendered value so the picker can insert real values in the simple
     * editor; non-scalar properties map to null (usable in twig mode only).
     *
     * @return array<string, array<string, string|null>>
     */
    public function getVariables(MailContext $mailContext): array
    {
        $variables = [];

        foreach ($mailContext->templateData as $rootKey => $value) {
            if (!$value instanceof Entity) {
                $variables[$rootKey] = [];

                continue;
            }

            $vars = $value->getVars();
            $keys = array_values(array_diff(array_keys($vars), self::INTERNAL_VARIABLE_KEYS));
            sort($keys);

            $entries = [];
            foreach ($keys as $key) {
                $entries[$key] = $this->toScalarPreview($vars[$key]);
            }

            $variables[$rootKey] = $entries;
        }

        return $variables;
    }

    private function toScalarPreview(mixed $value): ?string
    {
        if (\is_string($value)) {
            return $value;
        }

        if (\is_int($value) || \is_float($value)) {
            return (string) $value;
        }

        if (\is_bool($value)) {
            return $value ? '1' : '0';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i');
        }

        return null;
    }
}
