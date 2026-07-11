<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Service;

use Shopware\Core\Framework\Adapter\AdapterException;
use Shopware\Core\Framework\Adapter\Twig\StringTemplateRenderer;
use Shopware\Core\Framework\Context;

/**
 * Renders editor content against real order/customer data (F1 preview + F4).
 * Twig errors are mapped to per-field error structures instead of bubbling up,
 * so the admin can show them inline (message + line, konzept.md §5).
 */
class TemplatePreviewRenderer
{
    public function __construct(
        private readonly StringTemplateRenderer $stringTemplateRenderer,
    ) {
    }

    /**
     * @param array<string, mixed> $templateData
     */
    public function render(
        string $subject,
        string $contentHtml,
        array $templateData,
        Context $context,
        ?string $headerHtml = null,
        ?string $footerHtml = null,
    ): PreviewResult {
        $errors = [];

        // Letterhead is wrapped around the content BEFORE the twig pass —
        // the exact behavior of MailService::buildContents().
        if ($headerHtml !== null || $footerHtml !== null) {
            $contentHtml = \sprintf('%s%s%s', $headerHtml ?? '', $contentHtml, $footerHtml ?? '');
        }

        // Subject is rendered without HTML escaping, content with — the exact
        // behavior of MailService::send() (subject via render(..., false)).
        $renderedSubject = $this->renderField('subject', $subject, false, $templateData, $context, $errors);
        $renderedContent = $this->renderField('contentHtml', $contentHtml, true, $templateData, $context, $errors);

        return new PreviewResult($renderedSubject, $renderedContent, $errors);
    }

    /**
     * @param array<string, mixed> $templateData
     * @param list<array{field: string, message: string, line: int|null}> $errors
     */
    private function renderField(
        string $field,
        string $template,
        bool $htmlEscape,
        array $templateData,
        Context $context,
        array &$errors,
    ): ?string {
        try {
            return $this->stringTemplateRenderer->render($template, $templateData, $context, $htmlEscape);
        } catch (AdapterException $exception) {
            $errors[] = [
                'field' => $field,
                'message' => $exception->getMessage(),
                'line' => $this->extractLine($exception->getMessage()),
            ];

            return null;
        }
    }

    private function extractLine(string $message): ?int
    {
        // Twig appends "at line N" to its error messages; the AdapterException
        // wraps only the message, not the structured template line.
        if (preg_match('/at line (\d+)/', $message, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }
}
