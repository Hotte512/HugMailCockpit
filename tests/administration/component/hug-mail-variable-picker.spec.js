import { mount } from '@vue/test-utils';
import hugMailVariablePicker from '../../../src/Resources/app/administration/src/component/hug-mail-variable-picker';

const VARIABLES = {
    order: {
        orderNumber: '10001',
        amountTotal: '99.9',
        billingAddressId: 'a0b1c2d3e4f5a0b1c2d3e4f5a0b1c2d3',
        deepLinkCode: 'xYz',
        orderCustomer: null,
    },
    salesChannel: {
        name: 'Storefront',
    },
};

const LABELS = {
    'hug-mail-cockpit.variables.order.orderNumber': 'Bestellnummer',
    'hug-mail-cockpit.variables.order.amountTotal': 'Gesamtbetrag (brutto)',
    'hug-mail-cockpit.variables.salesChannel.name': 'Verkaufskanal',
};

function createWrapper(props = {}) {
    return mount(hugMailVariablePicker, {
        props: { variables: VARIABLES, ...props },
        global: {
            mocks: {
                $tc: (key) => LABELS[key] ?? key,
                $te: (key) => key in LABELS,
            },
            stubs: {
                'mt-text-field': {
                    template: '<input class="mt-text-field-stub" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
                    props: ['modelValue', 'size', 'placeholder'],
                },
                'mt-icon': true,
            },
        },
    });
}

describe('hug-mail-variable-picker', () => {
    it('renders translated labels sorted alphabetically (snapshot)', () => {
        const wrapper = createWrapper();

        expect(wrapper.html()).toMatchSnapshot();
    });

    it('shows translated labels and inserts the real value in values mode', async () => {
        const wrapper = createWrapper();

        const orderNumberButton = wrapper
            .findAll('.hug-mail-variable-picker__variable')
            .find((button) => button.text().startsWith('Bestellnummer'));

        expect(orderNumberButton).toBeDefined();
        await orderNumberButton.trigger('click');

        expect(wrapper.emitted('variable-selected')).toEqual([['10001']]);
    });

    it('shows only curated (translated) entries in values mode', () => {
        const visible = createWrapper().findAll('.hug-mail-variable-picker__variable').map((b) => b.text());

        expect(visible).toHaveLength(3);
        expect(visible.some((label) => label.includes('billingAddressId'))).toBe(false);
        expect(visible.some((label) => label.includes('deepLinkCode'))).toBe(false);
        expect(visible.some((label) => label.includes('orderCustomer'))).toBe(false);
    });

    it('shows all technical keys and inserts expressions in expressions mode', async () => {
        const wrapper = createWrapper({ mode: 'expressions' });

        const labels = wrapper.findAll('.hug-mail-variable-picker__variable').map((b) => b.text());
        expect(labels.some((label) => label.startsWith('billingAddressId'))).toBe(true);
        expect(labels.some((label) => label.startsWith('orderCustomer'))).toBe(true);

        const customerButton = wrapper
            .findAll('.hug-mail-variable-picker__variable')
            .find((button) => button.text().startsWith('orderCustomer'));

        await customerButton.trigger('click');

        expect(wrapper.emitted('variable-selected')).toEqual([['{{ order.orderCustomer }}']]);
    });

    it('filters by translated label and by technical key', async () => {
        const wrapper = createWrapper();

        await wrapper.find('.mt-text-field-stub').setValue('Gesamtbetrag');
        let visible = wrapper.findAll('.hug-mail-variable-picker__variable').map((b) => b.text());
        expect(visible).toHaveLength(1);
        expect(visible[0].startsWith('Gesamtbetrag')).toBe(true);

        await wrapper.find('.mt-text-field-stub').setValue('amountTot');
        visible = wrapper.findAll('.hug-mail-variable-picker__variable').map((b) => b.text());
        expect(visible).toHaveLength(1);
        expect(visible[0].startsWith('Gesamtbetrag')).toBe(true);
    });
});
