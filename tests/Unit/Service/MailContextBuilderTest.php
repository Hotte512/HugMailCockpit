<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Tests\Unit\Service;

use Hug\MailCockpit\Exception\MailCockpitException;
use Hug\MailCockpit\Service\MailContextBuilder;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;

class MailContextBuilderTest extends TestCase
{
    private string $orderId;

    private string $customerId;

    private string $orderLanguageId;

    private string $currencyId;

    private string $salesChannelId;

    protected function setUp(): void
    {
        $this->orderId = Uuid::randomHex();
        $this->customerId = Uuid::randomHex();
        $this->orderLanguageId = Uuid::randomHex();
        $this->currencyId = Uuid::randomHex();
        $this->salesChannelId = Uuid::randomHex();
    }

    public function testOrderContextUsesOrderLanguageNotAdminLanguage(): void
    {
        $adminContext = new Context(new SystemSource());
        $lightOrder = $this->createOrder();
        $fullOrder = $this->createOrder();

        $capturedChain = null;
        $orderRepository = new StaticEntityRepository([
            [$lightOrder],
            function (Criteria $criteria, Context $context) use (&$capturedChain, $fullOrder): array {
                $capturedChain = $context->getLanguageIdChain();

                return [$fullOrder];
            },
        ]);
        $customerRepository = new StaticEntityRepository([]);

        $builder = new MailContextBuilder($orderRepository, $customerRepository);
        $mailContext = $builder->buildOrderContext($this->orderId, $adminContext);

        static::assertIsArray($capturedChain);
        static::assertSame($this->orderLanguageId, $capturedChain[0], 'full order load must use order language');
        static::assertSame($this->orderLanguageId, $mailContext->context->getLanguageIdChain()[0]);
        static::assertSame($this->orderLanguageId, $mailContext->languageId);
        static::assertSame($this->currencyId, $mailContext->context->getCurrencyId());
    }

    public function testOrderContextExposesOrderEntityAndSalesChannel(): void
    {
        $fullOrder = $this->createOrder();

        $orderRepository = new StaticEntityRepository([
            [$this->createOrder()],
            [$fullOrder],
        ]);

        $builder = new MailContextBuilder($orderRepository, new StaticEntityRepository([]));
        $mailContext = $builder->buildOrderContext($this->orderId, new Context(new SystemSource()));

        static::assertSame($fullOrder, $mailContext->templateData['order']);
        // Templates rely on `salesChannel` — MailService injects it at send
        // time, so render/preview must see it as well.
        static::assertSame($fullOrder->getSalesChannel(), $mailContext->templateData['salesChannel']);
        static::assertSame($this->salesChannelId, $mailContext->templateData['salesChannelId']);
        static::assertSame($this->salesChannelId, $mailContext->salesChannelId);
        static::assertSame('max@example.com', $mailContext->recipientEmail);
        static::assertSame('Max Mustermann', $mailContext->recipientName);
    }

    public function testOrderContextLoadsMailRelevantAssociations(): void
    {
        $capturedCriteria = null;
        $orderRepository = new StaticEntityRepository([
            [$this->createOrder()],
            function (Criteria $criteria) use (&$capturedCriteria): array {
                $capturedCriteria = $criteria;

                return [$this->createOrder()];
            },
        ]);

        $builder = new MailContextBuilder($orderRepository, new StaticEntityRepository([]));
        $builder->buildOrderContext($this->orderId, new Context(new SystemSource()));

        static::assertInstanceOf(Criteria::class, $capturedCriteria);
        foreach (['lineItems', 'deliveries', 'transactions', 'orderCustomer', 'currency', 'addresses', 'documents', 'salesChannel'] as $association) {
            static::assertTrue(
                $capturedCriteria->hasAssociation($association),
                sprintf('Association %s must be loaded for the mail context', $association)
            );
        }
    }

    public function testThrowsWhenOrderNotFound(): void
    {
        $orderRepository = new StaticEntityRepository([[]]);

        $builder = new MailContextBuilder($orderRepository, new StaticEntityRepository([]));

        $this->expectException(MailCockpitException::class);

        $builder->buildOrderContext($this->orderId, new Context(new SystemSource()));
    }

    public function testCustomerContextUsesCustomerLanguage(): void
    {
        $customerLanguageId = Uuid::randomHex();

        $lightCustomer = $this->createCustomer($customerLanguageId);
        $fullCustomer = $this->createCustomer($customerLanguageId);

        $capturedChain = null;
        $customerRepository = new StaticEntityRepository([
            [$lightCustomer],
            function (Criteria $criteria, Context $context) use (&$capturedChain, $fullCustomer): array {
                $capturedChain = $context->getLanguageIdChain();

                return [$fullCustomer];
            },
        ]);

        $builder = new MailContextBuilder(new StaticEntityRepository([]), $customerRepository);
        $mailContext = $builder->buildCustomerContext($this->customerId, new Context(new SystemSource()));

        static::assertIsArray($capturedChain);
        static::assertSame($customerLanguageId, $capturedChain[0]);
        static::assertSame($customerLanguageId, $mailContext->languageId);
        static::assertSame($fullCustomer, $mailContext->templateData['customer']);
        static::assertSame($this->salesChannelId, $mailContext->salesChannelId);
        static::assertSame('erika@example.com', $mailContext->recipientEmail);
        static::assertSame('Erika Musterfrau', $mailContext->recipientName);
        static::assertArrayNotHasKey('order', $mailContext->templateData);
    }

    public function testThrowsWhenCustomerNotFound(): void
    {
        $builder = new MailContextBuilder(new StaticEntityRepository([]), new StaticEntityRepository([[]]));

        $this->expectException(MailCockpitException::class);

        $builder->buildCustomerContext($this->customerId, new Context(new SystemSource()));
    }

    public function testVariablesAreDerivedFromBuiltContextWithScalarValues(): void
    {
        $orderRepository = new StaticEntityRepository([
            [$this->createOrder()],
            [$this->createOrder()],
        ]);

        $builder = new MailContextBuilder($orderRepository, new StaticEntityRepository([]));
        $mailContext = $builder->buildOrderContext($this->orderId, new Context(new SystemSource()));

        $variables = $builder->getVariables($mailContext);

        static::assertArrayHasKey('order', $variables);
        // Scalar properties expose their rendered value for the picker …
        static::assertSame('10001', $variables['order']['orderNumber']);
        static::assertSame('99.9', $variables['order']['amountTotal']);
        // … non-scalar properties are listed without a value (twig mode only).
        static::assertArrayHasKey('orderCustomer', $variables['order']);
        static::assertNull($variables['order']['orderCustomer']);
        static::assertArrayNotHasKey('extensions', $variables['order']);
    }

    public function testVariableValuesFormatDates(): void
    {
        $orderDate = new \DateTimeImmutable('2026-07-01 10:30:00');
        $order = $this->createOrder();
        $order->setOrderDateTime($orderDate);

        $orderRepository = new StaticEntityRepository([
            [$this->createOrder()],
            [$order],
        ]);

        $builder = new MailContextBuilder($orderRepository, new StaticEntityRepository([]));
        $mailContext = $builder->buildOrderContext($this->orderId, new Context(new SystemSource()));

        $variables = $builder->getVariables($mailContext);

        static::assertSame('2026-07-01 10:30', $variables['order']['orderDateTime']);
    }

    private function createOrder(): OrderEntity
    {
        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId($this->salesChannelId);
        $salesChannel->setUniqueIdentifier($this->salesChannelId);

        $order = new OrderEntity();
        $order->setSalesChannel($salesChannel);
        $order->setId($this->orderId);
        $order->setUniqueIdentifier($this->orderId);
        $order->setVersionId(Uuid::randomHex());
        $order->setLanguageId($this->orderLanguageId);
        $order->setCurrencyId($this->currencyId);
        $order->setCurrencyFactor(1.0);
        $order->setSalesChannelId($this->salesChannelId);
        $order->setTaxStatus(CartPrice::TAX_STATE_GROSS);
        $order->setOrderNumber('10001');
        $order->setAmountTotal(99.9);

        $orderCustomer = new OrderCustomerEntity();
        $orderCustomer->setId(Uuid::randomHex());
        $orderCustomer->setUniqueIdentifier($orderCustomer->getId());
        $orderCustomer->setEmail('max@example.com');
        $orderCustomer->setFirstName('Max');
        $orderCustomer->setLastName('Mustermann');
        $order->setOrderCustomer($orderCustomer);

        return $order;
    }

    private function createCustomer(string $languageId): CustomerEntity
    {
        $customer = new CustomerEntity();
        $customer->setId($this->customerId);
        $customer->setUniqueIdentifier($this->customerId);
        $customer->setLanguageId($languageId);
        $customer->setSalesChannelId($this->salesChannelId);
        $customer->setEmail('erika@example.com');
        $customer->setFirstName('Erika');
        $customer->setLastName('Musterfrau');

        return $customer;
    }
}
