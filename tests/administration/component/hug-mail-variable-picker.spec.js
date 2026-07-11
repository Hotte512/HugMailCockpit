import { mount } from '@vue/test-utils';
import hugMailVariablePicker from '../../../src/Resources/app/administration/src/component/hug-mail-variable-picker';

const VARIABLES = {
    order: ['orderNumber', 'amountTotal', 'orderDateTime'],
    salesChannel: ['name'],
};

function createWrapper(variables = VARIABLES) {
    return mount(hugMailVariablePicker, {
        props: { variables },
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

    it('emits the twig expression when a variable is clicked', async () => {
        const wrapper = createWrapper();

        const orderNumberButton = wrapper
            .findAll('.hug-mail-variable-picker__variable')
            .find((button) => button.text() === 'orderNumber');

        await orderNumberButton.trigger('click');

        expect(wrapper.emitted('variable-selected')).toEqual([['{{ order.orderNumber }}']]);
    });

    it('filters variables by search term', async () => {
        const wrapper = createWrapper();

        await wrapper.find('.mt-text-field-stub').setValue('amount');

        const visible = wrapper.findAll('.hug-mail-variable-picker__variable').map((b) => b.text());
        expect(visible).toEqual(['amountTotal']);
    });
});
