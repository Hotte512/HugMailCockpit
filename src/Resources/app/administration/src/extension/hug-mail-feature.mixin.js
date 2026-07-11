/**
 * Shared state for the smart bar entry points: modal visibility, the F1
 * feature toggle from the plugin configuration and the ACL gate. The button
 * only controls UI visibility — the backend enforces the privileges on
 * every route regardless.
 */
export default {
    data() {
        return {
            hugMailModalOpen: false,
            hugMailFeatureEnabled: false,
        };
    },

    computed: {
        hugMailButtonVisible() {
            return this.hugMailFeatureEnabled
                && Shopware.Service('acl').can('hug_mail_cockpit.free_sender');
        },
    },

    created() {
        Shopware.Service('systemConfigApiService')
            .getValues('HugMailCockpit.config')
            .then((values) => {
                this.hugMailFeatureEnabled = values['HugMailCockpit.config.freeMailEnabled'] ?? true;
            })
            .catch(() => {
                this.hugMailFeatureEnabled = true;
            });
    },
};
