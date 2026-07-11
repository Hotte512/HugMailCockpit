import { mount, flushPromises } from '@vue/test-utils';
import hugMailPreviewCard from '../../../src/Resources/app/administration/src/component/hug-mail-preview-card';

const ORDER_ID = 'a0b1c2d3e4f5a0b1c2d3e4f5a0b1c2d3';

const MAIL_TEMPLATE = {
    id: 'template-1',
    translated: {
        subject: 'Order {{ order.orderNumber }}',
        contentHtml: '<p>{{ order.orderNumber }} — {{ flowVariable }}</p>',
    },
};

function createWrapper({ acl = { can: () => true }, apiOverrides = {} } = {}) {
    const apiService = {
        preview: jest.fn().mockResolvedValue({
            subject: 'Order 10001',
            contentHtml: '<p>10001 — </p>',
            errors: [{ field: 'contentHtml', message: 'Variable "flowVariable" does not exist', line: 1 }],
        }),
        send: jest.fn().mockResolvedValue(null),
        ...apiOverrides,
    };

    const wrapper = mount(hugMailPreviewCard, {
        props: { mailTemplate: MAIL_TEMPLATE },
        global: {
            mocks: {
                $tc: (key) => key,
            },
            provide: {
                acl,
                hugMailCockpitApiService: apiService,
            },
            stubs: {
                'mt-card': { template: '<div class="mt-card-stub"><slot /></div>' },
                'mt-button': {
                    template: '<button class="mt-button-stub" :disabled="disabled" @click="$emit(\'click\')"><slot /></button>',
                    props: ['disabled', 'variant', 'isLoading', 'size'],
                },
                'mt-text-field': {
                    template: '<input class="mt-text-field-stub" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
                    props: ['modelValue', 'label', 'helpText'],
                },
                'mt-banner': { template: '<div class="mt-banner-stub"><slot /></div>' },
                'mt-modal-root': { template: '<div class="mt-modal-root-stub"><slot /></div>' },
                'mt-modal': { template: '<div class="mt-modal-stub"><slot /></div>' },
                'sw-entity-single-select': true,
            },
        },
    });

    return { wrapper, apiService };
}

describe('hug-mail-preview-card', () => {
    beforeAll(() => {
        Shopware.Service = (name) => ({
            acl: { can: () => true },
            systemConfigApiService: { getValues: () => Promise.resolve({}) },
        }[name]);
    });

    it('renders the card (snapshot)', async () => {
        const { wrapper } = createWrapper();
        await flushPromises();

        expect(wrapper.html()).toMatchSnapshot();
    });

    it('previews leniently against the selected order', async () => {
        const { wrapper, apiService } = createWrapper();
        await flushPromises();

        wrapper.vm.orderId = ORDER_ID;
        await wrapper.vm.openPreview();

        expect(apiService.preview).toHaveBeenCalledWith({
            orderId: ORDER_ID,
            subject: 'Order {{ order.orderNumber }}',
            contentHtml: '<p>{{ order.orderNumber }} — {{ flowVariable }}</p>',
            lenient: true,
        });
        expect(wrapper.vm.preview.open).toBe(true);
        expect(wrapper.vm.preview.contentHtml).toBe('<p>10001 — </p>');
        expect(wrapper.vm.preview.errors).toHaveLength(1);
    });

    it('sends the rendered result as test mail', async () => {
        const { wrapper, apiService } = createWrapper();
        await flushPromises();

        wrapper.vm.orderId = ORDER_ID;
        wrapper.vm.recipientEmail = 'test@example.com';
        await wrapper.vm.sendTestMail();

        expect(apiService.send).toHaveBeenCalledWith({
            orderId: ORDER_ID,
            recipients: { 'test@example.com': 'test@example.com' },
            subject: 'Order 10001',
            contentHtml: '<p>10001 — </p>',
            mailTemplateId: 'template-1',
            source: 'preview',
        });
    });

    it('does not send when rendering fails completely', async () => {
        const { wrapper, apiService } = createWrapper({
            apiOverrides: {
                preview: jest.fn().mockResolvedValue({
                    subject: null,
                    contentHtml: null,
                    errors: [{ field: 'contentHtml', message: 'Syntax error', line: 2 }],
                }),
            },
        });
        await flushPromises();

        wrapper.vm.orderId = ORDER_ID;
        wrapper.vm.recipientEmail = 'test@example.com';
        await wrapper.vm.sendTestMail();

        expect(apiService.send).not.toHaveBeenCalled();
    });

    it('is hidden without the free_sender privilege', async () => {
        const { wrapper } = createWrapper({
            acl: { can: () => false },
        });
        await flushPromises();

        expect(wrapper.find('.mt-card-stub').exists()).toBe(false);
    });
});
