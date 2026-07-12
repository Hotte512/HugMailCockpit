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
        preselectedMailTemplateId: {
            type: String,
            required: false,
            default: null,
        },
        initialRecipientEmail: {
            type: String,
            required: false,
            default: null,
        },
        initialSubject: {
            type: String,
            required: false,
            default: null,
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
            // TipTap round-trips '' to '<p></p>' — starting with the stable
            // form keeps the mt-text-editor out of its read-only gate.
            contentHtml: '<p></p>',
            editorMode: 'simple',
            variables: {},
            languageId: null,
            languageName: '',
            documents: [],
            selectedDocumentIds: [...this.preselectedDocumentIds],
            uploadedMedia: [],
            uploadTag: 'hug-mail-cockpit-attachment',
            snippetPickerKey: 0,
            saveSnippetDialogOpen: false,
            saveSnippetName: '',
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

        canSaveSnippet() {
            return this.acl.can('hug_mail_text_snippet:create');
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

                // F3: document context menu preselects the mapped template.
                if (this.preselectedMailTemplateId) {
                    await this.onTemplateSelected(this.preselectedMailTemplateId);
                }

                // F2 "compose reply": explicit prefill wins over context data.
                if (this.initialRecipientEmail) {
                    this.recipientEmail = this.initialRecipientEmail;
                }

                if (this.initialSubject) {
                    this.subject = this.initialSubject;
                }
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

            // "Render, then edit": the backend renders the template against
            // the real order/customer data in the mail language. The user
            // edits the final result — no Twig reaches the editor.
            try {
                const result = await this.hugMailCockpitApiService.renderTemplate({
                    mailTemplateId,
                    orderId: this.orderId,
                    customerId: this.orderId ? null : this.customerId,
                });

                if (Array.isArray(result.errors) && result.errors.length > 0) {
                    this.createNotificationError({ message: result.errors[0].message });

                    return;
                }

                this.subject = result.subject ?? '';
                this.contentHtml = result.contentHtml ?? '<p></p>';
            } catch (error) {
                this.showApiError(error);
            }
        },

        switchEditorMode(mode) {
            this.editorMode = mode;
        },

        onVariableSelected(expression) {
            this.insertIntoEditor(expression);
        },

        async onSnippetSelected(snippetId) {
            if (!snippetId) {
                return;
            }

            const snippet = await this.repositoryFactory
                .create('hug_mail_text_snippet')
                .get(snippetId, Shopware.Context.api);

            if (snippet && snippet.content) {
                this.insertIntoEditor(snippet.content);
            }

            // Remount the select so it is ready for the next insertion.
            this.snippetPickerKey += 1;
        },

        insertIntoEditor(content) {
            if (this.editorMode === 'twig') {
                const aceEditor = this.$refs.codeEditor ? this.$refs.codeEditor.editor : null;

                if (aceEditor) {
                    aceEditor.session.insert(aceEditor.getCursorPosition(), content);
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
                // focus() restores the last selection so the content lands at
                // the cursor position instead of a new block at the end.
                tiptap.chain().focus().insertContent(content).run();

                return;
            }

            // Fallback: append at the end so the content is never lost.
            this.contentHtml = `${this.contentHtml}${content}`;
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

        async saveAsSnippet() {
            const name = this.saveSnippetName.trim();

            if (name === '') {
                return;
            }

            try {
                const repository = this.repositoryFactory.create('hug_mail_text_snippet');
                const entity = repository.create(Shopware.Context.api);
                entity.name = name;
                entity.content = this.contentHtml;
                await repository.save(entity, Shopware.Context.api);

                this.saveSnippetDialogOpen = false;
                this.saveSnippetName = '';
                // Remount the picker so the new snippet appears immediately.
                this.snippetPickerKey += 1;

                this.createNotificationSuccess({
                    message: this.$tc('hug-mail-cockpit.composeModal.saveAsSnippetSuccess'),
                });
            } catch (error) {
                this.showApiError(error);
            }
        },

        onSaveSnippetDialogChange(isOpen) {
            if (!isOpen) {
                this.saveSnippetDialogOpen = false;
                this.saveSnippetName = '';
            }
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
