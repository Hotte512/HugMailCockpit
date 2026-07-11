import template from './sw-mail-template-detail-hug.html.twig';

/**
 * F4 entry point: "test with real order" card below the basic info card on
 * the mail template detail page (no dedicated extension block exists there,
 * verified phase 0 — extending via {% parent %}).
 */
Shopware.Component.override('sw-mail-template-detail', {
    template,
});
