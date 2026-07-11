import template from './hug-mail-variable-picker.html.twig';
import './hug-mail-variable-picker.scss';

/**
 * Sidebar panel of the compose modal: lists the root keys of the actually
 * built Twig context (from the variables endpoint) and emits the selected
 * variable path so the editor can insert `{{ path }}` at the cursor.
 */
const hugMailVariablePicker = {
    template,

    emits: ['variable-selected'],

    props: {
        variables: {
            type: Object,
            required: true,
        },
    },

    data() {
        return {
            searchTerm: '',
            openGroups: [],
        };
    },

    computed: {
        filteredVariables() {
            const term = this.searchTerm.trim().toLowerCase();

            if (term === '') {
                return this.variables;
            }

            const filtered = {};
            Object.entries(this.variables).forEach(([rootKey, keys]) => {
                const matches = keys.filter((key) => key.toLowerCase().includes(term));

                if (matches.length > 0 || rootKey.toLowerCase().includes(term)) {
                    filtered[rootKey] = matches;
                }
            });

            return filtered;
        },
    },

    created() {
        // Open all groups initially when searching is most useful.
        this.openGroups = Object.keys(this.variables);
    },

    methods: {
        isGroupOpen(rootKey) {
            return this.openGroups.includes(rootKey);
        },

        toggleGroup(rootKey) {
            if (this.isGroupOpen(rootKey)) {
                this.openGroups = this.openGroups.filter((key) => key !== rootKey);

                return;
            }

            this.openGroups.push(rootKey);
        },

        buildExpression(rootKey, key) {
            return `{{ ${rootKey}.${key} }}`;
        },

        selectVariable(path) {
            this.$emit('variable-selected', `{{ ${path} }}`);
        },
    },
};

Shopware.Component.register('hug-mail-variable-picker', hugMailVariablePicker);

export default hugMailVariablePicker;
