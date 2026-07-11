import template from './sw-order-detail-hug.html.twig';
import hugMailFeatureMixin from '../hug-mail-feature.mixin';

/**
 * "E-Mails" tab entry point on the order detail page
 * (extension block sw_order_detail_content_tabs_extension, verified phase 0).
 */
Shopware.Component.override('sw-order-detail', {
    template,

    mixins: [hugMailFeatureMixin],
});
