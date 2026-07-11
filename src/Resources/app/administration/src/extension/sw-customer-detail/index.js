import template from './sw-customer-detail-hug.html.twig';
import hugMailFeatureMixin from '../hug-mail-feature.mixin';

/**
 * "E-Mails" tab entry point on the customer detail page
 * (extension block sw_customer_detail_content_tab_after, verified phase 0).
 */
Shopware.Component.override('sw-customer-detail', {
    template,

    mixins: [hugMailFeatureMixin],
});
