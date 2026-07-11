import template from './sw-customer-detail-hug.html.twig';
import hugMailFeatureMixin from '../hug-mail-feature.mixin';

/**
 * Smart bar entry point for F1 on the customer detail page
 * (block sw_customer_detail_actions, verified phase 0).
 */
Shopware.Component.override('sw-customer-detail', {
    template,

    mixins: [hugMailFeatureMixin],
});
