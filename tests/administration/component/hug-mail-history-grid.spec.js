import { mount, flushPromises } from '@vue/test-utils';
import hugMailHistoryGrid from '../../../src/Resources/app/administration/src/component/hug-mail-history-grid';

const ORDER_ID = 'a0b1c2d3e4f5a0b1c2d3e4f5a0b1c2d3';

const ENTRIES = [
    {
        id: 'entry-1',
        subject: 'Your invoice',
        receiver: { 'max@example.com': 'Max' },
        transportState: 'sent',
        mailTemplateId: null,
        htmlText: '<p>Body</p>',
        attachmentCount: 1,
        createdAt: '2026-07-01T10:00:00+00:00',
    },
];

function createWrapper({ apiOverrides = {} } = {}) {
    const apiService = {
        getHistory: jest.fn().mockResolvedValue({ entries: ENTRIES, total: 1 }),
        ...apiOverrides,
    };

    const wrapper = mount(hugMailHistoryGrid, {
        props: { orderId: ORDER_ID },
        global: {
            mocks: {
                $tc: (key) => key,
            },
            provide: {
                hugMailCockpitApiService: apiService,
            },
            stubs: {
                'sw-loader': true,
                'sw-data-grid': {
                    template: '<table class="sw-data-grid-stub"><slot name="actions" :item="dataSource[0]" /></table>',
                    props: ['dataSource', 'columns'],
                },
                'sw-context-menu-item': {
                    // No manual re-emit: the parent's @click listener falls
                    // through to the root button element automatically.
                    template: '<button class="sw-context-menu-item-stub"><slot /></button>',
                },
                'mt-banner': { template: '<div class="mt-banner-stub"><slot /></div>' },
                'mt-empty-state': true,
                'mt-modal-root': { template: '<div class="mt-modal-root-stub"><slot /></div>' },
                'mt-modal': { template: '<div class="mt-modal-stub"><slot /></div>' },
            },
        },
    });

    return { wrapper, apiService };
}

describe('hug-mail-history-grid', () => {
    beforeAll(() => {
        Shopware.Filter = {
            getByName: () => (value) => value,
        };
    });

    it('loads and renders the history (snapshot)', async () => {
        const { wrapper, apiService } = createWrapper();
        await flushPromises();

        expect(apiService.getHistory).toHaveBeenCalledWith({ orderId: ORDER_ID, customerId: null });
        expect(wrapper.html()).toMatchSnapshot();
    });

    it('shows the unavailable hint when MailArchive is missing (404)', async () => {
        const { wrapper } = createWrapper({
            apiOverrides: {
                getHistory: jest.fn().mockRejectedValue({ response: { status: 404 } }),
            },
        });
        await flushPromises();

        expect(wrapper.vm.archiveUnavailable).toBe(true);
        expect(wrapper.find('.mt-banner-stub').exists()).toBe(true);
    });

    it('opens the detail modal via the view action', async () => {
        const { wrapper } = createWrapper();
        await flushPromises();

        await wrapper.find('.sw-context-menu-item-stub').trigger('click');

        expect(wrapper.vm.detailEntry).toEqual(ENTRIES[0]);
    });

    it('emits reply with the entry', async () => {
        const { wrapper } = createWrapper();
        await flushPromises();

        const buttons = wrapper.findAll('.sw-context-menu-item-stub');
        await buttons[1].trigger('click');

        expect(wrapper.emitted('reply')).toEqual([[ENTRIES[0]]]);
    });
});
