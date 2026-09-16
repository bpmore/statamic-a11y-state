<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Statement;

/**
 * What the statement's own page renders with, decided per request.
 *
 * The layout is the one config names, else Statamic's system layout, else
 * a plain shell of this addon's own, because a site whose layout is Blade
 * with `@yield` has no `layout` view at all, and a 500 on the accessibility
 * statement is the one page that must not fail. A site like that sets
 * `view` to a Blade template of its own that calls the tag.
 *
 * This file is in the Statement namespace but reads config, which the
 * rest of that namespace does not: it is the seam between the two halves
 * and is named for what it decides.
 */
final class StatementRoute
{
    public const FALLBACK_LAYOUT = 'a11y-state::statement-layout';

    public const DEFAULT_VIEW = 'a11y-state::statement';

    public static function view(): string
    {
        $view = config('statamic-a11y-state.view');

        return is_string($view) && $view !== '' ? $view : self::DEFAULT_VIEW;
    }

    /**
     * @return array<string, mixed>
     */
    public static function data(): array
    {
        return [
            'title' => 'Accessibility statement',
            'layout' => self::layout(),
        ];
    }

    public static function layout(): string
    {
        $configured = config('statamic-a11y-state.layout');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        $system = (string) config('statamic.system.layout', 'layout');

        return self::viewExists($system) ? $system : self::FALLBACK_LAYOUT;
    }

    private static function viewExists(string $name): bool
    {
        try {
            app('view')->getFinder()->find($name);

            return true;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }
}
