import { mount } from '@vue/test-utils';
import hugMailVariablePicker from '../../../src/Resources/app/administration/src/component/hug-mail-variable-picker';

const VARIABLES = {
    order: {
        orderNumber: '10001',
        amountTotal: '99.9',
        orderCustomer: null,
    },
    salesChannel: {
        name: 'Storefront',
    },
};

function createWrapper(props = {}) {
    return mount(hugMailVariablePicker, {
        props: { variables: VARIABLES, ...props },
        global: {
            mocks: {
                $tc: (key) => key,
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
    it('renders all variable groups (snapshot)', () => {
        const wrapper = createWrapper();

        expect(wrapper.html()).toMatchSnapshot();
    });

    it('inserts the real value in values mode', async () => {
        const wrapper = createWrapper();

        const orderNumberButton = wrapper
            .findAll('.hug-mail-variable-picker__variable')
            .find((button) => button.text().startsWith('orderNumber'));

        await orderNumberButton.trigger('click');

        expect(wrapper.emitted('variable-selected')).toEqual([['10001']]);
    });

    it('hides non-scalar variables in values mode', () => {
        const wrapper = createWrapper();

        const labels = wrapper.findAll('.hug-mail-variable-picker__variable').map((b) => b.text());
        expect(labels.some((label) => label.startsWith('orderCustomer'))).toBe(false);
    });

    it('inserts the twig expression in expressions mode', async () => {
        const wrapper = createWrapper({ mode: 'expressions' });

        const customerButton = wrapper
            .findAll('.hug-mail-variable-picker__variable')
            .find((button) => button.text().startsWith('orderCustomer'));

        await customerButton.trigger('click');

        expect(wrapper.emitted('variable-selected')).toEqual([['{{ order.orderCustomer }}']]);
    });

    it('filters variables by search term', async () => {
        const wrapper = createWrapper();

        await wrapper.find('.mt-text-field-stub').setValue('amount');

        const visible = wrapper.findAll('.hug-mail-variable-picker__variable').map((b) => b.text());
        expect(visible).toHaveLength(1);
        expect(visible[0].startsWith('amountTotal')).toBe(true);
    });
});
