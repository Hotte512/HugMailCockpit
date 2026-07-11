import template from './sw-order-document-card-hug.html.twig';
import { resolveDocumentTemplate } from '../../utils/hug-mail-content.utils';

/**
 * F3 (konzept.md §4): "send by e-mail" context action + multi-select bulk
 * send on the order document grid. Both open the F1 compose modal with the
 * documents preselected and the template resolved from the plugin's
 * documentType => mailTemplate mapping.
 */
Shopware.Component.override('sw-order-document-card', {
    template,

    data() {
        return {
            hugSendModalOpen: false,
            hugPreselectedDocumentIds: [],
            hugPreselectedTemplateId: null,
            hugSelectedDocuments: {},
            hugDocumentMailEnabled: true,
            hugTemplateMapping: {},
        };
    },

    computed: {
        hugDocumentMailVisible() {
            return this.hugDocumentMailEnabled
                && Shopware.Service('acl').can('hug_mail_cockpit.sender');
        },

        hugSelectedCount() {
            return Object.keys(this.hugSelectedDocuments).length;
        },
    },

    created() {
        Shopware.Service('systemConfigApiService')
            .getValues('HugMailCockpit.config')
            .then((values) => {
                this.hugDocumentMailEnabled = values['HugMailCockpit.config.documentMailEnabled'] ?? true;

                const mapping = values['HugMailCockpit.config.documentTypeTemplateMapping'];
                if (mapping && typeof mapping === 'object') {
                    this.hugTemplateMapping = mapping;
                }
            })
            .catch(() => {});
    },

    methods: {
        onHugSelectionChange(selection) {
            this.hugSelectedDocuments = selection ?? {};
        },

        hugOpenSendModal(documents) {
            this.hugPreselectedDocumentIds = documents.map((document) => document.id);
            this.hugPreselectedTemplateId = resolveDocumentTemplate(this.hugTemplateMapping, documents);
            this.hugSendModalOpen = true;
        },

        hugOpenSendModalForSelection() {
            // One mail with n attachments — never n mails (konzept.md §4).
            this.hugOpenSendModal(Object.values(this.hugSelectedDocuments));
        },

        onHugModalClose() {
            this.hugSendModalOpen = false;
            this.hugPreselectedDocumentIds = [];
            this.hugPreselectedTemplateId = null;
        },
    },
});
