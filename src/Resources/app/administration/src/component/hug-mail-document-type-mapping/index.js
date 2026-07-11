import template from './hug-mail-document-type-mapping.html.twig';
import './hug-mail-document-type-mapping.scss';

const { Criteria } = Shopware.Data;

/**
 * Plugin config component (F3, konzept.md §4): maps document types to mail
 * templates. Rendered by sw-system-config via v-model:value; the value is
 * stored as JSON object { technicalName: mailTemplateId } in the system
 * config key HugMailCockpit.config.documentTypeTemplateMapping.
 */
const hugMailDocumentTypeMapping = {
    template,

    inject: ['repositoryFactory'],

    emits: ['update:value'],

    props: {
        value: {
            type: Object,
            required: false,
            default: null,
        },
    },

    data() {
        return {
            documentTypes: [],
        };
    },

    created() {
        const criteria = new Criteria(1, 100);
        criteria.addSorting(Criteria.sort('name', 'ASC'));

        this.repositoryFactory
            .create('document_type')
            .search(criteria, Shopware.Context.api)
            .then((documentTypes) => {
                this.documentTypes = documentTypes;
            });
    },

    methods: {
        mappingValue(technicalName) {
            return this.value && typeof this.value === 'object'
                ? this.value[technicalName] ?? null
                : null;
        },

        setMapping(technicalName, mailTemplateId) {
            const mapping = { ...(this.value ?? {}) };

            if (mailTemplateId) {
                mapping[technicalName] = mailTemplateId;
            } else {
                delete mapping[technicalName];
            }

            this.$emit('update:value', mapping);
        },
    },
};

Shopware.Component.register('hug-mail-document-type-mapping', hugMailDocumentTypeMapping);

export default hugMailDocumentTypeMapping;
