<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Tests\Unit\Service;

use Hug\MailCockpit\Service\TwigContentPolicy;
use PHPUnit\Framework\TestCase;

class TwigContentPolicyTest extends TestCase
{
    private TwigContentPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new TwigContentPolicy();
    }

    public function testPlainTextDoesNotRequireTwigEditor(): void
    {
        static::assertFalse($this->policy->requiresTwigEditor('Hello, thank you for your order.'));
    }

    public function testSimpleVariableInterpolationDoesNotRequireTwigEditor(): void
    {
        static::assertFalse($this->policy->requiresTwigEditor(
            'Your order {{ order.orderNumber }} has shipped, {{ customer.firstName }}!'
        ));
    }

    public function testVariableWithoutDotsDoesNotRequireTwigEditor(): void
    {
        static::assertFalse($this->policy->requiresTwigEditor('Hi {{ salutation }}'));
    }

    public function testBlockTagRequiresTwigEditor(): void
    {
        static::assertTrue($this->policy->requiresTwigEditor(
            '{% if order.amountTotal > 100 %}Big spender{% endif %}'
        ));
    }

    public function testCommentRequiresTwigEditor(): void
    {
        static::assertTrue($this->policy->requiresTwigEditor('Hello {# hidden #}'));
    }

    public function testFilterExpressionRequiresTwigEditor(): void
    {
        static::assertTrue($this->policy->requiresTwigEditor('{{ order.orderNumber|upper }}'));
    }

    public function testFunctionCallRequiresTwigEditor(): void
    {
        static::assertTrue($this->policy->requiresTwigEditor('{{ dump(order) }}'));
    }

    public function testArrayAccessRequiresTwigEditor(): void
    {
        static::assertTrue($this->policy->requiresTwigEditor('{{ order.lineItems[0].label }}'));
    }
}
