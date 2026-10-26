# Changelog

Newest first. Before 1.0, a breaking change raises the minor version.

## Unreleased

- The known exceptions table is announced as "Known exceptions: 2 open"
  rather than "Known exceptions:2 open". A space inside the hidden part of
  the caption was dropped from the table's name, as VoiceOver read it in
  CI.
- A field the regime's own text does not ask for says why it is there in
  words that agree with its label: "Required here because the W3C model
  asks for it" or "From the W3C model". VoiceOver read "(required)" and
  then "not required by the regime's own text", which sounded like a
  contradiction.
- A VoiceOver run in CI (macOS, Safari's engine) walks the statement
  editor, the evidence log, the feedback screen and the public statement,
  and posts what VoiceOver said on the run's summary page.

## 0.1.2 - 2026-10-26

- A11y Report findings are listed once per problem and page. The report
  keeps a row per element, so two images with no description on one page
  printed the same sentence twice in the statement. They are now one known
  exception that says "Found in 2 places", at the worst severity among
  them.
- The public statement reads well on a site whose CSS resets headings and
  lists. The tag links a small stylesheet of its own: heading sizes,
  bullets, a readable width and margins. Its rules weigh no more than a
  plain element rule, so a theme rule that names a class wins, and
  `statement_styles` set to `false` turns it off.
- A reason or description typed with a full stop no longer prints two
  ("made on request..").
- The control panel tables have space between their columns. They used
  `pr-3`, which Statamic's stylesheet does not have; they now use `pe-3`.

Nothing to do: `composer update bpmore/statamic-a11y-state`. To style the
statement entirely yourself, set `statement_styles` to `false`.

## 0.1.1 - 2026-10-26

- The control panel icon shows. The header and the Utilities listing named
  an icon Statamic does not have (`list-checklist`), so both showed a blank
  space. They now use `document-certificate`, which is in Statamic 6.27
  and in 6.38, and a test fails on any icon name the installed Statamic
  does not have.

Nothing to do: `composer update bpmore/statamic-a11y-state`.

## 0.1.0 - 2026-10-26

The first release. Everything below is new.

- Five jurisdiction profiles as config data, every disclosure traced to
  its source in `docs/profiles.md`.
- Four conformance statuses, with "fully conformant" reachable only
  through a named, dated attestation with a basis, refused while any open
  exception at or above the configured severity exists.
- Required-disclosure enforcement: generation refuses and names the
  missing disclosure.
- Append-only evidence log, one YAML file per record, no edit or delete
  path, corrections by superseding record, CSV and JSON export.
- Staleness notice in the control panel and in `a11y:state:check`, without
  changing the published statement.
- Profile switching that keeps and names disclosures the new profile has
  no field for.
- Public page at `/accessibility-statement`, the `a11y_state:statement` tag,
  Markdown and HTML export.
- Feedback clock on a Statamic form, acknowledgment as an evidence
  record, an overdue report, and no response-time claim unless one is
  configured.
- `php please a11y:state:check` with `--json`, `--fail-on` and exit codes
  0, 1, 2.
- Optional A11y Report integration behind an interface with a
  null adapter: open issues join the exceptions, fixes join the log.
- Eloquent evidence driver: `storage.evidence_driver` set to `eloquent`
  keeps the log in a table on the connection `storage.evidence_connection`
  names, created by `php please a11y:state:install`. Same contract as the
  YAML driver: insert only, primary key on the id, no update, no delete.
- Control panel structure: every panel title is a heading, and each wide
  table scrolls sideways on a narrow screen, takes keyboard focus, and is
  named by its caption.
