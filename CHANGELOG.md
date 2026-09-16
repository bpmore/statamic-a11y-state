# Changelog

Newest first. Before 1.0, a breaking change raises the minor version.

## Unreleased

- Eloquent evidence driver: `storage.evidence_driver` set to `eloquent`
  keeps the log in a table on the connection `storage.evidence_connection`
  names, created by `php please a11y:state:install`. Same contract as the
  YAML driver: insert only, primary key on the id, no update, no delete.

## 0.1.0

First build, from the brief in `docs/build-grange.md`, where the addon
was called Grange. Renamed A11y State before release; nothing was
installed under the old name.

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
