<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Core\Content\TextSnippet;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class TextSnippetEntity extends Entity
{
    use EntityIdTrait;

    protected string $name;

    protected string $content;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }
}
