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
    },

    created() {
        Shopware.Service('systemConfigApiService')
            .getValues('HugMailCockpit.config')
            .then((values) => {
                this.hugMailFeatures.freeMailEnabled = values['HugMailCockpit.config.freeMailEnabled'] ?? true;
                this.hugMailFeatures.historyEnabled = values['HugMailCockpit.config.historyEnabled'] ?? true;
            })
            .catch(() => {});
    },
};
