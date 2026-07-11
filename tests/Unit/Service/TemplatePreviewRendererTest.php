<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Tests\Unit\Service;

use Hug\MailCockpit\Service\TemplatePreviewRenderer;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Adapter\Twig\StringTemplateRenderer;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class TemplatePreviewRendererTest extends TestCase
{
    private TemplatePreviewRenderer $renderer;

    private Context $context;

    protected function setUp(): void
    {
        $cacheDir = sys_get_temp_dir() . '/hug-mail-cockpit-preview-test-' . uniqid();
        $stringTemplateRenderer = new StringTemplateRenderer(new Environment(new ArrayLoader()), $cacheDir);

        $this->renderer = new TemplatePreviewRenderer($stringTemplateRenderer);
        $this->context = new Context(new SystemSource());
    }

    public function testRendersSubjectAndContentAgainstTemplateData(): void
    {
        $order = new OrderEntity();
        $order->setOrderNumber('10001');

        $result = $this->renderer->render(
            'Order {{ order.orderNumber }}',
            '<p>Number: {{ order.orderNumber }}</p>',
            ['order' => $order],
            $this->context,
        );

        static::assertTrue($result->isSuccessful());
        static::assertSame([], $result->errors);
        static::assertSame('Order 10001', $result->subject);
        static::assertSame('<p>Number: 10001</p>', $result->contentHtml);
    }

    public function testSubjectIsNotHtmlEscapedButContentIs(): void
    {
        $result = $this->renderer->render(
            '{{ shopName }}',
            '{{ shopName }}',
            ['shopName' => 'Muster & Shop'],
            $this->context,
        );

        static::assertSame('Muster & Shop', $result->subject);
        static::assertSame('Muster &amp; Shop', $result->contentHtml);
    }

    public function testTwigSyntaxErrorIsMappedToReadableErrorWithLine(): void
    {
        $result = $this->renderer->render(
            'Valid subject',
            "line one\n{% if %}\nline three",
            [],
            $this->context,
        );

        static::assertFalse($result->isSuccessful());
        static::assertNull($result->contentHtml);
        static::assertCount(1, $result->errors);
        static::assertSame('contentHtml', $result->errors[0]['field']);
        static::assertNotSame('', $result->errors[0]['message']);
        static::assertSame(2, $result->errors[0]['line']);
        // The valid subject must still be rendered so the user only fixes the body.
        static::assertSame('Valid subject', $result->subject);
    }

    public function testUnknownVariableIsReportedAsError(): void
    {
        $result = $this->renderer->render(
            'Subject',
            '{{ order.orderNumber }}',
            [],
            $this->context,
        );

        static::assertFalse($result->isSuccessful());
        static::assertCount(1, $result->errors);
        static::assertSame('contentHtml', $result->errors[0]['field']);
    }

    public function testLenientModeStillRendersDespiteUnknownVariables(): void
    {
        $result = $this->renderer->render(
            'Subject',
            'Hello {{ unknownVariable }}!',
            [],
            $this->context,
            lenient: true,
        );

        // The missing variable is still reported (konzept.md §5) …
        static::assertFalse($result->isSuccessful());
        static::assertCount(1, $result->errors);
        static::assertSame('contentHtml', $result->errors[0]['field']);
        // … but a lenient second pass produces renderable output.
        static::assertSame('Hello !', $result->contentHtml);
        static::assertSame('Subject', $result->subject);
    }

    public function testLenientModeDoesNotHideSyntaxErrors(): void
    {
        $result = $this->renderer->render(
            'Subject',
            '{% if %}',
            [],
            $this->context,
            lenient: true,
        );

        static::assertFalse($result->isSuccessful());
        static::assertNull($result->contentHtml);
    }

    public function testLetterheadWrapsContentBeforeRendering(): void
    {
        $order = new OrderEntity();
        $order->setOrderNumber('10001');

        $result = $this->renderer->render(
            'Subject',
            '<p>Body {{ order.orderNumber }}</p>',
            ['order' => $order],
            $this->context,
            headerHtml: '<div>Header {{ order.orderNumber }}</div>',
            footerHtml: '<div>Footer</div>',
        );

        static::assertTrue($result->isSuccessful());
        // Header/footer are wrapped around the content BEFORE the twig pass —
        // exactly like MailService::buildContents().
        static::assertSame(
            '<div>Header 10001</div><p>Body 10001</p><div>Footer</div>',
            $result->contentHtml,
        );
    }

    public function testSubjectErrorIsReportedWithSubjectField(): void
    {
        $result = $this->renderer->render(
            '{% broken %}',
            'Valid content',
            [],
            $this->context,
        );

        static::assertFalse($result->isSuccessful());
        static::assertSame('subject', $result->errors[0]['field']);
        static::assertNull($result->subject);
        static::assertSame('Valid content', $result->contentHtml);
    }
}
