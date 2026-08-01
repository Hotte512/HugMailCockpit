<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Service;

/**
 * Free mail dispatch is an abuse vector (konzept.md §7): users without the
 * `hug_mail_cockpit.twig_editor` privilege may only use the variable picker,
 * i.e. plain `{{ path.to.variable }}` interpolations. Everything else
 * (tags, comments, filters, functions, array access) requires the privilege.
 * Enforced server-side; the editor mode toggle in the admin is UI only.
 */
class TwigContentPolicy
{
    /**
     * Entity properties that must never end up in a customer mail, whatever
     * privileges the sender holds. Shopware withholds these from the API via a
     * missing ApiAware flag, but the plain getters on the entity stay readable
     * from twig — so `{{ order.internalComment }}` would happily render staff
     * remarks about the customer, and `{{ customer.password }}` the hash.
     *
     * MailContextBuilder keeps them out of the variable list; this list keeps
     * them out of hand-written content.
     */
    public const BLOCKED_VARIABLE_KEYS = [
        // order: staff-only remarks about the customer
        'internalComment',
        // customer: credentials and the last-session IP address
        'password',
        'legacyPassword',
        'legacyEncoder',
        'remoteAddress',
    ];

    private const SIMPLE_VARIABLE_PATTERN = '/^\s*[a-zA-Z_]\w*(\.[a-zA-Z_]\w*)*\s*$/';

    /**
     * Twig output and tag markers — a blocked name only matters inside these;
     * in prose it is just a word.
     */
    private const TWIG_SEGMENT_PATTERN = '/\{\{(.*?)\}\}|\{%(.*?)%\}/s';

    /**
     * @return string|null the blocked property name, or null when the content is clean
     */
    public function findBlockedVariable(string $content): ?string
    {
        if (preg_match_all(self::TWIG_SEGMENT_PATTERN, $content, $matches) === false) {
            // Unparsable content is never worth the benefit of the doubt.
            return self::BLOCKED_VARIABLE_KEYS[0];
        }

        $expressions = implode(' ', array_merge($matches[1], $matches[2]));

        foreach (self::BLOCKED_VARIABLE_KEYS as $blocked) {
            if (preg_match('/\b' . preg_quote($blocked, '/') . '\b/i', $expressions) === 1) {
                return $blocked;
            }
        }

        return null;
    }

    public function requiresTwigEditor(string $content): bool
    {
        if (str_contains($content, '{%') || str_contains($content, '{#')) {
            return true;
        }

        if (preg_match_all('/\{\{(.*?)\}\}/s', $content, $matches) === false) {
            return true;
        }

        foreach ($matches[1] as $expression) {
            if (preg_match(self::SIMPLE_VARIABLE_PATTERN, $expression) !== 1) {
                return true;
            }
        }

        return false;
    }
}
