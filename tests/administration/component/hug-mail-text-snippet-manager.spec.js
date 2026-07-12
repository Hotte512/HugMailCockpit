import { mount, flushPromises } from '@vue/test-utils';
import hugMailTextSnippetManager from '../../../src/Resources/app/administration/src/component/hug-mail-text-snippet-manager';

const SNIPPETS = [
    { id: 's1', name: 'Grußformel', content: '<p>Viele Grüße</p>' },
    { id: 's2', name: 'Rückgabe-Hinweis', content: '<p>Rückgabe binnen 14 Tagen</p>' },
];

function createWrapper() {
    const repository = {
        search: jest.fn().mockResolvedValue([...SNIPPETS]),
        create: jest.fn().mockReturnValue({ id: null, name: undefined, content: undefined }),
        save: jest.fn().mockResolvedValue(null),
        delete: jest.fn().mockResolvedValue(null),
    };

    const wrapper = mount(hugMailTextSnippetManager, {
        global: {
            mocks: {
                $tc: (key) => key,
            },
            provide: {
                repositoryFactory: { create: () => repository },
            },
            stubs: {
                'sw-loader': true,
                'mt-button': {
                    template: '<button class="mt-button-stub" :disabled="disabled" @click="$emit(\'click\')"><slot /></button>',
                    props: ['disabled', 'variant', 'size'],
                },
                'mt-text-field': {
                    template: '<input class="mt-text-field-stub" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
                    props: ['modelValue', 'label'],
                },
                'mt-text-editor': {
                    template: '<textarea class="mt-text-editor-stub" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)"></textarea>',
                    props: ['modelValue'],
                },
            },
        },
    });

    return { wrapper, repository };
}

describe('hug-mail-text-snippet-manager', () => {
    it('lists the snippets sorted (snapshot)', async () => {
        const { wrapper } = createWrapper();
        await flushPromises();

        expect(wrapper.html()).toMatchSnapshot();
    });

    it('creates a new snippet with tiptap-stable content', async () => {
        const { wrapper, repository } = createWrapper();
        await flushPromises();

        wrapper.vm.startCreate();

        expect(repository.create).toHaveBeenCalled();
        expect(wrapper.vm.editing.content).toBe('<p></p>');
    });

    it('saves the edited snippet and reloads', async () => {
        const { wrapper, repository } = createWrapper();
        await flushPromises();

        wrapper.vm.startEdit({ ...SNIPPETS[0] });
        wrapper.vm.editing.name = 'Grußformel neu';
        await wrapper.vm.save();

        expect(repository.save).toHaveBeenCalledWith(
            expect.objectContaining({ name: 'Grußformel neu' }),
            expect.anything(),
        );
        expect(wrapper.vm.editing).toBeNull();
        expect(repository.search).toHaveBeenCalledTimes(2);
    });

    it('deletes a snippet', async () => {
        const { wrapper, repository } = createWrapper();
        await flushPromises();

        await wrapper.vm.remove('s2');

        expect(repository.delete).toHaveBeenCalledWith('s2', expect.anything());
    });
});
