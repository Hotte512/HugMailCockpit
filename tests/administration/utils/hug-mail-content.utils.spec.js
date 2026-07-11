import {
    containsTwigSyntax,
    parseAddressList,
    buildRecipientMap,
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
