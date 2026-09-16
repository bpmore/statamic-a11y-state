# Grange: Build Brief

Package `bpmore/statamic-grange`, namespace `Bpmore\Grange`, repo `~/Herd/statamic-grange`.
Read `build-conventions.md` first. Paid addon, target $99.

---

## 1. What it is

Generates and maintains the accessibility statement, and keeps the evidence behind it. Every regulated entity must publish a statement, and almost none maintains one that reflects reality.

**Market check, verified September 2026:** zero results across 497 listings for statement, VPAT, conformance, 508, or Title II, other than a passing mention in `statamic-a11y-report`.

**Timing:** the ADA Title II web rule compliance dates are April 24, 2026 for public entities serving 50,000 or more, and April 26, 2027 for smaller ones. **Verify both dates against the current rule text before any of this reaches marketing copy.** Do not restate them from this brief.

## 2. The rule that governs the whole design

**The addon must never assert a conformance level the site has not evidenced.**

This is a legal-adjacent document. An addon that cheerfully generates "fully conformant" because that is the default select option is a liability generator, and it would be embarrassing coming from this vendor specifically.

Concretely:
- Conformance status is `non-conformant`, `partially conformant`, or `fully conformant`, following the W3C statement model
- **`fully conformant` requires an explicit, dated, named human attestation** recorded in the evidence log, not merely a select choice. Implement it as a separate confirmation step with its own field for who attested and on what basis
- If any open exception exists at or above a configured severity, `fully conformant` is refused with a clear explanation, not a silent downgrade
- A statement whose assessment date is older than a configured staleness window renders a visible notice in the CP and flags in the audit command

If any of this conflicts with how you read the relevant standard, stop and ask. Do not resolve it yourself.

## 3. Jurisdiction profiles

Each profile declares the disclosures its regime requires. Profiles are data, in publishable config, not hardcoded classes.

- **ADA Title II** (US public entities)
- **Section 508** (US federal and recipients)
- **EN 301 549 and the European Accessibility Act**
- **AODA** (Ontario)
- **Generic W3C** as the fallback

Common disclosure set across profiles: conformance status, the standard with version and level, scope of the claim (which URLs, which content types), known exceptions with reasons and remediation dates, the feedback mechanism, the assessment date, the evaluation method, the preparation date, and contact details.

A profile marks each disclosure required or optional. **The generator refuses to produce a statement with a required disclosure missing**, and names which one.

Do not write legal guidance into the profiles. They encode which fields a regime expects, not what the law means. Ship a plain disclaimer: this addon produces a document, it does not provide legal advice.

## 4. Evidence log

Append-only. Each record: timestamp, actor, WCAG success criterion, the page or asset, the action taken, and free-text notes.

- Storage behind an interface. Flat-file YAML default, Eloquent driver optional. Ask before adding a migration
- **No edit or delete path in the UI.** Corrections are new records that supersede, and the superseded record remains. That is what makes it evidence rather than a content field
- Export to CSV and JSON. The compliance buyer needs to hand this to someone
- Records are immutable once written: enforce it in the storage layer, not only in the UI

## 5. Integrations, all optional

`statamic-a11y-report`, `statamic-a11y-docs`, and `statamic-a11y-gate` may be present. If they are, open findings feed the known-exceptions list and fixes feed the evidence log automatically.

Implement through an interface with a null adapter. **Grange must install and work standalone**, with exceptions entered by hand. Do not create a hard dependency on the other addons.

## 6. Surfaces

- CP utility: statement editor, completeness check against the selected profile, evidence log viewer with filters, export
- Route or entry rendering the published statement, themeable, as accessible HTML. The accessibility statement being inaccessible is the joke everyone makes, so do not make it available
- Feedback mechanism built on Statamic Forms: acknowledgment clock, a report of submissions past the configured response window, and no claim about response times the site has not configured
- `php please grange:check` following the conventions command rules: exits non-zero when the statement is incomplete or stale
- Markdown and HTML export of the statement itself

## 7. Discriminating tests

1. **Refusal to overclaim.** With one open exception at or above the configured severity, `fully conformant` is refused, and the refusal names the exception.
2. **Attestation required.** `fully conformant` cannot be persisted without a recorded attestation containing a name and a date. Try to persist it without one and assert failure.
3. **Required disclosure enforcement.** Each profile, with one required disclosure removed, fails generation and names the missing disclosure. Parameterize over all profiles.
4. **Evidence immutability.** An attempt to modify a written record through any public path fails. An attempt to delete one fails. A superseding record links to its predecessor and both remain readable.
5. **Staleness.** A statement with an assessment date older than the window flags in `grange:check` and renders a CP notice, without altering the published statement.
6. **Standalone operation.** With no other `bpmore/*` addon installed, every feature works and the null adapters are exercised.
7. **Profile isolation.** Switching profiles does not silently drop already-entered data belonging only to the previous profile: it warns and preserves.

## 8. Definition of done

Conventions file section 10, plus:

- Generated statement output audited against WCAG 2.1 AA
- Every profile's disclosure list traced to a citable source, recorded in a `docs/profiles.md` with the source named per field. **Do not invent requirements from memory.** Where a source is unavailable, mark the field as inferred and stop to ask
- Export formats validated
- Legal disclaimer present in the README, the CP utility, and the generated document

## 9. Explicit non-goals

Not a scanner: it does not test the site, it records what testing found. Not a VPAT or ACR generator in v1, though the evidence model should not preclude one later. Not legal advice, stated everywhere.
