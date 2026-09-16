# CLAUDE.md

Non-negotiables for any model working in this repository. Rules, not
documentation. Obey them without asking for confirmation.

This is a commercial addon whose entire value is that the statement it
generates says no more than the evidence supports. Most rules here exist to
protect that.

## THE PRODUCT

- A11y State generates and maintains the accessibility statement and keeps the
  evidence behind it. It does not test the site. It is not a VPAT or ACR
  generator. It is not legal advice, and it says so in the README, the
  control panel and every generated document.
- **The addon never asserts a conformance level the site has not
  evidenced.** "Fully conformant" is reached only through the attestation
  step (`Statements::attest`): a name, a date, a basis, an evidence record.
  The status control does not offer it. The repository guard
  (`Storage\Guard`) refuses to persist it without an attestation, and that
  guard is called by every driver.
- An open exception at or above `overclaim_severity`, manual or from an
  integration, refuses full conformance, and the refusal names the
  exception. Never a silent downgrade.
- A required disclosure that is missing refuses generation and names the
  disclosure. There is no "generate anyway".
- Staleness is a warning, never an error, and never changes the published
  statement.
- The evidence log is append-only. There is no update and no delete on
  `EvidenceLog`, in any driver, in any controller, on any route, and there
  must never be. A correction is a new record that supersedes; both stay.
  The actor on a record is the signed-in user and is never a form field.
- Switching profile never drops a disclosure. Orphans are kept, listed, and
  named in the save message.
- The statement makes no claim about response times unless
  `feedback.response_days` is set.
- Profiles are data in the config file. Every disclosure names a source.
  `docs/profiles.md` traces every one; a field the regime's text does not
  ask for is marked inferred there and in the config. Do not add a
  requirement from memory. Find the text or mark it inferred and ask.

## ARCHITECTURE

- `src/Statement`, `src/Evidence`, `src/Feedback`, `src/Storage` and the
  `FindingSource` interface are pure PHP: no Statamic facades, no Illuminate
  container. The unit suite boots nothing and must stay that way. The one
  exception is `Statement\StatementRoute`, which reads config and is named
  for the seam it sits on. `src/Database` is the other side of the seam: the
  Eloquent evidence driver takes a `ConnectionInterface` and nothing else,
  and `EvidenceDatabase` is the only class that reads config for it.
- The Eloquent driver's `append` is an INSERT and nothing else. A test
  greps its source for any other write. The migration runs only through
  `a11y:state:install`, never `loadMigrationsFrom`.
- The integrations are adapters behind `FindingSource` with a null source.
  A11y State installs and works with no other `bpmore/*` addon, and the test
  suite proves it by booting A11y State alone. An adapter resolves the other
  addon's classes by name and asks `isAvailable()` before doing anything.
- Every controller and the command go through `Statements`, so a rule is
  applied the same way from every surface.
- No licence check, ever. Statamic shows its own banner.
- No new Composer dependency without asking. `symfony/yaml` is used because
  Statamic already requires it.

## CONTROL PANEL

- Utility views compile as Vue templates. Every data value goes through
  `@a11yStateText`, never `{{ }}`. A directive glued to a word (`x@endif`) is
  printed as text; leave whitespace around directives.
- Native form controls with visible labels, inside plain forms, so every
  screen works with no script. No `aria-label` on non-interactive elements.
  Nothing means anything by colour alone.
- Only classes the CP stylesheet actually has. `list-disc`, `pl-*`,
  `align-top`, `border-amber-600` and `prose-invert` are not in it (checked
  against Statamic 6.31); `ps-*`, `border-amber-400`, `align-middle` are.
  Check `resources/dist/build/assets/app-*.css` before using a new one.
- The directive is `@a11yStateText`, the utility slug `a11y-state`, the permission
  group `a11y-state`, the route `/accessibility-statement`. A11y Report
  owns `@plain`, `a11y-report`, and `/accessibility`. Do not collide.

## TESTING

- The seven discriminating tests in the brief are `tests/Unit/OverclaimTest`,
  `AttestationTest`, `RequiredDisclosureTest`, `EvidenceImmutabilityTest`,
  `StalenessTest` (with the command and CP halves in `tests/Feature`),
  `tests/Feature/PublicPageTest` (standalone) and `ProfileIsolationTest`.
  Mutation-test a rule before trusting its test: break it and confirm red.
- `RequiredDisclosureTest` is parameterised from the shipped config, so a
  new profile is covered without a test being written. Keep it that way.
- Forms and roles leak between feature tests through the harness's
  resource path; `TestCase::forgetLeakedFixtures` clears them.
- Verify against a scratch Statamic site before calling anything done.
  `README.md`, "Verified, and not", says what was checked last time.

## WRITING

- No em dashes anywhere: code, comments, config, views, docs, output.
  `tests/Feature/PublicPageTest` sweeps the repository for them.
- Plain words. No "seamlessly", "powerful", "simply".
- Comments say why, and name the failure that motivated the rule.
- Add an entry to `docs/DECISIONS.md` when a change settles a question.
