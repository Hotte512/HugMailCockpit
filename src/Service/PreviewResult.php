<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Service;

final readonly class PreviewResult
{
    /**
     * @param list<array{field: string, message: string, line: int|null}> $errors
     */
    public function __construct(
        public ?string $subject,
        public ?string $contentHtml,
        public array $errors,
    ) {
    }

    public function isSuccessful(): bool
    {
        return $this->errors === [];
    }
}
