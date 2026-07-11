import template from './hug-mail-tab-content.html.twig';
import './hug-mail-tab-content.scss';

/**
 * Content of the "E-Mails" tab on order and customer detail — the central
 * mail place per entity: history grid (F2) plus the compose entry (F1).
 */
const hugMailTabContent = {
    template,

    inject: ['acl'],

    props: {
        orderId: {
            type: String,
            required: false,
            default: null,
        },
        customerId: {
            type: String,
            required: false,
            default: null,
        },
    },

    data() {
        return {
            composeOpen: false,
            features: {
                freeMailEnabled: true,
                historyEnabled: true,
            },
            historyReloadKey: 0,
            replyPrefill: {
                recipientEmail: null,
                subject: null,
            },
        };
    },

    computed: {
        canCompose() {
            return this.features.freeMailEnabled && this.acl.can('hug_mail_cockpit.free_sender');
        },

        canViewHistory() {
            return this.features.historyEnabled && this.acl.can('hug_mail_cockpit.viewer');
        },
    },

    created() {
        Shopware.Service('systemConfigApiService')
            .getValues('HugMailCockpit.config')
            .then((values) => {
                this.features.freeMailEnabled = values['HugMailCockpit.config.freeMailEnabled'] ?? true;
                this.features.historyEnabled = values['HugMailCockpit.config.historyEnabled'] ?? true;
            })
            .catch(() => {});
    },

    methods: {
        onMailSent() {
            // Force the grid to reload so the sent mail shows up immediately.
            this.historyReloadKey += 1;
        },

        onReply(entry) {
            const receiver = entry && entry.receiver && typeof entry.receiver === 'object'
                ? Object.keys(entry.receiver)[0] ?? null
                : null;
            const subject = entry && typeof entry.subject === 'string' && entry.subject !== ''
                ? (entry.subject.startsWith('Re:') ? entry.subject : `Re: ${entry.subject}`)
                : null;

            this.replyPrefill = { recipientEmail: receiver, subject };
            this.composeOpen = true;
        },

        onComposeClose() {
            this.composeOpen = false;
            this.replyPrefill = { recipientEmail: null, subject: null };
        },
    },
};

Shopware.Component.register('hug-mail-tab-content', hugMailTabContent);

export default hugMailTabContent;
