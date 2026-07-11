/**
 * Registers the child routes for the "E-Mails" tabs on order and customer
 * detail. The tab items themselves are injected via the template overrides
 * in src/extension (blocks verified in phase 0).
 */
Shopware.Component.register('hug-mail-order-tab', {
    template: '<hug-mail-tab-content :order-id="$route.params.id" />',
});

Shopware.Component.register('hug-mail-customer-tab', {
    template: '<hug-mail-tab-content :customer-id="$route.params.id" />',
});

Shopware.Module.register('hug-mail-cockpit', {
    type: 'plugin',
    name: 'hug-mail-cockpit',
    title: 'hug-mail-cockpit.tab.cardTitle',
    description: 'hug-mail-cockpit.tab.cardTitle',

    routeMiddleware(next, currentRoute) {
        if (currentRoute.name === 'sw.order.detail' && Array.isArray(currentRoute.children)) {
            currentRoute.children.push({
                name: 'sw.order.detail.hugMails',
                path: '/sw/order/detail/:id/hug-mails',
                component: 'hug-mail-order-tab',
                meta: {
                    parentPath: 'sw.order.index',
                    privilege: 'order.viewer',
                },
            });
        }

        if (currentRoute.name === 'sw.customer.detail' && Array.isArray(currentRoute.children)) {
            currentRoute.children.push({
                name: 'sw.customer.detail.hugMails',
                path: '/sw/customer/detail/:id/hug-mails',
                component: 'hug-mail-customer-tab',
                meta: {
                    parentPath: 'sw.customer.index',
                    privilege: 'customer.viewer',
                },
            });
        }

        next(currentRoute);
    },
});
