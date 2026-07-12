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
            selectedEntry: null,
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

    mounted() {
        // sw-data-grid has no row-click event — delegate on the component
        // root and resolve the entry via the row index class. Single click
        // fills the inline preview pane, double click opens the modal.
        this.$el.addEventListener('click', this.onGridClick);
        this.$el.addEventListener('dblclick', this.onGridDoubleClick);
    },

    beforeUnmount() {
        this.$el.removeEventListener('click', this.onGridClick);
        this.$el.removeEventListener('dblclick', this.onGridDoubleClick);
    },

    methods: {
        resolveRowEntry(event) {
            const row = event.target.closest('.sw-data-grid__body .sw-data-grid__row');

            if (!row || !this.$el.contains(row)) {
                return null;
            }

            const match = row.className.match(/sw-data-grid__row--(\d+)/);

            return match ? this.entries[Number(match[1])] ?? null : null;
        },

        onGridClick(event) {
            const entry = this.resolveRowEntry(event);

            if (entry) {
                this.selectedEntry = entry;
            }
        },

        onGridDoubleClick(event) {
            const entry = this.resolveRowEntry(event);

            if (entry) {
                this.detailEntry = entry;
            }
        },
        async loadHistory() {
            this.isLoading = true;

            try {
                const result = await this.hugMailCockpitApiService.getHistory({
                    orderId: this.orderId,
                    customerId: this.orderId ? null : this.customerId,
                });

                this.entries = result.entries ?? [];
                // Preview pane starts with the most recent mail.
                this.selectedEntry = this.entries[0] ?? null;
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

        openInMailArchive(item) {
            // EML download and resend live in the MailArchive module — deep
            // link instead of rebuilding them (konzept.md §3).
            this.$router.push({ name: 'frosh.mail.archive.detail', params: { id: item.id } });
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
