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
    private const SIMPLE_VARIABLE_PATTERN = '/^\s*[a-zA-Z_]\w*(\.[a-zA-Z_]\w*)*\s*$/';

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
