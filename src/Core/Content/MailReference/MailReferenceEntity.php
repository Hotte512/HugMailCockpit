<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Core\Content\MailReference;

use Shopware\Core\Checkout\Document\DocumentEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\User\UserEntity;

class MailReferenceEntity extends Entity
{
    use EntityIdTrait;

    protected ?string $mailArchiveId = null;

    protected ?string $orderId = null;

    protected ?string $orderVersionId = null;

    protected ?OrderEntity $order = null;

    protected ?string $documentId = null;

    protected ?DocumentEntity $document = null;

    protected string $source;

    protected ?string $sentByUserId = null;

    protected ?UserEntity $sentByUser = null;

    public function getMailArchiveId(): ?string
    {
        return $this->mailArchiveId;
    }

    public function setMailArchiveId(?string $mailArchiveId): void
    {
        $this->mailArchiveId = $mailArchiveId;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function setOrderId(?string $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function getOrderVersionId(): ?string
    {
        return $this->orderVersionId;
    }

    public function setOrderVersionId(?string $orderVersionId): void
    {
        $this->orderVersionId = $orderVersionId;
    }

    public function getOrder(): ?OrderEntity
    {
        return $this->order;
    }

    public function setOrder(?OrderEntity $order): void
    {
        $this->order = $order;
    }

    public function getDocumentId(): ?string
    {
        return $this->documentId;
    }

    public function setDocumentId(?string $documentId): void
    {
        $this->documentId = $documentId;
    }

    public function getDocument(): ?DocumentEntity
    {
        return $this->document;
    }

    public function setDocument(?DocumentEntity $document): void
    {
        $this->document = $document;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): void
    {
        $this->source = $source;
    }

    public function getSentByUserId(): ?string
    {
        return $this->sentByUserId;
    }

    public function setSentByUserId(?string $sentByUserId): void
    {
        $this->sentByUserId = $sentByUserId;
    }

    public function getSentByUser(): ?UserEntity
    {
        return $this->sentByUser;
    }

    public function setSentByUser(?UserEntity $sentByUser): void
    {
        $this->sentByUser = $sentByUser;
    }
}
