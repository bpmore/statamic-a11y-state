<?php

declare(strict_types=1);

use Bpmore\A11yState\Http\Controllers\StatementController;
use Illuminate\Support\Facades\Route;
use Statamic\Facades\Site;

/*
 * The statement's own page, once per site, on the path the config file
 * names. A subdirectory site (`/fr`) gets its own prefixed route; a
 * domain-based site shares the path and the request's host picks the site.
 *
 * A controller and never a closure: `route:cache` writes route defaults
 * with `var_export`, which cannot express a closure, and writes the broken
 * file without complaining. A11y Report took a live site down
 * that way once, and this addon does not need to learn it again.
 */
$configured = config('statamic-a11y-state.route');

if (is_string($configured) && trim($configured, '/ ') !== '') {
    $route = '/'.trim($configured, '/');

    Site::all()
        ->map(fn ($site) => rtrim((string) parse_url((string) $site->url(), PHP_URL_PATH), '/'))
        ->unique()
        ->each(function (string $prefix) use ($route) {
            Route::get($prefix.$route, StatementController::class)
                ->name('a11y-state.statement'.($prefix === '' ? '' : '.'.trim($prefix, '/')));
        });
}
