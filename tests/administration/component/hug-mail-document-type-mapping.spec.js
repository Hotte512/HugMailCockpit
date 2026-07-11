import { mount, flushPromises } from '@vue/test-utils';
import hugMailDocumentTypeMapping from '../../../src/Resources/app/administration/src/component/hug-mail-document-type-mapping';

const DOCUMENT_TYPES = [
    { id: 'type-1', technicalName: 'invoice', translated: { name: 'Rechnung' } },
    { id: 'type-2', technicalName: 'delivery_note', translated: { name: 'Lieferschein' } },
];

function createWrapper(value = null) {
    const repositoryFactory = {
        create: () => ({
            search: jest.fn().mockResolvedValue(DOCUMENT_TYPES),
        }),
    };

    return mount(hugMailDocumentTypeMapping, {
        props: { value },
        global: {
            mocks: {
                $tc: (key) => key,
            },
            provide: { repositoryFactory },
            stubs: {
                'sw-entity-single-select': {
                    template: '<select class="sw-entity-single-select-stub" :data-value="value" @change="$emit(\'update:value\', $event.target.value)"><option value="template-1">t1</option><option value="">none</option></select>',
                    props: ['value', 'entity', 'labelProperty', 'placeholder'],
                },
            },
        },
    });
}

describe('hug-mail-document-type-mapping', () => {
    it('renders a row per document type (snapshot)', async () => {
        const wrapper = createWrapper({ invoice: 'template-1' });
        await flushPromises();

        expect(wrapper.html()).toMatchSnapshot();
    });

    it('adds a mapping and emits the full object', async () => {
        const wrapper = createWrapper({ invoice: 'template-1' });
        await flushPromises();

        const selects = wrapper.findAll('.sw-entity-single-select-stub');
        await selects[1].setValue('template-1');

        expect(wrapper.emitted('update:value')).toEqual([
            [{ invoice: 'template-1', delivery_note: 'template-1' }],
        ]);
    });

    it('removes a mapping when the template is cleared', async () => {
        const wrapper = createWrapper({ invoice: 'template-1' });
        await flushPromises();

        const selects = wrapper.findAll('.sw-entity-single-select-stub');
        await selects[0].setValue('');

        expect(wrapper.emitted('update:value')).toEqual([[{}]]);
    });
});
