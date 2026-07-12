<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Core\Content\TextSnippet;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<TextSnippetEntity>
 */
class TextSnippetCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return TextSnippetEntity::class;
    }
}
