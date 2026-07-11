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
    ];

    private const CUSTOMER_ASSOCIATIONS = [
        'salutation',
        'group',
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
            templateData: ['order' => $fullOrder],
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
            templateData: ['customer' => $fullCustomer],
            context: $customerContext,
            salesChannelId: $customer->getSalesChannelId(),
            languageId: $customer->getLanguageId(),
            recipientEmail: $fullCustomer->getEmail(),
            recipientName: trim($fullCustomer->getFirstName() . ' ' . $fullCustomer->getLastName()),
        );
    }

    /**
     * Variable keys for the picker, derived from the actually built template
     * data (konzept.md §2) — never hardcoded.
     *
     * @return array<string, list<string>>
     */
    public function getVariables(MailContext $mailContext): array
    {
        $variables = [];

        foreach ($mailContext->templateData as $rootKey => $value) {
            if (!$value instanceof Entity) {
                $variables[$rootKey] = [];

                continue;
            }

            $keys = array_keys($value->getVars());
            $keys = array_values(array_diff($keys, self::INTERNAL_VARIABLE_KEYS));
            sort($keys);

            $variables[$rootKey] = $keys;
        }

        return $variables;
    }
}
