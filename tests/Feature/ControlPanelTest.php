<?php

declare(strict_types=1);

use Bpmore\A11yState\Evidence\Actions;
use Bpmore\A11yState\Evidence\Filter;
use Bpmore\A11yState\Statement\ConformanceStatus;
use Bpmore\A11yState\Statements;

/*
 * The utility: the editor, the attestation step, the evidence screen and
 * the feedback screen, driven the way a person drives them, through the
 * routes. Every save goes through the same service the command reads,
 * so what the screen refuses the command would refuse too.
 */

beforeEach(function () {
    $this->actingAs(cpUser());
});

function fields(string $profile = 'w3c', string $status = 'partially-conformant', array $override = []): array
{
    $disclosures = [];

    foreach (profiles()->get($profile)->disclosures as $d) {
        if (in_array($d->key, ['conformance_status', 'exceptions'], true)) {
            continue;
        }

        $disclosures[$d->key] = $d->type === 'date' ? '2026-09-01' : ($d->type === 'url' ? 'https://example.test/r' : 'Example '.$d->label);
    }

    return array_replace_recursive(['site' => 'default', 'profile' => $profile, 'status' => $status, 'disclosures' => $disclosures], $override);
}

it('renders the editor as a well-formed Vue template with the disclaimer, for a site with no statement yet', function () {
    $html = utilityHtml();

    assertVueTemplateIsWellFormed($html);
    $text = pageText($html);
    expect($text)->toContain('Not legal advice');
    expect($text)->toContain('Jurisdiction profile');
    expect($text)->toContain('"Fully conformant" is not a choice here');
    // The source is printed beside every field, so a buyer's auditor can check it.
    expect($text)->toContain('Source: W3C WAI');
});

it('saves a statement from the editor and writes an evidence record saying so', function () {
    $this->post(cp_route('utilities.a11y-state.save'), fields())
        ->assertRedirect()
        ->assertSessionHas('success');

    $statement = app(Statements::class)->for('default');
    expect($statement->status)->toBe(ConformanceStatus::PartiallyConformant);
    expect($statement->disclosure('contact'))->toBe('Example Contact details');
    expect($statement->updatedBy)->toContain('Ada Example');

    $records = app(Statements::class)->log()->filter(new Filter(action: Actions::STATEMENT_UPDATED));
    expect($records)->toHaveCount(1);
    expect($records[0]->actor)->toContain('Ada Example');
});

it('refuses "fully conformant" from the status control with an explanation', function () {
    $this->post(cp_route('utilities.a11y-state.save'), fields(status: 'fully-conformant'))
        ->assertRedirect()
        ->assertSessionHas('error', fn ($m) => str_contains($m, 'attestation step'));

    expect(app(Statements::class)->exists('default'))->toBeFalse();
});

it('adds an exception, then refuses the attestation naming it, then allows it once resolved', function () {
    $this->post(cp_route('utilities.a11y-state.save'), fields(override: [
        'new_exception' => ['description' => 'Carousel cannot be paused', 'severity' => 'serious', 'criterion' => '2.2.2', 'page' => '/home'],
    ]))->assertSessionHas('success');

    $statement = app(Statements::class)->for('default');
    expect($statement->openExceptions())->toHaveCount(1);
    $id = $statement->openExceptions()[0]->id;

    $attest = ['site' => 'default', 'attestation' => ['name' => 'Grace Example', 'date' => '2026-09-14', 'basis' => 'Manual audit of every template, 12 September 2026.']];

    $this->post(cp_route('utilities.a11y-state.attest'), $attest)
        ->assertRedirect()
        ->assertSessionHas('error', fn ($m) => str_contains($m, 'Carousel cannot be paused') && str_contains($m, 'refused'));

    expect(app(Statements::class)->for('default')->status)->toBe(ConformanceStatus::PartiallyConformant);

    // The editor says the same thing, in the attestation panel.
    $text = pageText(utilityHtml());
    expect($text)->toContain('Not available');
    expect($text)->toContain('Carousel cannot be paused');

    $this->post(cp_route('utilities.a11y-state.save'), fields(override: ['resolve' => [$id]]))->assertSessionHas('success');

    $this->post(cp_route('utilities.a11y-state.attest'), $attest)->assertSessionHas('success');

    $statement = app(Statements::class)->for('default');
    expect($statement->status)->toBe(ConformanceStatus::FullyConformant);
    expect($statement->attestation->name)->toBe('Grace Example');
    expect($statement->attestation->recordId)->not->toBeNull();

    $record = app(Statements::class)->log()->find($statement->attestation->recordId);
    expect($record->action)->toBe(Actions::ATTESTED);
    expect($record->notes)->toContain('Grace Example');

    $actions = array_map(fn ($r) => $r->action, app(Statements::class)->log()->all());
    expect($actions)->toContain(Actions::EXCEPTION_OPENED, Actions::EXCEPTION_RESOLVED, Actions::ATTESTED);
});

it('refuses an attestation with no name, no date, or no basis', function () {
    saveStatement();

    foreach ([
        ['name' => '', 'date' => '2026-09-14', 'basis' => 'x'],
        ['name' => 'Grace', 'date' => 'yesterday', 'basis' => 'x'],
        ['name' => 'Grace', 'date' => '2026-09-14', 'basis' => ' '],
    ] as $attestation) {
        $this->post(cp_route('utilities.a11y-state.attest'), ['site' => 'default', 'attestation' => $attestation])->assertSessionHas('error');
    }

    expect(app(Statements::class)->for('default')->status)->toBe(ConformanceStatus::PartiallyConformant);
    expect(app(Statements::class)->log()->filter(new Filter(action: Actions::ATTESTED)))->toBe([]);
});

it('clears the attestation when the status is moved off fully conformant', function () {
    saveStatement('w3c', ConformanceStatus::PartiallyConformant)->exceptions;
    app(Statements::class)->save(app(Statements::class)->for('default')->withExceptions([]), 'fixture');
    app(Statements::class)->attest('default', 'Grace', '2026-09-14', 'Audit', 'fixture');

    expect(app(Statements::class)->for('default')->attestation)->not->toBeNull();

    $this->post(cp_route('utilities.a11y-state.save'), fields(status: 'partially-conformant'))->assertSessionHas('success');

    $statement = app(Statements::class)->for('default');
    expect($statement->status)->toBe(ConformanceStatus::PartiallyConformant);
    expect($statement->attestation)->toBeNull();
});

it('warns on a profile switch and keeps the disclosures the new profile has no field for', function () {
    $this->post(cp_route('utilities.a11y-state.save'), fields('section508'))->assertSessionHas('success');

    // Looking before saving: the notice names what would be kept.
    $text = pageText(utilityHtml('?site=default&profile=w3c'));
    expect($text)->toContain('You are looking at the Generic (W3C) profile');
    expect($text)->toContain('relay_service');

    $this->post(cp_route('utilities.a11y-state.save'), fields('w3c'))
        ->assertSessionHas('success', fn ($m) => str_contains($m, 'kept and not shown') && str_contains($m, 'relay_service'));

    $statement = app(Statements::class)->for('default');
    expect($statement->profile)->toBe('w3c');
    expect($statement->disclosure('relay_service'))->toBe('Example Telecommunications relay service instructions');
    expect($statement->disclosure('complaint_instructions'))->toBe('Example How to file a Section 508 complaint');

    // And the editor shows them as kept.
    $text = pageText(utilityHtml());
    expect($text)->toContain('Kept from another profile');
});

it('shows the stale notice in the editor without changing the statement', function () {
    saveStatement('w3c', ConformanceStatus::PartiallyConformant, ['assessment_date' => '2020-01-01']);
    $before = file_get_contents($this->storage.'/statements/default.yaml');

    $text = pageText(utilityHtml());
    expect($text)->toContain('Stale assessment');
    expect($text)->toContain('older than the 365-day window');
    expect(file_get_contents($this->storage.'/statements/default.yaml'))->toBe($before);
});

it('previews the generated statement, or lists why it cannot', function () {
    $this->get(cp_route('utilities.a11y-state.preview').'?site=default')->assertOk();
    $html = (string) json_decode(html_entity_decode((string) preg_replace('/^.*data-page="([^"]+)".*$/s', '$1', $this->get(cp_route('utilities.a11y-state.preview').'?site=default')->getContent()), ENT_QUOTES | ENT_HTML5, 'UTF-8'), true)['props']['html'];
    assertVueTemplateIsWellFormed($html);
    expect($html)->toContain('cannot be generated yet');

    saveStatement();
    $html = (string) json_decode(html_entity_decode((string) preg_replace('/^.*data-page="([^"]+)".*$/s', '$1', $this->get(cp_route('utilities.a11y-state.preview').'?site=default')->getContent()), ENT_QUOTES | ENT_HTML5, 'UTF-8'), true)['props']['html'];
    assertVueTemplateIsWellFormed($html);
    expect(pageText($html))->toContain('Conformance status: Partially conformant');
});

it('exports the statement as Markdown and HTML, and refuses when incomplete', function () {
    $this->get(cp_route('utilities.a11y-state.export', ['format' => 'md']).'?site=default')->assertRedirect()->assertSessionHas('error');

    saveStatement();

    $md = $this->get(cp_route('utilities.a11y-state.export', ['format' => 'md']).'?site=default')->assertOk()->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
    expect($md->getContent())->toStartWith('# Accessibility statement');

    $html = $this->get(cp_route('utilities.a11y-state.export', ['format' => 'html']).'?site=default')->assertOk();
    expect($html->getContent())->toContain('<!DOCTYPE html>');
    expect($html->getContent())->toContain('<h1>Accessibility statement</h1>');
    expect($html->getContent())->toContain('not legal advice');

    $this->get(cp_route('utilities.a11y-state.export', ['format' => 'pdf']).'?site=default')->assertNotFound();
});

it('writes, lists, filters and exports evidence, and has no route that changes or removes a record', function () {
    $this->post(cp_route('utilities.a11y-state.evidence.append'), ['site' => 'default', 'action' => 'assessed', 'criterion' => '1.4.3', 'subject' => '/home', 'notes' => 'Contrast checked with a meter'])
        ->assertSessionHas('success');
    $this->post(cp_route('utilities.a11y-state.evidence.append'), ['site' => 'default', 'action' => 'fixed', 'criterion' => '1.4.3', 'subject' => '/home', 'notes' => 'Button colour changed'])
        ->assertSessionHas('success');

    $all = app(Statements::class)->log()->all();
    expect($all)->toHaveCount(2);
    expect($all[0]->notes)->toBe('Contrast checked with a meter');
    $fixed = $all[1];
    expect($fixed->action)->toBe('fixed');

    $this->post(cp_route('utilities.a11y-state.evidence.append'), ['site' => 'default', 'action' => 'note', 'notes' => 'Correction: it was the link colour', 'supersedes' => $fixed->id])
        ->assertSessionHas('success');
    $this->post(cp_route('utilities.a11y-state.evidence.append'), ['site' => 'default', 'action' => 'note', 'notes' => 'x', 'supersedes' => 'nope'])
        ->assertSessionHas('error');
    $this->post(cp_route('utilities.a11y-state.evidence.append'), ['site' => 'default', 'action' => 'attested', 'notes' => 'x'])
        ->assertSessionHas('error');
    $this->post(cp_route('utilities.a11y-state.evidence.append'), ['site' => 'default', 'action' => 'note', 'notes' => ''])
        ->assertSessionHas('error');

    expect(app(Statements::class)->log()->count())->toBe(3);

    $html = (string) json_decode(html_entity_decode((string) preg_replace('/^.*data-page="([^"]+)".*$/s', '$1', $this->get(cp_route('utilities.a11y-state.evidence').'?site=default&action=fixed')->getContent()), ENT_QUOTES | ENT_HTML5, 'UTF-8'), true)['props']['html'];
    assertVueTemplateIsWellFormed($html);
    $text = pageText($html);
    expect($html)->toContain('3 records, 1 shown');
    expect($text)->toContain('Button colour changed');
    expect($text)->toContain('Corrected by a later record');
    expect($text)->not->toContain('Contrast checked');

    $csv = $this->get(cp_route('utilities.a11y-state.evidence.export', ['format' => 'csv']))->assertOk()->getContent();
    expect(substr_count($csv, "\n"))->toBe(4);
    $json = json_decode($this->get(cp_route('utilities.a11y-state.evidence.export', ['format' => 'json']).'?action=fixed')->assertOk()->getContent(), true);
    expect($json['count'])->toBe(1);

    // No route under the utility updates or deletes.
    $routes = collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn ($r) => str_starts_with((string) $r->getName(), 'statamic.cp.utilities.a11y-state'))
        ->map(fn ($r) => implode('|', $r->methods()).' '.$r->uri())
        ->values()->all();
    expect($routes)->not->toBe([]);

    foreach ($routes as $route) {
        expect(preg_match('/\b(DELETE|PUT|PATCH)\b/', $route))->toBe(0, $route);
        expect(preg_match('~evidence/[^/]*(delete|update|edit)~', $route))->toBe(0, $route);
    }
});

it('refuses edits, attestations and records to a user without the permission, and lets them read', function () {
    saveStatement();
    $this->actingAs(cpUser(['access cp', 'access utilities', 'access a11y-state utility']));

    $this->get(cp_route('utilities.index').'/a11y-state')->assertOk();
    $this->post(cp_route('utilities.a11y-state.save'), fields())->assertForbidden();
    $this->post(cp_route('utilities.a11y-state.attest'), ['site' => 'default', 'attestation' => ['name' => 'G', 'date' => '2026-09-14', 'basis' => 'x']])->assertForbidden();
    $this->post(cp_route('utilities.a11y-state.evidence.append'), ['site' => 'default', 'action' => 'note', 'notes' => 'x'])->assertForbidden();

    expect(pageText(utilityHtml()))->toContain('You can read this statement but not change it');
});

it('says so when no feedback form is configured, and reports the clock when one is', function () {
    $page = fn () => (string) json_decode(html_entity_decode((string) preg_replace('/^.*data-page="([^"]+)".*$/s', '$1', $this->get(cp_route('utilities.a11y-state.feedback').'?site=default')->getContent()), ENT_QUOTES | ENT_HTML5, 'UTF-8'), true)['props']['html'];

    $html = $page();
    assertVueTemplateIsWellFormed($html);
    expect($html)->toContain('No feedback form configured');

    config()->set('statamic-a11y-state.feedback.form', 'accessibility');
    config()->set('statamic-a11y-state.feedback.response_days', 5);

    expect($page())->toContain('does not exist');

    $form = \Statamic\Facades\Form::make('accessibility')->title('Accessibility feedback');
    $form->save();
    $old = \Statamic\Facades\FormSubmission::make()->form($form)->id((string) strtotime('-20 days'))->data(['message' => 'The menu traps focus']);
    $old->save();
    $new = \Statamic\Facades\FormSubmission::make()->form($form)->id((string) strtotime('-1 day'))->data(['message' => 'Thanks for the captions']);
    $new->save();

    $html = $page();
    assertVueTemplateIsWellFormed($html);
    $text = pageText($html);
    expect($html)->toContain('2 submissions, 2 awaiting acknowledgment, 1 past the 5-day window');
    expect($text)->toContain('The menu traps focus');
    expect($text)->toContain('Past the window');

    $this->post(cp_route('utilities.a11y-state.feedback.acknowledge'), ['site' => 'default', 'submission' => $old->id(), 'notes' => 'Replied by email'])->assertSessionHas('success');
    $this->post(cp_route('utilities.a11y-state.feedback.acknowledge'), ['site' => 'default', 'submission' => 'nope'])->assertSessionHas('error');

    $html = $page();
    expect($html)->toContain('1 awaiting acknowledgment, 0 past the 5-day window');
    expect(pageText($html))->toContain('Acknowledged by Ada Example');

    $records = app(Statements::class)->log()->filter(new Filter(action: Actions::FEEDBACK_ACKNOWLEDGED));
    expect($records)->toHaveCount(1);
    expect($records[0]->subject)->toBe($old->id());

    // And the command sees the same clock.
    \Illuminate\Support\Facades\Artisan::call('statamic:a11y:state:check', ['--json' => true]);
    $json = json_decode(\Illuminate\Support\Facades\Artisan::output(), true);
    expect($json['feedback']['overdue'])->toBe(0);
    expect($json['feedback']['awaiting'])->toBe(1);
});

it('gives every panel a real heading and lets the keyboard into every table that scrolls', function () {
    // Statamic's card panel prints its title as a plain div, so a screen
    // reader user had one heading per screen and nothing to jump between.
    // Safari keeps the keyboard out of a scroll area that cannot take focus,
    // so the table columns past a phone's edge were out of reach.
    $this->post(cp_route('utilities.a11y-state.save'), fields(override: [
        'new_exception' => ['description' => 'Carousel cannot be paused', 'severity' => 'serious', 'criterion' => '2.2.2', 'page' => '/home'],
    ]))->assertSessionHas('success');

    config()->set('statamic-a11y-state.feedback.form', 'accessibility');
    $form = \Statamic\Facades\Form::make('accessibility')->title('Accessibility feedback');
    $form->save();
    \Statamic\Facades\FormSubmission::make()->form($form)->id((string) strtotime('-1 day'))->data(['message' => 'The menu traps focus'])->save();

    $tables = 0;

    foreach (['', '/evidence', '/feedback', '/preview'] as $screen) {
        $html = utilityHtml($screen);

        expect($html)->not->toContain('<ui-card-panel');
        expect(substr_count($html, '<ui-panel-header>'))->toBeGreaterThan(0, $screen);
        expect(substr_count($html, '<ui-panel-header><ui-heading level="2"'))->toBe(substr_count($html, '<ui-panel-header>'), $screen);

        preg_match_all('/<div class="overflow-x-auto"[^>]*>/', $html, $scrollers);

        foreach ($scrollers[0] as $scroller) {
            expect($scroller)->toContain('tabindex="0"');
            expect($scroller)->toContain('role="region"');
            expect(preg_match('/aria-labelledby="([^"]+)"/', $scroller, $m))->toBe(1, $screen);
            expect($html)->toMatch('/<caption id="'.preg_quote($m[1], '/').'"/');
            $tables++;
        }
    }

    // The exceptions, evidence and feedback tables, so the loop above was not empty.
    expect($tables)->toBe(3);
});
