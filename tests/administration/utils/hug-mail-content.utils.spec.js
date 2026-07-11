import {
    containsTwigSyntax,
    parseAddressList,
    buildRecipientMap,
    resolveDocumentTemplate,
} from '../../../src/Resources/app/administration/src/utils/hug-mail-content.utils';

describe('hug-mail-content.utils', () => {
    describe('containsTwigSyntax', () => {
        it('is false for plain text and simple variables', () => {
            expect(containsTwigSyntax('Hello Max')).toBe(false);
            expect(containsTwigSyntax('Order {{ order.orderNumber }}')).toBe(false);
        });

        it('is true for twig tags and comments', () => {
            expect(containsTwigSyntax('{% if order %}x{% endif %}')).toBe(true);
            expect(containsTwigSyntax('Hello {# note #}')).toBe(true);
        });
    });

    describe('parseAddressList', () => {
        it('parses a comma separated list into an address map', () => {
            expect(parseAddressList('a@example.com, b@example.com')).toEqual({
                'a@example.com': 'a@example.com',
                'b@example.com': 'b@example.com',
            });
        });

        it('ignores empty segments and trims whitespace', () => {
            expect(parseAddressList(' a@example.com ,, ')).toEqual({
                'a@example.com': 'a@example.com',
            });
        });

        it('returns an empty map for empty input', () => {
            expect(parseAddressList('')).toEqual({});
            expect(parseAddressList(null)).toEqual({});
        });
    });

    describe('resolveDocumentTemplate', () => {
        const invoice = { documentType: { technicalName: 'invoice' } };
        const deliveryNote = { documentType: { technicalName: 'delivery_note' } };

        it('resolves the template of the first mapped document type', () => {
            const mapping = { invoice: 'template-invoice', delivery_note: 'template-delivery' };

            expect(resolveDocumentTemplate(mapping, [invoice, deliveryNote])).toBe('template-invoice');
            expect(resolveDocumentTemplate(mapping, [deliveryNote, invoice])).toBe('template-delivery');
        });

        it('skips unmapped types and falls back to null', () => {
            expect(resolveDocumentTemplate({ invoice: 'template-invoice' }, [deliveryNote])).toBeNull();
            expect(resolveDocumentTemplate({}, [invoice])).toBeNull();
            expect(resolveDocumentTemplate(null, [invoice])).toBeNull();
            expect(resolveDocumentTemplate({ invoice: 'x' }, [])).toBeNull();
            expect(resolveDocumentTemplate({ invoice: 'x' }, [{ documentType: null }])).toBeNull();
        });
    });

    describe('buildRecipientMap', () => {
        it('maps the address to the display name', () => {
            expect(buildRecipientMap('max@example.com', 'Max Mustermann')).toEqual({
                'max@example.com': 'Max Mustermann',
            });
        });

        it('falls back to the address when no name is given', () => {
            expect(buildRecipientMap('max@example.com', '')).toEqual({
                'max@example.com': 'max@example.com',
            });
        });
    });
});
