<?php

declare(strict_types=1);

use Bpmore\A11yState\Statement\ConformanceStatus;
use Statamic\Facades\Antlers;

/*
 * The statement's own page and the tag, standalone (discriminating test
 * 6): no other addon is booted in this suite, and everything works.
 */

it('serves an incomplete statement as a short notice, never a 500 and never a half document', function () {
    $response = $this->get('/accessibility-statement')->assertOk();
    $html = $response->getContent();

    expect($html)->toContain('<h1>Accessibility statement</h1>');
    expect($html)->toContain('being prepared and is not yet published');
    expect($html)->not->toContain('Conformance status:');
    expect($html)->toContain('<main');
    expect($html)->toContain('<html lang=');
});

it('serves the generated statement as accessible HTML with no script and a heading hierarchy', function () {
    saveStatement('w3c', ConformanceStatus::PartiallyConformant, ['contact' => 'accessibility@example.test']);

    $html = $this->get('/accessibility-statement')->assertOk()->getContent();

    expect($html)->toContain('<html lang=');
    expect(substr_count($html, '<h1>'))->toBe(1);
    expect($html)->toContain('<h2>Conformance status</h2>');
    expect($html)->toContain('Partially conformant');
    expect($html)->toContain('accessibility@example.test');
    expect($html)->toContain('not legal advice');
    expect($html)->toContain('<main');
    expect(str_contains($html, '<script'))->toBeFalse();
    // Every h2 follows the h1 and there is no h3 before an h2.
    expect(strpos($html, '<h1>'))->toBeLessThan(strpos($html, '<h2>'));
    expect(str_contains($html, '<h3>'))->toBeFalse();
});

it('renders through the tag inside a page of the site, headings from h2 by default', function () {
    saveStatement();

    // Trusted, as a template is; untrusted parsing runs no tags at all.
    $out = (string) Antlers::parse('<h1>About</h1>{{ a11y_state:statement }}', [], true);

    expect($out)->toContain('<h2>Accessibility statement</h2>');
    expect($out)->toContain('<h3>Known exceptions</h3>');
    expect($out)->toContain('Images in the gallery have no text alternative');
});

it('runs with no other bpmore addon and the null source in effect', function () {
    $statements = app(\Bpmore\A11yState\Statements::class);

    expect(class_exists(\Bpmore\A11yReport\ServiceProvider::class))->toBeFalse();
    expect($statements->findings()->isAvailable())->toBeFalse();
    expect($statements->findings()->name())->toBe('none');
    expect($statements->externalFindings('default'))->toBe([]);
    expect($statements->importFixes('default', new DateTimeImmutable('2020-01-01')))->toBe(0);

    saveStatement();
    expect($statements->document($statements->for('default')))->toBeInstanceOf(\Bpmore\A11yState\Statement\Document::class);
});

it('has no em dash anywhere in the repository', function () {
    $offenders = [];

    foreach (['src', 'config', 'resources', 'routes', 'docs', 'tests'] as $dir) {
        $path = __DIR__.'/../../'.$dir;

        if (! is_dir($path)) {
            continue;
        }

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $file) {
            if (str_contains((string) file_get_contents($file->getPathname()), "\u{2014}")) {
                $offenders[] = $file->getPathname();
            }
        }
    }

    foreach (['README.md', 'LICENSE.md', 'CHANGELOG.md', 'composer.json'] as $file) {
        $path = __DIR__.'/../../'.$file;

        if (file_exists($path) && str_contains((string) file_get_contents($path), "\u{2014}")) {
            $offenders[] = $path;
        }
    }

    expect($offenders)->toBe([]);
});
