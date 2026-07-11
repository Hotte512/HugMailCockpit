import template from './sw-order-detail-hug.html.twig';
import hugMailFeatureMixin from '../hug-mail-feature.mixin';

/**
 * Smart bar entry point for F1 on the order detail page
 * (block sw_order_detail_actions_slot_smart_bar_actions, verified phase 0).
 */
Shopware.Component.override('sw-order-detail', {
    template,

    mixins: [hugMailFeatureMixin],
});
