<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Core\Content\MailReference;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<MailReferenceEntity>
 */
class MailReferenceCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return MailReferenceEntity::class;
    }
}
