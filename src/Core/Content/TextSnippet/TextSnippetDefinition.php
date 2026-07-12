<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Core\Content\TextSnippet;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\AllowHtml;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * Text building blocks for the compose modal (1.1 feature, konzept.md §2):
 * reusable snippets (greetings, standard answers) inserted at the cursor.
 * Deliberately not translatable in this iteration.
 */
class TextSnippetDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'hug_mail_text_snippet';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return TextSnippetEntity::class;
    }

    public function getCollectionClass(): string
    {
        return TextSnippetCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new StringField('name', 'name'))->addFlags(new Required()),
            (new LongTextField('content', 'content'))->addFlags(new Required(), new AllowHtml()),
        ]);
    }
}
