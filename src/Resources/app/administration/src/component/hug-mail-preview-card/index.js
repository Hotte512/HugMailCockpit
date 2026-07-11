import template from './hug-mail-preview-card.html.twig';
import './hug-mail-preview-card.scss';

const { Criteria } = Shopware.Data;

/**
 * F4 (konzept.md §5): "test with a real order" card on the mail template
 * detail page. Renders the template leniently against the selected order
 * (missing flow-event variables are reported, not fatal) and can send the
 * rendered result as a test mail — always the rendered content, so the
 * server-side twig policy never blocks and WYSIWYG equals dispatch.
 */
const hugMailPreviewCard = {
    template,

    inject: ['acl', 'hugMailCockpitApiService'],

    mixins: [Shopware.Mixin.getByName('notification')],

    props: {
        mailTemplate: {
            type: Object,
            required: false,
            default: null,
        },
    },

    data() {
        return {
            featureEnabled: true,
            orderId: null,
            recipientEmail: '',
            isLoadingPreview: false,
            isSending: false,
            preview: {
                open: false,
                subject: null,
                contentHtml: null,
                errors: [],
            },
        };
    },

    computed: {
        cardVisible() {
            return this.featureEnabled
                && this.mailTemplate !== null
                && this.acl.can('hug_mail_cockpit.free_sender');
        },

        orderCriteria() {
            const criteria = new Criteria(1, 25);
            criteria.addSorting(Criteria.sort('createdAt', 'DESC'));

            return criteria;
        },

        templateSubject() {
            if (!this.mailTemplate) {
                return '';
            }

            return this.mailTemplate.translated?.subject ?? this.mailTemplate.subject ?? '';
        },

        templateContentHtml() {
            if (!this.mailTemplate) {
                return '';
            }

            return this.mailTemplate.translated?.contentHtml ?? this.mailTemplate.contentHtml ?? '';
        },
    },

    created() {
        Shopware.Service('systemConfigApiService')
            .getValues('HugMailCockpit.config')
            .then((values) => {
                this.featureEnabled = values['HugMailCockpit.config.templatePreviewEnabled'] ?? true;
            })
            .catch(() => {});
    },

    methods: {
        async renderAgainstOrder() {
            return this.hugMailCockpitApiService.preview({
                orderId: this.orderId,
                subject: this.templateSubject,
                contentHtml: this.templateContentHtml,
                lenient: true,
            });
        },

        async openPreview() {
            this.isLoadingPreview = true;

            try {
                const result = await this.renderAgainstOrder();

                this.preview = {
                    open: true,
                    subject: result.subject,
                    contentHtml: result.contentHtml,
                    errors: result.errors ?? [],
                };
            } catch (error) {
                this.showApiError(error);
            } finally {
                this.isLoadingPreview = false;
            }
        },

        async sendTestMail() {
            this.isSending = true;

            try {
                // Render first, send the rendered result (source "preview").
                const rendered = await this.renderAgainstOrder();

                if (rendered.contentHtml === null || rendered.subject === null) {
                    this.createNotificationError({
                        message: rendered.errors && rendered.errors[0]
                            ? rendered.errors[0].message
                            : this.$tc('hug-mail-cockpit.previewCard.renderFailed'),
                    });

                    return;
                }

                const email = this.recipientEmail.trim();
                await this.hugMailCockpitApiService.send({
                    orderId: this.orderId,
                    recipients: { [email]: email },
                    subject: rendered.subject,
                    contentHtml: rendered.contentHtml,
                    mailTemplateId: this.mailTemplate ? this.mailTemplate.id : null,
                    source: 'preview',
                });

                this.createNotificationSuccess({
                    message: this.$tc('hug-mail-cockpit.previewCard.sendTestSuccess'),
                });
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
    },
};

Shopware.Component.register('hug-mail-preview-card', hugMailPreviewCard);

export default hugMailPreviewCard;
