<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Exception;

use Shopware\Core\Framework\HttpException;
use Symfony\Component\HttpFoundation\Response;

class MailCockpitException extends HttpException
{
    final public const ORDER_NOT_FOUND = 'HUG_MAIL_COCKPIT__ORDER_NOT_FOUND';
    final public const CUSTOMER_NOT_FOUND = 'HUG_MAIL_COCKPIT__CUSTOMER_NOT_FOUND';
    final public const DOCUMENT_NOT_FOUND = 'HUG_MAIL_COCKPIT__DOCUMENT_NOT_FOUND';
    final public const MISSING_TARGET = 'HUG_MAIL_COCKPIT__MISSING_TARGET';
    final public const MISSING_RECIPIENTS = 'HUG_MAIL_COCKPIT__MISSING_RECIPIENTS';
    final public const INVALID_SOURCE = 'HUG_MAIL_COCKPIT__INVALID_SOURCE';
    final public const MAIL_DISPATCH_FAILED = 'HUG_MAIL_COCKPIT__MAIL_DISPATCH_FAILED';
    final public const TWIG_EDITOR_PRIVILEGE_REQUIRED = 'HUG_MAIL_COCKPIT__TWIG_EDITOR_PRIVILEGE_REQUIRED';
    final public const MAIL_ARCHIVE_NOT_AVAILABLE = 'HUG_MAIL_COCKPIT__MAIL_ARCHIVE_NOT_AVAILABLE';
    final public const INVALID_PAYLOAD = 'HUG_MAIL_COCKPIT__INVALID_PAYLOAD';
    final public const MAIL_TEMPLATE_NOT_FOUND = 'HUG_MAIL_COCKPIT__MAIL_TEMPLATE_NOT_FOUND';
    final public const DOCUMENTS_REQUIRE_ORDER = 'HUG_MAIL_COCKPIT__DOCUMENTS_REQUIRE_ORDER';
    final public const MEDIA_ATTACHMENT_NOT_ALLOWED = 'HUG_MAIL_COCKPIT__MEDIA_ATTACHMENT_NOT_ALLOWED';

    public static function orderNotFound(string $orderId): self
    {
        return new self(
            Response::HTTP_NOT_FOUND,
            self::ORDER_NOT_FOUND,
            'Order "{{ orderId }}" not found.',
            ['orderId' => $orderId],
        );
    }

    public static function customerNotFound(string $customerId): self
    {
        return new self(
            Response::HTTP_NOT_FOUND,
            self::CUSTOMER_NOT_FOUND,
            'Customer "{{ customerId }}" not found.',
            ['customerId' => $customerId],
        );
    }

    public static function documentNotFound(string $documentId): self
    {
        return new self(
            Response::HTTP_NOT_FOUND,
            self::DOCUMENT_NOT_FOUND,
            'Document "{{ documentId }}" could not be read as mail attachment.',
            ['documentId' => $documentId],
        );
    }

    public static function missingTarget(): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::MISSING_TARGET,
            'Either an order id or a customer id must be provided.',
        );
    }

    public static function missingRecipients(): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::MISSING_RECIPIENTS,
            'At least one recipient must be provided.',
        );
    }

    public static function invalidSource(string $source): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::INVALID_SOURCE,
            'Invalid mail reference source "{{ source }}".',
            ['source' => $source],
        );
    }

    public static function mailDispatchFailed(): self
    {
        return new self(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            self::MAIL_DISPATCH_FAILED,
            'The mail could not be dispatched. Check the mail configuration and the logs.',
        );
    }

    public static function twigEditorPrivilegeRequired(): self
    {
        return new self(
            Response::HTTP_FORBIDDEN,
            self::TWIG_EDITOR_PRIVILEGE_REQUIRED,
            'The content contains Twig expressions that require the "hug_mail_cockpit.twig_editor" privilege.',
        );
    }

    public static function mailTemplateNotFound(string $mailTemplateId): self
    {
        return new self(
            Response::HTTP_NOT_FOUND,
            self::MAIL_TEMPLATE_NOT_FOUND,
            'Mail template "{{ mailTemplateId }}" not found.',
            ['mailTemplateId' => $mailTemplateId],
        );
    }

    public static function documentsRequireOrder(): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::DOCUMENTS_REQUIRE_ORDER,
            'Document attachments can only be sent from an order context.',
        );
    }

    public static function mediaAttachmentNotAllowed(string $mediaId): self
    {
        return new self(
            Response::HTTP_FORBIDDEN,
            self::MEDIA_ATTACHMENT_NOT_ALLOWED,
            'Media "{{ mediaId }}" is not an allowed mail attachment. Only files uploaded to the Mail-Cockpit attachment folder may be attached.',
            ['mediaId' => $mediaId],
        );
    }

    public static function invalidPayload(string $field): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::INVALID_PAYLOAD,
            'Invalid request payload: field "{{ field }}" is missing or malformed.',
            ['field' => $field],
        );
    }

    public static function mailArchiveNotAvailable(): self
    {
        return new self(
            Response::HTTP_NOT_FOUND,
            self::MAIL_ARCHIVE_NOT_AVAILABLE,
            'FroshPlatformMailArchive >= 3.6 is not installed or inactive; the mail history is not available.',
        );
    }
}
