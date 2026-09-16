<?php

declare(strict_types=1);

use Bpmore\Grange\Statement\ConformanceStatus;
use Bpmore\Grange\Statement\Generator;
use Bpmore\Grange\Statement\HtmlRenderer;
use Bpmore\Grange\Statement\MarkdownRenderer;
use Bpmore\Grange\Statement\Section;

function generate(\Bpmore\Grange\Statement\Statement $statement, array $external = [], ?int $responseDays = null): \Bpmore\Grange\Statement\Document
{
    return (new Generator(profiles(), checker()))->generate($statement, at(), $external, 'Example Site', $responseDays);
}

it('says partially conformant with the exceptions listed, each with criterion, page, reason and date', function () {
    $document = generate(completeStatement('w3c')->withExceptions([exception('e1', 'serious')]));
    $sections = array_combine(array_map(fn (Section $s) => $s->key, $document->sections), $document->sections);

    expect($document->status)->toBe(ConformanceStatus::PartiallyConformant);
    expect($sections['conformance']->paragraphs[1])->toBe('This website is partially conformant with Example Standard, version and level, due to the exceptions listed below.');
    expect($sections['exceptions']->items)->toBe(['Images in the gallery have no text alternative (WCAG 1.1.1) on /gallery. Reason: Legacy uploads. Planned fix: 2026-12-01.']);
});

it('lists an open finding from an integration among the exceptions', function () {
    $document = generate(completeStatement('w3c')->withExceptions([]), [exception('ext-1', 'moderate')]);
    $sections = array_combine(array_map(fn (Section $s) => $s->key, $document->sections), $document->sections);

    expect($sections['exceptions']->items)->toHaveCount(1);
});

it('says no known exceptions when there are none open, and leaves resolved ones out', function () {
    $document = generate(completeStatement('w3c', ConformanceStatus::NotAssessed)->withExceptions([exception('old', 'critical', 'resolved')]));
    $sections = array_combine(array_map(fn (Section $s) => $s->key, $document->sections), $document->sections);

    expect($sections['exceptions']->paragraphs)->toBe(['No known exceptions are recorded.']);
    expect($sections['exceptions']->items)->toBe([]);
});

it('mentions a response time only when one is configured', function () {
    $without = generate(completeStatement('w3c'));
    $with = generate(completeStatement('w3c'), [], 10);

    $feedback = fn ($doc) => array_values(array_filter($doc->sections, fn (Section $s) => $s->key === 'feedback'))[0];

    expect(implode(' ', $feedback($without)->paragraphs))->not->toContain('within');
    expect(implode(' ', $feedback($with)->paragraphs))->toContain('within 10 days');
});

it('prints the attestation on a fully conformant statement', function () {
    $document = generate(completeStatement('w3c', ConformanceStatus::FullyConformant)->withExceptions([])->withAttestation(attestation()));
    $conformance = array_values(array_filter($document->sections, fn (Section $s) => $s->key === 'conformance'))[0];

    expect(implode(' ', $conformance->paragraphs))->toContain('confirmed by Ada Example on 2026-09-10');
});

it('gives a profile-specific disclosure its own section headed with its label', function () {
    $document = generate(completeStatement('section508'));
    $keys = array_map(fn (Section $s) => $s->key, $document->sections);

    expect($keys)->toContain('relay_service');
    $relay = array_values(array_filter($document->sections, fn (Section $s) => $s->key === 'relay_service'))[0];
    expect($relay->heading)->toBe('Telecommunications relay service instructions');
});

it('uses the site name when no organisation disclosure is filled', function () {
    $document = generate(completeStatement('w3c')->withDisclosures(['organization' => '']));

    expect($document->organization)->toBe('Example Site');
});

it('carries the legal disclaimer in the document, the HTML and the Markdown', function () {
    $document = generate(completeStatement('w3c'));

    expect($document->disclaimer)->toContain('not legal advice');
    expect((new HtmlRenderer)->render($document))->toContain('not legal advice');
    expect((new MarkdownRenderer)->render($document))->toContain('not legal advice');
});

it('renders HTML with a heading hierarchy from the level asked for, escaped text, and links only for bare URLs', function () {
    $statement = completeStatement('w3c')->withDisclosures([
        'contact' => 'Email <accessibility@example.test>',
        'evaluation_report' => 'https://example.test/report?a=1&b=2',
    ]);
    $html = (new HtmlRenderer)->render(generate($statement), 2);

    expect($html)->toContain('<h2>Accessibility statement</h2>');
    expect($html)->toContain('<h3>Contact</h3>');
    expect($html)->not->toContain('<h1');
    expect($html)->toContain('Email &lt;accessibility@example.test&gt;');
    expect($html)->toContain('<a href="https://example.test/report?a=1&amp;b=2">https://example.test/report?a=1&amp;b=2</a>');
    expect($html)->toContain('lang="en"');
    expect(str_contains($html, '<script'))->toBeFalse();
});

it('renders Markdown with the title, status and every section', function () {
    $md = (new MarkdownRenderer)->render(generate(completeStatement('en301549')));

    expect($md)->toStartWith('# Accessibility statement');
    expect($md)->toContain('**Conformance status:** Partially conformant');
    expect($md)->toContain('## Enforcement procedure');
    expect($md)->toContain('- Images in the gallery have no text alternative');
});

it('has no em dash anywhere in what it emits', function () {
    $document = generate(completeStatement('en301549'));

    foreach ([(new HtmlRenderer)->render($document), (new MarkdownRenderer)->render($document), json_encode($document->toArray())] as $out) {
        expect(str_contains($out, "\u{2014}"))->toBeFalse();
    }
});
