import template from './hug-mail-variable-picker.html.twig';
import './hug-mail-variable-picker.scss';

/**
 * Sidebar panel of the compose modal. `variables` maps root keys to
 * key => scalar value entries (value null for non-scalar properties).
 *
 * mode 'values' (default, simple editor): clicking inserts the real value.
 * Only curated variables (with a translated label under
 * hug-mail-cockpit.variables.*) are shown — everything technical stays out.
 * mode 'expressions' (twig editor): all keys, technical names, clicking
 * inserts `{{ root.key }}`.
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
                const matches = Object.entries(entries)
                    .filter(([key, value]) => {
                        if (this.mode !== 'values') {
                            return true;
                        }

                        return value !== null && this.hasCuratedLabel(rootKey, key);
                    })
                    .map(([key, value]) => ({
                        key,
                        value,
                        label: this.labelFor(rootKey, key),
                    }))
                    .filter((entry) => term === ''
                        || entry.label.toLowerCase().includes(term)
                        || entry.key.toLowerCase().includes(term)
                        || rootKey.toLowerCase().includes(term))
                    .sort((a, b) => a.label.localeCompare(b.label));

                if (matches.length > 0) {
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
        hasCuratedLabel(rootKey, key) {
            return typeof this.$te === 'function'
                && this.$te(`hug-mail-cockpit.variables.${rootKey}.${key}`);
        },

        labelFor(rootKey, key) {
            if (this.mode !== 'values' || !this.hasCuratedLabel(rootKey, key)) {
                return key;
            }

            return this.$tc(`hug-mail-cockpit.variables.${rootKey}.${key}`);
        },

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

        selectVariable(rootKey, entry) {
            if (this.mode === 'values' && entry.value !== null) {
                this.$emit('variable-selected', entry.value);

                return;
            }

            this.$emit('variable-selected', this.buildExpression(rootKey, entry.key));
        },
    },
};

Shopware.Component.register('hug-mail-variable-picker', hugMailVariablePicker);

export default hugMailVariablePicker;
