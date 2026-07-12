import { mount, flushPromises } from '@vue/test-utils';
import hugMailComposeModal from '../../../src/Resources/app/administration/src/component/hug-mail-compose-modal';

const ORDER_ID = 'a0b1c2d3e4f5a0b1c2d3e4f5a0b1c2d3';

const slotStub = (name) => ({
    name,
    template: `<div class="${name}-stub"><slot /><slot name="footer" /></div>`,
});

function createWrapper({ acl = { can: () => true }, apiOverrides = {}, props = {} } = {}) {
    const apiService = {
        getMailContext: jest.fn().mockResolvedValue({
            variables: { order: { orderNumber: '10001', amountTotal: '99.9' } },
            languageId: 'lang-id',
            salesChannelId: 'sales-channel-id',
            recipientEmail: 'max@example.com',
            recipientName: 'Max Mustermann',
        }),
        renderTemplate: jest.fn().mockResolvedValue({
            subject: 'Rendered subject 10001',
            contentHtml: '<p>Rendered body</p>',
            errors: [],
        }),
        preview: jest.fn().mockResolvedValue({ subject: 'S', contentHtml: '<p>x</p>', errors: [] }),
        send: jest.fn().mockResolvedValue(null),
        ...apiOverrides,
    };

    const snippetRepository = {
        get: jest.fn().mockResolvedValue({ id: 'snippet-1', name: 'Gruß', content: '<p>Viele Grüße</p>' }),
        create: jest.fn().mockReturnValue({}),
        save: jest.fn().mockResolvedValue(null),
    };

    const repositoryFactory = {
        create: (entityName) => (entityName === 'hug_mail_text_snippet'
            ? snippetRepository
            : {
                search: jest.fn().mockResolvedValue([]),
                get: jest.fn().mockResolvedValue({ name: 'Deutsch' }),
            }),
    };

    const wrapper = mount(hugMailComposeModal, {
        props: {
            orderId: ORDER_ID,
            ...props,
        },
        global: {
            mocks: {
                $tc: (key) => key,
            },
            provide: {
                repositoryFactory,
                acl,
                hugMailCockpitApiService: apiService,
            },
            stubs: {
                'mt-modal-root': { template: '<div class="mt-modal-root-stub"><slot /></div>' },
                'mt-modal': slotStub('mt-modal'),
                'mt-button': {
                    template: '<button class="mt-button-stub" @click="$emit(\'click\')"><slot /></button>',
                },
                'mt-text-field': {
                    template: '<label class="mt-text-field-stub">{{ label }}<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" /></label>',
                    props: ['modelValue', 'label'],
                },
                'mt-banner': { template: '<div class="mt-banner-stub"><slot /></div>' },
                'mt-checkbox': true,
                'mt-text-editor': {
                    template: '<textarea class="mt-text-editor-stub" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)"></textarea>',
                    props: ['modelValue'],
                },
                'sw-code-editor': true,
                'sw-loader': true,
                'sw-entity-single-select': true,
                'sw-media-upload-v2': true,
                'sw-upload-listener': true,
                'hug-mail-variable-picker': true,
            },
        },
    });

    return { wrapper, apiService, snippetRepository };
}

describe('hug-mail-compose-modal', () => {
    it('renders the compose form after loading the mail context (snapshot)', async () => {
        const { wrapper } = createWrapper();
        await flushPromises();

        expect(wrapper.html()).toMatchSnapshot();
    });

    it('prefills the recipient from the mail context', async () => {
        const { wrapper, apiService } = createWrapper();
        await flushPromises();

        expect(apiService.getMailContext).toHaveBeenCalledWith({ orderId: ORDER_ID, customerId: null });
        expect(wrapper.vm.recipientEmail).toBe('max@example.com');
        expect(wrapper.vm.languageName).toBe('Deutsch');
    });

    it('hides the twig editor toggle without the twig_editor privilege', async () => {
        const { wrapper } = createWrapper({
            acl: { can: (privilege) => privilege !== 'hug_mail_cockpit.twig_editor' },
        });
        await flushPromises();

        const buttons = wrapper.findAll('.hug-mail-compose-modal__editor-toggle .mt-button-stub');
        expect(buttons).toHaveLength(1);
    });

    it('builds the send payload from the form state', async () => {
        const { wrapper } = createWrapper();
        await flushPromises();

        wrapper.vm.subject = 'Order {{ order.orderNumber }}';
        wrapper.vm.contentHtml = '<p>Hello</p>';
        wrapper.vm.cc = 'cc@example.com';
        wrapper.vm.selectedDocumentIds = ['doc-1'];
        wrapper.vm.uploadedMedia = [{ id: 'media-1', fileName: 'flyer.pdf' }];

        expect(wrapper.vm.buildPayload()).toEqual({
            orderId: ORDER_ID,
            customerId: null,
            recipients: { 'max@example.com': 'Max Mustermann' },
            cc: { 'cc@example.com': 'cc@example.com' },
            bcc: {},
            subject: 'Order {{ order.orderNumber }}',
            contentHtml: '<p>Hello</p>',
            mailTemplateId: null,
            documentIds: ['doc-1'],
            mediaIds: ['media-1'],
            source: 'free',
        });
    });

    it('sends the mail and emits mail-sent + modal-close', async () => {
        const { wrapper, apiService } = createWrapper();
        await flushPromises();

        wrapper.vm.subject = 'S';
        wrapper.vm.contentHtml = '<p>x</p>';
        await wrapper.vm.send();

        expect(apiService.send).toHaveBeenCalledTimes(1);
        expect(wrapper.emitted('mail-sent')).toHaveLength(1);
        expect(wrapper.emitted('modal-close')).toHaveLength(1);
    });

    it('renders the selected template server-side and adopts the result', async () => {
        const { wrapper, apiService } = createWrapper();
        await flushPromises();

        await wrapper.vm.onTemplateSelected('template-id');

        expect(apiService.renderTemplate).toHaveBeenCalledWith({
            mailTemplateId: 'template-id',
            orderId: ORDER_ID,
            customerId: null,
        });
        expect(wrapper.vm.subject).toBe('Rendered subject 10001');
        expect(wrapper.vm.contentHtml).toBe('<p>Rendered body</p>');
    });

    it('applies the reply prefill over the context defaults', async () => {
        const { wrapper } = createWrapper({
            props: {
                initialRecipientEmail: 'reply-to@example.com',
                initialSubject: 'Re: Your invoice',
            },
        });
        await flushPromises();

        expect(wrapper.vm.recipientEmail).toBe('reply-to@example.com');
        expect(wrapper.vm.subject).toBe('Re: Your invoice');
    });

    it('starts with tiptap-stable empty content', async () => {
        const { wrapper } = createWrapper();
        await flushPromises();

        expect(wrapper.vm.contentHtml).toBe('<p></p>');
    });

    it('inserts a selected text snippet into the content', async () => {
        const { wrapper } = createWrapper();
        await flushPromises();

        wrapper.vm.contentHtml = '<p>Hallo</p>';
        await wrapper.vm.onSnippetSelected('snippet-1');

        // jsdom has no editor refs — the fallback appends the snippet.
        expect(wrapper.vm.contentHtml).toBe('<p>Hallo</p><p>Viele Grüße</p>');
        expect(wrapper.vm.snippetPickerKey).toBe(1);
    });

    it('saves the current content as a new text snippet', async () => {
        const { wrapper, snippetRepository } = createWrapper();
        await flushPromises();

        wrapper.vm.contentHtml = '<p>Standardantwort</p>';
        wrapper.vm.saveSnippetDialogOpen = true;
        wrapper.vm.saveSnippetName = ' Meine Antwort ';
        await wrapper.vm.saveAsSnippet();

        expect(snippetRepository.save).toHaveBeenCalledWith(
            expect.objectContaining({ name: 'Meine Antwort', content: '<p>Standardantwort</p>' }),
            expect.anything(),
        );
        expect(wrapper.vm.saveSnippetDialogOpen).toBe(false);
        expect(wrapper.vm.snippetPickerKey).toBe(1);
    });

    it('does not save a snippet without a name', async () => {
        const { wrapper, snippetRepository } = createWrapper();
        await flushPromises();

        wrapper.vm.saveSnippetName = '   ';
        await wrapper.vm.saveAsSnippet();

        expect(snippetRepository.save).not.toHaveBeenCalled();
    });

    it('ignores an empty snippet selection', async () => {
        const { wrapper } = createWrapper();
        await flushPromises();

        wrapper.vm.contentHtml = '<p>Hallo</p>';
        await wrapper.vm.onSnippetSelected(null);

        expect(wrapper.vm.contentHtml).toBe('<p>Hallo</p>');
        expect(wrapper.vm.snippetPickerKey).toBe(0);
    });

    it('shows the twig warning in simple mode when content contains twig blocks', async () => {
        const { wrapper } = createWrapper();
        await flushPromises();

        wrapper.vm.contentHtml = '{% if order %}x{% endif %}';
        await wrapper.vm.$nextTick();

        expect(wrapper.find('.mt-banner-stub').exists()).toBe(true);
    });
});
