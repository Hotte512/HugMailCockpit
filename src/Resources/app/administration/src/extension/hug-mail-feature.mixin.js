/**
 * Visibility of the "E-Mails" tab on order/customer detail: at least one
 * cockpit feature (F1 compose or F2 history) must be enabled AND the user
 * needs the matching privilege. The backend enforces the privileges on every
 * route regardless — this only controls UI visibility.
 */
export default {
    data() {
        return {
            hugMailFeatures: {
                freeMailEnabled: true,
                historyEnabled: true,
            },
            hugMailCount: 0,
        };
    },

    computed: {
        hugMailTabVisible() {
            const acl = Shopware.Service('acl');

            const canCompose = this.hugMailFeatures.freeMailEnabled
                && acl.can('hug_mail_cockpit.free_sender');
            const canView = this.hugMailFeatures.historyEnabled
                && acl.can('hug_mail_cockpit.viewer');

            return canCompose || canView;
        },

        hugMailTabLabel() {
            const label = this.$tc('hug-mail-cockpit.tab.label');

            return this.hugMailCount > 0 ? `${label} (${this.hugMailCount})` : label;
        },
    },

    created() {
        Shopware.Service('systemConfigApiService')
            .getValues('HugMailCockpit.config')
            .then((values) => {
                this.hugMailFeatures.freeMailEnabled = values['HugMailCockpit.config.freeMailEnabled'] ?? true;
                this.hugMailFeatures.historyEnabled = values['HugMailCockpit.config.historyEnabled'] ?? true;
            })
            .catch(() => {});

        this.hugLoadMailCount();
    },

    methods: {
        hugLoadMailCount() {
            if (!Shopware.Service('acl').can('hug_mail_cockpit.viewer')) {
                return;
            }

            const id = this.$route.params.id;
            if (!id) {
                return;
            }

            const isOrderPage = typeof this.$route.name === 'string' && this.$route.name.startsWith('sw.order.');

            Shopware.Service('hugMailCockpitApiService')
                .getHistoryCount(isOrderPage ? { orderId: id } : { customerId: id })
                .then((total) => {
                    this.hugMailCount = total;
                })
                .catch(() => {});
        },
    },
};
