<?php

declare(strict_types=1);

use Bpmore\A11yState\Integrations\A11yReportSource;
use Bpmore\A11yState\Statement\Severity;

/*
 * A11y Report keeps one row per element. The statement lists problems as a
 * reader sees them, so two images with no description on one page are one
 * known exception found in two places, not the same sentence twice.
 * Rows stand in for the report's tables, so the report need not be installed.
 */

function reportRow(string $message, string $path, string $impact = 'serious', string $criterion = '1.1.1', string $label = 'WCAG 1.1.1'): object
{
    return (object) ['label' => $label, 'message' => $message, 'wcag_criteria' => json_encode([$criterion]), 'path' => $path, 'impact' => $impact];
}

it('lists one exception per problem and page, saying how many places it was found', function () {
    $image = 'This image has no description, so people using screen readers do not know what it shows.';

    $findings = A11yReportSource::grouped([
        reportRow($image, '/articles/signage'),
        reportRow('Link text like "click here" does not describe where the link goes.', '/forms', 'moderate', '2.4.4', 'WCAG 2.4.4'),
        reportRow($image, '/articles/signage'),
        reportRow($image, '/people/ingrid'),
    ]);

    expect($findings)->toHaveCount(3);
    expect($findings[0]->description)->toBe('WCAG 1.1.1: '.rtrim($image, '.').'. Found in 2 places.');
    expect($findings[0]->page)->toBe('/articles/signage');
    expect($findings[0]->criterion)->toBe('1.1.1');
    expect($findings[1]->description)->not->toContain('Found in');
    expect($findings[2]->page)->toBe('/people/ingrid');
    expect($findings[2]->description)->not->toContain('Found in');
});

it('keeps the worst severity in a group, whatever order the rows come in', function () {
    $findings = A11yReportSource::grouped([
        reportRow('Same words.', '/', 'minor'),
        reportRow('Same words.', '/', 'critical'),
        reportRow('Same words.', '/', 'moderate'),
    ]);

    expect($findings)->toHaveCount(1);
    expect($findings[0]->severity)->toBe(Severity::Critical);
    expect($findings[0]->description)->toBe('WCAG 1.1.1: Same words. Found in 3 places.');
});

it('keeps an id that does not depend on which places are still open', function () {
    $both = A11yReportSource::grouped([reportRow('Same words.', '/'), reportRow('Same words.', '/')]);
    $one = A11yReportSource::grouped([reportRow('Same words.', '/')]);

    expect($both[0]->id)->toBe($one[0]->id);
    expect($both[0]->id)->toStartWith('a11y-report:');
});
