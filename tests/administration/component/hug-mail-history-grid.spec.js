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
    {
        id: 'entry-2',
        subject: 'Order confirmation',
        receiver: { 'max@example.com': 'Max' },
        transportState: 'sent',
        mailTemplateId: null,
        htmlText: '<p>Older body</p>',
        attachmentCount: 0,
        createdAt: '2026-06-01T10:00:00+00:00',
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
                    template: `<table class="sw-data-grid-stub">
                        <tbody class="sw-data-grid__body">
                            <tr v-for="(item, index) in dataSource" :key="item.id" :class="'sw-data-grid__row sw-data-grid__row--' + index">
                                <td>{{ item.subject }}</td>
                            </tr>
                        </tbody>
                        <slot name="actions" :item="dataSource[0]" />
                    </table>`,
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

    it('preselects the most recent mail for the preview pane', async () => {
        const { wrapper } = createWrapper();
        await flushPromises();

        expect(wrapper.vm.selectedEntry).toEqual(ENTRIES[0]);
        expect(wrapper.find('.hug-mail-history-grid__preview-frame').exists()).toBe(true);
    });

    it('shows the clicked row in the preview pane', async () => {
        const { wrapper } = createWrapper();
        await flushPromises();

        await wrapper.findAll('.sw-data-grid__row')[1].trigger('click');

        expect(wrapper.vm.selectedEntry).toEqual(ENTRIES[1]);
    });

    it('opens the modal on double click', async () => {
        const { wrapper } = createWrapper();
        await flushPromises();

        await wrapper.findAll('.sw-data-grid__row')[1].trigger('dblclick');

        expect(wrapper.vm.detailEntry).toEqual(ENTRIES[1]);
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

    it('deep links into the mail archive module', async () => {
        const { wrapper } = createWrapper();
        await flushPromises();

        const push = jest.fn();
        wrapper.vm.$router = { push };

        const buttons = wrapper.findAll('.sw-context-menu-item-stub');
        await buttons[2].trigger('click');

        expect(push).toHaveBeenCalledWith({
            name: 'frosh.mail.archive.detail',
            params: { id: 'entry-1' },
        });
    });
});
