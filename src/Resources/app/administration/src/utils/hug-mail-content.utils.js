/**
 * Twig block tags or comments in the content — used to warn when switching
 * from the Twig editor back to the WYSIWYG editor (content would be shown
 * as plain text) and to hint at the twig_editor privilege requirement.
 */
export function containsTwigSyntax(content) {
    if (typeof content !== 'string') {
        return false;
    }

    return content.includes('{%') || content.includes('{#');
}

/**
 * "a@example.com, b@example.com" → { address: address } map as expected by
 * the send endpoint for cc/bcc.
 */
export function parseAddressList(value) {
    if (typeof value !== 'string' || value.trim() === '') {
        return {};
    }

    const map = {};
    value.split(',').forEach((segment) => {
        const address = segment.trim();
        if (address !== '') {
            map[address] = address;
        }
    });

    return map;
}

export function buildRecipientMap(email, name) {
    const displayName = typeof name === 'string' && name.trim() !== '' ? name.trim() : email;

    return { [email]: displayName };
}
