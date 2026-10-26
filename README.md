# A11y State for Statamic

A11y State keeps a site's accessibility statement and the evidence behind it.
It generates the statement from what you enter and what your testing
found, refuses to claim more than the evidence supports, and keeps an
append-only log of what was assessed, fixed, attested and answered.

It is a paid addon and runs on Statamic 6 with PHP 8.2 or newer. It works
on its own, and it works alongside A11y Report, A11y
Gate and A11y Docs when they are installed.

**Not legal advice.** A11y State produces a document. It cannot tell you
whether a site is accessible or whether any legal obligation has been met,
and nothing it generates is a certification. The organisation publishing
the statement is responsible for what it says. That sentence is in the
control panel, in the generated statement, and here.

## Status

This is 0.1.0, the first release. It includes the profiles, the statement editor, the attestation step, the
evidence log with CSV and JSON export, the public page and tag, Markdown
and HTML export, the feedback clock, `a11y:state:check`, and the optional
A11y Report integration. What is not built is in "What it does
not do" below.

## Install

```
composer require bpmore/statamic-a11y-state
```

Optionally publish the config file to change the route, the storage
paths, the rules or the profiles:

```
php please vendor:publish --tag=statamic-a11y-state-config
```

Then open Utilities, A11y State, in the control panel.

## What it stores, and where

Two directories under `content/a11y-state/`, both YAML, both meant to live in
the site's repository beside its content:

- `statements/<site>.yaml`: one file per site with the profile, the
  claim, every disclosure, the known exceptions and the attestation.
- `evidence/<id>.yaml`: one file per evidence record, written once. There
  is no update and no delete anywhere in the addon, and the files are
  made read-only after writing. A correction is a new record that names
  the one it corrects; both stay, and both are exported.

Both paths can be moved in the config file. A site that already runs a
database can keep the evidence log in a table instead: set
`storage.evidence_driver` to `eloquent`, name a connection in
`storage.evidence_connection` (or leave it null for the site's default),
and run `php please a11y:state:install` once. The table's id is its
primary key, so the database refuses a second write of the same record
the way the file driver does, and the driver has no update and no delete
either. The statement itself stays in YAML on both drivers.

## The rule that governs the design

**The addon never asserts a conformance level the site has not
evidenced.**

- A statement starts as **not assessed**, and can be set to
  **non-conformant** or **partially conformant** in the editor.
- **Fully conformant** is not a choice in the editor. It is reached only
  through the attestation step: a named person, a date, and the basis for
  the claim, written to the evidence log and printed on the statement.
  Attesting has its own permission.
- While any open exception at or above the configured severity exists,
  in the statement or in an integration's findings, full conformance is
  refused and the refusal names the exceptions. The default severity is
  `minor`, which means every open exception, because the W3C's definition
  of fully conformant is "without any exceptions".
- A statement whose assessment date is older than the configured window
  (365 days by default) shows a notice in the control panel and flags in
  `a11y:state:check`. The published statement is not changed.
- A profile's required disclosure that is empty stops generation, and
  the refusal names the disclosure. The public page then shows a short
  "being prepared" notice rather than a statement with holes in it.

## Profiles

A profile is the list of disclosures a regime expects and which of them
it requires. Five ship: **Generic (W3C)**, **EN 301 549 (EU)**, **Section
508 (US federal)**, **ADA Title II (US state and local government)** and
**AODA (Ontario)**. Every disclosure in every profile names the text it
came from, printed under the field in the editor and traced in
[docs/profiles.md](docs/profiles.md). Where a regime's own text does not
ask for a disclosure, the field says so and the requirement is the W3C
model's.

Profiles are data in the config file. Add a disclosure with a key, a
label and a source, and the editor, the completeness check and the
document all pick it up. Switching profile never drops a value: a
disclosure with no field under the new profile is kept on the statement,
the editor lists it, and the save message names it.

## The statement's page

Every site gets its statement at `/accessibility-statement`. Not
`/accessibility`, which A11y Report serves when it is installed;
the two can run side by side and a site with both picks one to link to.
Change or turn off the route in the config file.

To put the statement inside a page of your own:

```
{{ a11y_state:statement }}                    Antlers, headings from h2
{{ a11y_state:statement heading="1" }}
<s:a11y_state:statement heading="2" />        Blade
```

The output is plain HTML: one heading for the title, one level down for
each section, paragraphs and lists, no script, and a `lang` attribute.
The tag also links a small stylesheet, served by the addon at
`/a11y-state/statement.css`, so the statement reads well on a site whose
CSS resets headings and lists: heading sizes, bullets, a readable width.
Its rules weigh no more than a plain element rule, so any rule of your
theme's that names a class wins. Set `statement_styles` to `false` to
style the `<article class="a11y-state-statement">` yourself. It was checked with axe-core against the
WCAG 2.1 A and AA rules and with a keyboard walk of every control panel
screen. A site whose layout is Blade with `@yield` has no layout Statamic
can wrap the page in; the page then renders in a plain shell of the
addon's own, or set `view` in the config file to a template that calls
the tag.

Markdown and a complete HTML page are downloadable from the editor and
the preview. Both refuse when the statement cannot be generated, for the
same reasons and with the same message.

## Evidence

The log is the record a compliance officer asks for. Each record has a
timestamp, the actor, an optional WCAG success criterion, an optional page
or asset, an action from a closed list, and notes. Records are written by
hand on the Evidence screen, by the editor when the statement is saved,
when an exception is added or resolved, and when an attestation is made,
and by the feedback screen when a submission is acknowledged. The actor is
the signed-in user and is not a field.

Filters narrow the view. Exports carry the filter, or the whole log when
there is none, as CSV (with a byte order mark, for spreadsheets) or JSON.
A corrected record is exported with the id of the record that corrected
it.

## Feedback

Set `feedback.form` to the handle of the Statamic form that receives
accessibility feedback. The Feedback screen lists every submission with
how long it has waited. Acknowledging one writes an evidence record, which
is what stops its clock.

Set `feedback.response_days` to start the clock. Submissions waiting
longer than that are reported as past the window, in the control panel
and by `a11y:state:check`, and the statement says "We aim to acknowledge
feedback within N days". With no window configured nothing is overdue and
the statement says nothing about response times: a promise nobody made is
not a promise.

## `a11y:state:check`

```
php please a11y:state:check
php please a11y:state:check --json | jq .
php please a11y:state:check --site=default --fail-on=incomplete,overclaim
```

Exit codes: 0 nothing in `--fail-on` was found, 1 something was, 2 the
input was wrong. `--fail-on` takes any of `incomplete`, `stale`,
`overclaim` and `feedback`, and is all four when omitted, so a team can
start with `--fail-on=incomplete` and add the rest as it goes. `--json` is
the whole of stdout with nothing else on it.

When A11y Report is installed, the check also imports its fixes
into the evidence log, keyed on the finding so a second run writes
nothing twice. `--no-import` turns that off.

## Integrations

A11y Report, when installed and set up, feeds A11y State in two ways:
its open issues are listed among the known exceptions and count against
full conformance, and its fixes can be imported into the evidence log,
from the Evidence screen or by `a11y:state:check`. Nothing is written back to
the report.

The integration is an interface with a null adapter. With no other
`bpmore/*` addon installed every feature works, and exceptions are entered
by hand. The test suite boots A11y State alone for exactly that reason.

## Permissions

Reading the statement and the log is Statamic's own "access utility"
permission. Three more are registered under "A11y State":
edit the statement, attest to full conformance, and record evidence. They
are separate because attesting is a different act from editing text, and
a site may want different people doing each.

## Config

Published to `config/statamic-a11y-state.php`. The keys:

- `route`, `view`, `layout`: the public page.
- `statement_styles`: the statement's own stylesheet, on by default.
- `storage.statements`, `storage.evidence`: the two directories.
- `storage.evidence_driver` (`yaml` or `eloquent`) and
  `storage.evidence_connection`.
- `overclaim_severity`: the severity at or above which an open exception
  refuses full conformance. `minor` by default.
- `stale_after_days`: 365 by default, from the EU model statement's
  "at least once per year".
- `feedback.form`, `feedback.response_days`.
- `profile`: the default for a new statement. `profiles`: the data.

## What it does not do

- It does not test the site. It records what testing found. A11y
  Report and A11y Gate test; A11y State keeps the statement and the
  evidence.
- It does not generate a VPAT or an Accessibility Conformance Report.
  The evidence model does not stand in the way of one later.
- It does not give legal advice, and the profiles do not say what the
  law means. They say what a statement under a regime is expected to
  contain and where that expectation is written down.
- It does not edit or delete evidence, and will not grow a way to.

## Verified, and not

The suite is 136 tests, Pest, green on PHP 8.4 with Statamic 6.31 and on
PHP 8.2 with the oldest dependencies, in CI. The
seven rules the addon exists to enforce each have their own test, and
each was mutation-tested: the rule it guards was broken and the test went
red. The addon was installed by path on a scratch Statamic site with
A11y Report and A11y Gate beside it, the editor,
attestation, evidence, feedback and public page were driven by hand, a
real finding from a real scan reached the statement and its fix reached
the log, and `--json` was piped through `jq`. axe-core found no WCAG 2.1
A or AA violation on the public page or any control panel screen, and a
keyboard walk reached every control with a visible focus ring. On 23
October 2026 every screen was checked at 390 pixels wide in Chromium: no
sideways page scroll, a level-two heading on every panel, and each wide
table reachable by keyboard and scrolled with the arrow keys.

Not done: a pass with a real screen reader (the accessibility tree was
read, not listened to), Safari itself, and a production site.

## Support

Report a bug or ask a question by opening an issue:

<https://github.com/bpmore/statamic-a11y-state/issues>

Every bug report gets a reply. Aim for a few working days. There is no
guaranteed response time, and nothing here phones home, so a support
problem is never an outage: the statement keeps being served and the log
keeps being written whatever happens to the addon's author.

A useful report says which version of the addon and of Statamic you are
on: `composer show bpmore/statamic-a11y-state statamic/cms`.

## Licence

Proprietary. See `LICENSE.md`.
