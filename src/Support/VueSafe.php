<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Support;

/**
 * Text for a view that Vue will compile.
 *
 * A utility view is not printed as HTML: Statamic hands the rendered markup
 * to Vue as a component template. Blade's escaping is half the job. A
 * value containing `{{`, which an error message or a URL can, is read by
 * Vue as an interpolation and the whole page fails to compile, blank, with
 * no exception on the server. Vue finds its delimiters in the raw source
 * before it decodes entities, so a brace written as an entity is safe.
 * Learned by A11y Report; the same helper under this namespace.
 */
final class VueSafe
{
    public static function text(mixed $value): string
    {
        return self::braces(e((string) $value));
    }

    /** For markup that is already escaped and trusted: only the braces are hidden from Vue. */
    public static function braces(string $html): string
    {
        return str_replace(['{', '}'], ['&#123;', '&#125;'], $html);
    }
}
