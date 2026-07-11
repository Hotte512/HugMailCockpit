import template from './hug-mail-history-grid.html.twig';
import './hug-mail-history-grid.scss';

/**
 * F2 history grid (konzept.md §3): reads frosh_mail_archive through the
 * plugin's history endpoint. Shows a hint instead when MailArchive is not
 * installed (404 from the runtime guard).
 */
const hugMailHistoryGrid = {
    template,

    inject: ['hugMailCockpitApiService'],

    emits: ['reply'],

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
            isLoading: true,
            archiveUnavailable: false,
            entries: [],
            detailEntry: null,
        };
    },

    computed: {
        columns() {
            return [
                { property: 'createdAt', label: this.$tc('hug-mail-cockpit.history.columnDate'), sortable: false },
                { property: 'subject', label: this.$tc('hug-mail-cockpit.history.columnSubject'), sortable: false },
                { property: 'receiver', label: this.$tc('hug-mail-cockpit.history.columnReceiver'), sortable: false },
                { property: 'transportState', label: this.$tc('hug-mail-cockpit.history.columnState'), sortable: false },
                { property: 'attachmentCount', label: '📎', sortable: false },
            ];
        },

        dateFilter() {
            return Shopware.Filter.getByName('date');
        },
    },

    created() {
        this.loadHistory();
    },

    methods: {
        async loadHistory() {
            this.isLoading = true;

            try {
                const result = await this.hugMailCockpitApiService.getHistory({
                    orderId: this.orderId,
                    customerId: this.orderId ? null : this.customerId,
                });

                this.entries = result.entries ?? [];
            } catch (error) {
                if (error && error.response && error.response.status === 404) {
                    this.archiveUnavailable = true;
                } else {
                    throw error;
                }
            } finally {
                this.isLoading = false;
            }
        },

        formatAddresses(addressMap) {
            if (!addressMap || typeof addressMap !== 'object') {
                return '';
            }

            return Object.keys(addressMap).join(', ');
        },

        openDetail(item) {
            this.detailEntry = item;
        },

        onDetailModalChange(isOpen) {
            if (!isOpen) {
                this.detailEntry = null;
            }
        },
    },
};

Shopware.Component.register('hug-mail-history-grid', hugMailHistoryGrid);

export default hugMailHistoryGrid;
