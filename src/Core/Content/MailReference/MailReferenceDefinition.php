<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Core\Content\MailReference;

use Shopware\Core\Checkout\Document\DocumentDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\User\UserDefinition;

/**
 * Audit/link layer for mails sent through the cockpit (konzept.md §1).
 *
 * Mail contents live in FroshPlatformMailArchive; this entity only records
 * who sent what from which entry point and which document was attached.
 * mail_archive_id is a loose reference (plain id, no association) because
 * MailArchive is an optional runtime dependency.
 */
class MailReferenceDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'hug_mail_reference';

    public const SOURCE_FREE = 'free';
    public const SOURCE_DOCUMENT = 'document';
    public const SOURCE_PREVIEW = 'preview';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return MailReferenceEntity::class;
    }

    public function getCollectionClass(): string
    {
        return MailReferenceCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),

            new IdField('mail_archive_id', 'mailArchiveId'),

            new FkField('order_id', 'orderId', OrderDefinition::class),
            new ReferenceVersionField(OrderDefinition::class, 'order_version_id'),
            new ManyToOneAssociationField('order', 'order_id', OrderDefinition::class, 'id', false),

            new FkField('document_id', 'documentId', DocumentDefinition::class),
            new ManyToOneAssociationField('document', 'document_id', DocumentDefinition::class, 'id', false),

            (new StringField('source', 'source', 32))->addFlags(new Required()),

            new FkField('sent_by_user_id', 'sentByUserId', UserDefinition::class),
            new ManyToOneAssociationField('sentByUser', 'sent_by_user_id', UserDefinition::class, 'id', false),
        ]);
    }
}
