import template from './hug-mail-variable-picker.html.twig';
import './hug-mail-variable-picker.scss';

/**
 * Sidebar panel of the compose modal. `variables` maps root keys to
 * key => scalar value entries (value null for non-scalar properties).
 *
 * mode 'values' (default, simple editor): clicking inserts the real value;
 * entries without a value are hidden — the user works with final content.
 * mode 'expressions' (twig editor): clicking inserts `{{ root.key }}`.
 */
const hugMailVariablePicker = {
    template,

    emits: ['variable-selected'],

    props: {
        variables: {
            type: Object,
            required: true,
        },
        mode: {
            type: String,
            required: false,
            default: 'values',
            validator: (value) => ['values', 'expressions'].includes(value),
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
            const filtered = {};

            Object.entries(this.variables).forEach(([rootKey, entries]) => {
                const matches = {};

                Object.entries(entries).forEach(([key, value]) => {
                    if (this.mode === 'values' && value === null) {
                        return;
                    }

                    if (term === '' || key.toLowerCase().includes(term) || rootKey.toLowerCase().includes(term)) {
                        matches[key] = value;
                    }
                });

                if (Object.keys(matches).length > 0) {
                    filtered[rootKey] = matches;
                }
            });

            return filtered;
        },
    },

    created() {
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

        shorten(value) {
            return value.length > 24 ? `${value.slice(0, 24)}…` : value;
        },

        selectVariable(rootKey, key, value) {
            if (this.mode === 'values' && value !== null) {
                this.$emit('variable-selected', value);

                return;
            }

            this.$emit('variable-selected', this.buildExpression(rootKey, key));
        },
    },
};

Shopware.Component.register('hug-mail-variable-picker', hugMailVariablePicker);

export default hugMailVariablePicker;
