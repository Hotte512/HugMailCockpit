import template from './hug-mail-compose-modal.html.twig';
import './hug-mail-compose-modal.scss';
import {
    containsTwigSyntax,
    parseAddressList,
    buildRecipientMap,
} from '../../utils/hug-mail-content.utils';

const { Criteria } = Shopware.Data;

/**
 * F1 compose modal (konzept.md §2): free e-mail from order or customer
 * detail. Dual editor (WYSIWYG ⇄ Twig, both write to the same contentHtml),
 * variable picker fed by the actually built Twig context, order documents
 * and uploads as attachments, preview against real data.
 *
 * The mail language is always the order/customer language — displayed here,
 * enforced by the backend.
 */
const hugMailComposeModal = {
    template,

    inject: ['repositoryFactory', 'acl', 'hugMailCockpitApiService'],

    mixins: [Shopware.Mixin.getByName('notification')],

    emits: ['modal-close', 'mail-sent'],

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
        source: {
            type: String,
            required: false,
            default: 'free',
        },
        preselectedDocumentIds: {
            type: Array,
            required: false,
            default: () => [],
        },
    },

    data() {
        return {
            isLoading: true,
            isSending: false,
            recipientEmail: '',
            recipientName: '',
            showCcBcc: false,
            cc: '',
            bcc: '',
            mailTemplateId: null,
            subject: '',
            contentHtml: '',
            editorMode: 'simple',
            variables: {},
            languageId: null,
            languageName: '',
            documents: [],
            selectedDocumentIds: [...this.preselectedDocumentIds],
            uploadedMedia: [],
            uploadTag: 'hug-mail-cockpit-attachment',
            preview: {
                open: false,
                subject: null,
                contentHtml: null,
                errors: [],
            },
        };
    },

    computed: {
        modalTitle() {
            return this.$tc('hug-mail-cockpit.composeModal.title');
        },

        canUseTwigEditor() {
            return this.acl.can('hug_mail_cockpit.twig_editor');
        },

        twigContentWarningVisible() {
            return this.editorMode === 'simple' && containsTwigSyntax(this.contentHtml);
        },

        mailTemplateCriteria() {
            const criteria = new Criteria(1, 100);
            criteria.addAssociation('mailTemplateType');

            return criteria;
        },

        documentRepository() {
            return this.repositoryFactory.create('document');
        },

        languageRepository() {
            return this.repositoryFactory.create('language');
        },

        mailTemplateRepository() {
            return this.repositoryFactory.create('mail_template');
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        async createdComponent() {
            this.isLoading = true;

            try {
                const context = await this.hugMailCockpitApiService.getMailContext({
                    orderId: this.orderId,
                    customerId: this.customerId,
                });

                this.variables = context.variables ?? {};
                this.languageId = context.languageId;
                this.recipientEmail = context.recipientEmail ?? '';
                this.recipientName = context.recipientName ?? '';

                await Promise.all([
                    this.loadLanguageName(),
                    this.loadDocuments(),
                ]);
            } catch (error) {
                this.showApiError(error);
            } finally {
                this.isLoading = false;
            }
        },

        async loadLanguageName() {
            if (!this.languageId) {
                return;
            }

            const language = await this.languageRepository.get(this.languageId, Shopware.Context.api);
            this.languageName = language ? language.name : '';
        },

        async loadDocuments() {
            if (!this.orderId) {
                return;
            }

            const criteria = new Criteria(1, 50);
            criteria.addFilter(Criteria.equals('orderId', this.orderId));
            criteria.addAssociation('documentType');
            criteria.addSorting(Criteria.sort('createdAt', 'DESC'));

            this.documents = await this.documentRepository.search(criteria, Shopware.Context.api);
        },

        documentLabel(document) {
            const typeName = document.documentType ? document.documentType.translated.name : '';
            const number = document.config && document.config.documentNumber
                ? document.config.documentNumber
                : '';

            return `${typeName} ${number}`.trim();
        },

        toggleDocument(documentId, checked) {
            if (checked && !this.selectedDocumentIds.includes(documentId)) {
                this.selectedDocumentIds.push(documentId);

                return;
            }

            if (!checked) {
                this.selectedDocumentIds = this.selectedDocumentIds.filter((id) => id !== documentId);
            }
        },

        onUploadFinished({ targetId }) {
            this.repositoryFactory
                .create('media')
                .get(targetId, Shopware.Context.api)
                .then((media) => {
                    this.uploadedMedia.push({
                        id: targetId,
                        fileName: media ? `${media.fileName}.${media.fileExtension}` : targetId,
                    });
                });
        },

        async onTemplateSelected(mailTemplateId) {
            this.mailTemplateId = mailTemplateId;

            if (!mailTemplateId) {
                return;
            }

            // Load the template copy in the mail language (order/customer
            // language), never the admin language — konzept.md §6.
            const templateContext = { ...Shopware.Context.api };
            if (this.languageId) {
                templateContext.languageId = this.languageId;
            }

            const mailTemplate = await this.mailTemplateRepository.get(mailTemplateId, templateContext);

            if (!mailTemplate) {
                return;
            }

            // Copy, never a live binding: editing here must not change the template.
            this.subject = mailTemplate.translated.subject ?? mailTemplate.subject ?? '';
            this.contentHtml = mailTemplate.translated.contentHtml ?? mailTemplate.contentHtml ?? '';

            if (containsTwigSyntax(this.contentHtml) && this.canUseTwigEditor) {
                this.editorMode = 'twig';
            }
        },

        switchEditorMode(mode) {
            this.editorMode = mode;
        },

        onVariableSelected(expression) {
            if (this.editorMode === 'twig') {
                const aceEditor = this.$refs.codeEditor ? this.$refs.codeEditor.editor : null;

                if (aceEditor) {
                    aceEditor.session.insert(aceEditor.getCursorPosition(), expression);
                    this.contentHtml = aceEditor.getValue();

                    return;
                }
            }

            const tiptap = this.$refs.wysiwygEditor
                && this.$refs.wysiwygEditor.editor
                && this.$refs.wysiwygEditor.editor.commands
                ? this.$refs.wysiwygEditor.editor
                : null;

            if (tiptap) {
                tiptap.commands.insertContent(expression);

                return;
            }

            // Fallback: append at the end so the variable is never lost.
            this.contentHtml = `${this.contentHtml}${expression}`;
        },

        buildPayload() {
            return {
                orderId: this.orderId,
                customerId: this.orderId ? null : this.customerId,
                recipients: buildRecipientMap(this.recipientEmail.trim(), this.recipientName),
                cc: parseAddressList(this.cc),
                bcc: parseAddressList(this.bcc),
                subject: this.subject,
                contentHtml: this.contentHtml,
                mailTemplateId: this.mailTemplateId,
                documentIds: [...this.selectedDocumentIds],
                mediaIds: this.uploadedMedia.map((media) => media.id),
                source: this.source,
            };
        },

        async openPreview() {
            try {
                const result = await this.hugMailCockpitApiService.preview({
                    orderId: this.orderId,
                    customerId: this.orderId ? null : this.customerId,
                    subject: this.subject,
                    contentHtml: this.contentHtml,
                });

                this.preview = {
                    open: true,
                    subject: result.subject,
                    contentHtml: result.contentHtml,
                    errors: result.errors ?? [],
                };
            } catch (error) {
                this.showApiError(error);
            }
        },

        previewErrorTitle(error) {
            if (error.line) {
                return this.$tc('hug-mail-cockpit.composeModal.previewErrorLine', 0, { line: error.line });
            }

            return this.$tc('hug-mail-cockpit.composeModal.previewError');
        },

        onPreviewModalChange(isOpen) {
            if (!isOpen) {
                this.preview = { open: false, subject: null, contentHtml: null, errors: [] };
            }
        },

        async send() {
            if (this.recipientEmail.trim() === '' || this.subject.trim() === '' || this.contentHtml.trim() === '') {
                this.createNotificationError({
                    message: this.$tc('hug-mail-cockpit.composeModal.missingFields'),
                });

                return;
            }

            this.isSending = true;

            try {
                await this.hugMailCockpitApiService.send(this.buildPayload());

                this.createNotificationSuccess({
                    message: this.$tc('hug-mail-cockpit.composeModal.sendSuccess'),
                });

                this.$emit('mail-sent');
                this.close();
            } catch (error) {
                this.showApiError(error);
            } finally {
                this.isSending = false;
            }
        },

        showApiError(error) {
            const message = error && error.response && error.response.data
                && Array.isArray(error.response.data.errors) && error.response.data.errors[0]
                ? error.response.data.errors[0].detail
                : String(error);

            this.createNotificationError({ message });
        },

        onModalChange(isOpen) {
            if (!isOpen) {
                this.close();
            }
        },

        close() {
            this.$emit('modal-close');
        },
    },
};

Shopware.Component.register('hug-mail-compose-modal', hugMailComposeModal);

export default hugMailComposeModal;
