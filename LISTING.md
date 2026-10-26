# Marketplace listing copy

Copy for the Statamic marketplace listing. The marketplace page's
description is pulled from README.md, so the README's opening, "What you
get" and "A11y State or A11y Report?" carry the long description below;
change them together. Every claim here is checked
against the 0.1.2 release; keep it that way when the product changes.

---

## Name

**A11y State**

Listing name:

> **A11y State: Accessibility Statements**

"Accessibility statement" is what the buyer searches for: a council, a
university, a hospital that has to publish one. Shortened by the owner on
26 October 2026 from "...That Match the Evidence"; the one-liner below
still carries that idea.

## Price

$19 per site (one production install, however many sites it hosts).

Set by the owner on 26 October 2026 (first $79, then $29, down from the
build brief's $99).

## One-liner

An accessibility statement that never claims more than your evidence
supports.

## Summary (marketplace card)

Write and keep your accessibility statement in Statamic, with a log of
the evidence behind it and no claim the evidence does not support.

## Long description

Most accessibility statements are written once and left alone. The site
changes, the known problems change, and the statement keeps saying what
it said the day it was published. A11y State keeps the statement and the
evidence behind it in one place, and it will not let the two drift apart.

**It refuses to overclaim.** A statement starts as "not assessed".
"Fully conformant" is not an option in the editor. It is reached only by
an attestation: a named person, a date, and the basis for the claim,
written to the evidence log and printed on the statement. While any known
problem is open, the attestation is refused, and the refusal names the
problem.

**It knows what your regime asks for.** Five profiles ship: Generic
(W3C), EN 301 549 (EU), Section 508 (US federal), ADA Title II (US state
and local government) and AODA (Ontario). Each lists the disclosures a
statement under that regime is expected to contain, and every field
names the text it came from. A statement missing a required disclosure
is not published; the page says the statement is being prepared, and
the editor says exactly what is missing.

**It keeps the evidence.** Every save, every known problem opened or
resolved, every attestation and every check you record by hand goes into
an append-only log: who, when, which WCAG criterion, which page. Nothing
in the addon can edit or delete a record. A correction is a new record
that names the one it corrects, and both stay. Filter the log and export
it as CSV or JSON for whoever asks.

**It tells you when the statement goes stale.** An assessment older than
a year (configurable) is flagged in the control panel and by the check
command. The published statement is never changed behind your back.

**It answers feedback on the record.** Point it at a Statamic form and
the Feedback screen shows every accessibility report with how long it has
waited. Acknowledging one writes an evidence record. The statement only
promises a response time if you set one.

**It checks itself in CI.** `php please a11y:state:check` reports missing
disclosures, stale assessments, overclaims and overdue feedback, with a
JSON mode and exit codes, so a pipeline can fail when the statement and
the evidence disagree.

### A11y State or A11y Report?

A11y Report, our scanning addon, also publishes a statement, at
`/accessibility`. Its conformance claim follows the latest scan, and what
it says about your organisation is set in the config file. If a statement
that mirrors your scans is all you need, A11y Report's is enough.

A11y State is for the statement you maintain: the regime's disclosures,
problems found by people as well as tools, a named attestation, and the
log an auditor asks for. It works on its own. With A11y Report installed,
the report's open issues join the known problems and its fixes join the
evidence log. A11y State publishes at `/accessibility-statement`, so the
two can run side by side.

### What it does not do

It does not test your site; it records what your testing found. It does
not produce a VPAT or an Accessibility Conformance Report. And it is not
legal advice: it produces a document from what you enter, it cannot tell
you whether a legal obligation has been met, and the organisation
publishing the statement is responsible for what it says. The control
panel and every generated statement say so too.

## Features list

- Five jurisdiction profiles as config data, every disclosure traced to
  its source
- Four conformance statuses; "fully conformant" only by a named, dated
  attestation, refused while a known problem is open
- Publishing refused, with the missing disclosure named, until the
  statement is complete
- Known problems with severity, WCAG criterion, page, planned fix date
  and reason
- Append-only evidence log with filters and CSV and JSON export; YAML
  files by default, or a database table
- Staleness notice after a configurable window, without changing the
  published statement
- Feedback clock on a Statamic form, with acknowledgment as evidence
- Public page at `/accessibility-statement` with readable default styles
  any theme can override, an Antlers and Blade tag, and Markdown and HTML
  downloads
- `a11y:state:check` with `--json`, `--fail-on` and exit codes for CI
- Separate permissions for editing, attesting and recording evidence
- Optional A11y Report integration; everything works without it
- Multisite: one statement per site

## Requirements

Statamic 6.31 or newer on PHP 8.2 or newer. Works on Statamic Core; the
separate permissions need Pro, as all Statamic roles do. No database
required.

## Screenshots

In `docs/screenshots/`, taken from the 0.1.2 code on a fresh Statamic
6.38 site with demo content for a made-up county:

1. `1-editor.jpg`: the statement editor under the ADA Title II profile,
   complete, with the profile's source named under the menu and "fully
   conformant" explained rather than offered.
2. `2-exceptions-and-refusal.jpg`: two known problems, and the
   attestation refused with both named.
3. `3-evidence.jpg`: the evidence log, with filters, exports, and records
   written by the editor and by hand.
4. `4-public-statement.jpg`: the published statement at
   `/accessibility-statement`, with its own readable default styles.

Caption the first with the one-liner.

## Categories

| | |
|---|---|
| **Utility** | Primary. The same category the other A11y addons lead with. |
| **CLI** | `a11y:state:check` with a JSON mode and exit codes. |

## Keywords

accessibility statement, accessibility, a11y, WCAG, ADA Title II,
Section 508, EN 301 549, AODA, conformance, compliance

## Support

GitHub Issues: <https://github.com/bpmore/statamic-a11y-state/issues>
