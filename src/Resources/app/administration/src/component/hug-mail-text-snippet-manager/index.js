import template from './hug-mail-text-snippet-manager.html.twig';
import './hug-mail-text-snippet-manager.scss';

const { Criteria } = Shopware.Data;

/**
 * CRUD for text building blocks (hug_mail_text_snippet) inside the plugin
 * configuration. Works directly on the entity repository — the bound system
 * config value is intentionally unused.
 */
const hugMailTextSnippetManager = {
    template,

    inject: ['repositoryFactory'],

    mixins: [Shopware.Mixin.getByName('notification')],

    props: {
        // v-model:value binding from sw-system-config — unused on purpose.
        value: {
            type: Object,
            required: false,
            default: null,
        },
    },

    data() {
        return {
            isLoading: true,
            snippets: [],
            editing: null,
        };
    },

    computed: {
        snippetRepository() {
            return this.repositoryFactory.create('hug_mail_text_snippet');
        },
    },

    created() {
        this.loadSnippets();
    },

    methods: {
        async loadSnippets() {
            this.isLoading = true;

            try {
                const criteria = new Criteria(1, 100);
                criteria.addSorting(Criteria.sort('name', 'ASC'));

                this.snippets = await this.snippetRepository.search(criteria, Shopware.Context.api);
            } finally {
                this.isLoading = false;
            }
        },

        startCreate() {
            const entity = this.snippetRepository.create(Shopware.Context.api);
            entity.name = '';
            // TipTap-stable start value — see compose modal.
            entity.content = '<p></p>';
            this.editing = entity;
        },

        startEdit(snippet) {
            this.editing = snippet;
        },

        cancelEdit() {
            this.editing = null;
            this.loadSnippets();
        },

        async save() {
            try {
                await this.snippetRepository.save(this.editing, Shopware.Context.api);
                this.editing = null;
                await this.loadSnippets();

                this.createNotificationSuccess({
                    message: this.$tc('hug-mail-cockpit.textSnippets.saveSuccess'),
                });
            } catch (error) {
                this.createNotificationError({ message: String(error) });
            }
        },

        async remove(id) {
            await this.snippetRepository.delete(id, Shopware.Context.api);
            await this.loadSnippets();
        },
    },
};

Shopware.Component.register('hug-mail-text-snippet-manager', hugMailTextSnippetManager);

export default hugMailTextSnippetManager;
