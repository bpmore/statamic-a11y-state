# Decision log

The **why**, and the **alternatives that were rejected**, behind choices that
are not obvious from the code they produced. Newest first, headed
`## YYYY-MM-DD: what was settled`, with a `---` between entries. Entries are
not edited when they stop being true; a reversal gets a newer entry.

---

## 2026-10-23: Panels carry real headings, and wide tables take keyboard focus

A phone-width pass over the control panel found two things a screen reader
or keyboard user could not do.

**Headings.** `<ui-card-panel heading="...">` prints its title as a `div`,
so every screen had one heading (the page title) and nothing to jump
between. Statamic's own `ui-heading` takes a `level`, so each panel is now
`ui-panel` > `ui-panel-header` > `ui-heading level="2"` > `ui-card`, which
is what `ui-card-panel` renders, with the title as an `h2`. The title goes
in through `text`, printed as text; `ui-card-panel` set it as `innerHTML`.
A test fails if `ui-card-panel` comes back or a panel title is not an `h2`.

**Tables.** The tables scroll sideways at phone width (17 September).
Safari does not let the keyboard into a scroll area that cannot take
focus, so the columns past the edge were out of reach. Each scroll area
now has `tabindex="0"` and `role="region"`, named by the table's caption.

**Rejected:** a script that adds focus only when the table overflows (the
screens work with no script, and an extra tab stop on a wide screen costs
little); asking Statamic to change `ui-card-panel` (the fix is available
in our own markup today).

---

## 2026-09-16: An Eloquent evidence driver, and the inferred profile fields stay

**The driver.** The brief allowed a database driver as an option and said
to ask before adding a migration; the owner asked for it on 2026-09-16.
One table, the id as primary key, `append` as an INSERT and nothing else,
the same two refusals as the YAML driver, and a test that reads the
driver's source for any other kind of write. The migration runs only
through `a11y:state:install`, as A11y Report's do, so there is one path
and one record of what ran, and a flat-file site never has the table.
YAML stays the default. The statement file itself stays YAML on both
drivers: it is content, and one file per site in the repository is where
content belongs.

**Rejected:** a database trigger refusing UPDATE and DELETE (dialect
specific, and a person with database access can drop it; the answer to
that person is backups and access control, as for the files); a hash
chain across rows (machinery the guarantee does not need).

**The profiles.** The ADA Title II and AODA profiles mark `commitment`,
`conformance_status` and `contact` required although neither regime
requires a statement document. The owner confirmed on 2026-09-16 that
they stay as they are: a statement without them is not one by the W3C's
minimum, and a site that disagrees can set them optional in its
published config.

---

## 2026-09-16: The addon is A11y State, not Grange

The brief named it Grange. The owner renamed it on 2026-09-16 to sit
beside A11y Gate, A11y Docs and A11y Report, and because the name should
say what it does: it keeps the site's accessibility statement and the
state of the evidence behind it.

**Everything moved, not only the display name:** package
`bpmore/statamic-a11y-state`, namespace `Bpmore\A11yState`, folder,
GitHub repository, config file and keys, command `a11y:state:check`, tag
`a11y_state:statement`, utility and permission slugs `a11y-state`,
directive `@a11yStateText`, storage `content/a11y-state/`, CSS classes.
Nothing was installed anywhere, so there was nothing to migrate.

**Rejected:** a display-name-only rename, which would have carried two
names for the life of the product. The brief and conventions documents in
`docs/` keep the old name because they are the owner's record of what was
asked for.

---

## 2026-09-15: Four conformance statuses, with "not assessed" as the start

The brief names three (non-conformant, partially conformant, fully
conformant) "following the W3C statement model". The W3C generator, read on
the day, offers a fourth: "Not assessed: the content has not been evaluated
or the evaluation results are not available".

**Kept the fourth.** A statement that must pick one of three on the day it
is created guesses on the day it is created, and the whole product exists
to stop that. "Non-conformant" as a default is also a claim, and a false
one for a site nobody has looked at. Every new statement starts as not
assessed; a profile that requires the exceptions list does not require it
of a statement that has not looked.

**Rejected:** three statuses with "non-conformant" as the default (a false
claim); three statuses with no default and a refusal to save until one is
chosen (which makes the first save a guess under pressure).

**Confirmed by the owner on 2026-09-15:** four statuses stay. Still open:
the EU model has three and no "not assessed"; whether the EU profile should
refuse to generate while unassessed is flagged in `docs/profiles.md`.

---

## 2026-09-15: The route is `/accessibility-statement`, not `/accessibility`

A11y Report already serves `/accessibility` with a statement of
its own, derived from its latest report. The brief's market check called
that a "passing mention"; it is a working feature with a route, a tag and
config. Two addons registering one path is a collision the second to boot
loses silently.

**Kept both.** A11y State takes `/accessibility-statement` by default and the
config file says why. A site with both picks one to link to.

**Rejected:** taking `/accessibility` and documenting the clash (the clash
is silent, so documentation is the only warning); refusing to boot when
the report is present (A11y State must work beside it, and the report's
statement is a different document with a different source of truth).

**Confirmed by the owner on 2026-09-16:** the route stays
`/accessibility-statement`. Still open: whether A11y State should replace the
report's statement in a later release of the report, or read the report's
conformance as a further source.

---

## 2026-09-15: `a11y:state:check` imports fixes from the integrations

The brief says fixes from the other addons "feed the evidence log
automatically". The only place a write can happen without a person's
action and without a page view causing a write is a command, and
`a11y:state:check` is the command a site already runs on a schedule or in CI.

**Kept:** the import runs on every check unless `--no-import`, keyed on the
finding so a second run writes nothing twice. The Evidence screen has a
button for the same import for a person who wants it now.

**Rejected:** importing on the Evidence screen's load (a read that writes);
a queue listener on the report's scan (a dependency on the report's events
and a code path that runs only when it is installed, untestable alone).

---

## 2026-09-15: One YAML file per evidence record, made read-only

The brief asks for immutability enforced in the storage layer, not only in
the UI. A single file holding every record can only be appended to by
rewriting the file, and a rewrite is the operation the store must not
have.

**Kept:** one file per record, created once, written to a temporary name
and renamed into place, refused if the id exists (checked before the write
and again before the rename), `chmod 0444` afterwards. Ids carry a
millisecond time prefix so a directory listing is the log's order. The
interface has `append`, `find`, `all`, `filter`, `count` and nothing else,
and a test asserts exactly that list.

**Rejected:** SQLite or Eloquent by default (the brief says flat-file
default, and asks before a migration; the interface is there for a driver
later); an append-only single file with a hash chain (more machinery than
the guarantee needs, and still a rewrite on every append).

**Known limit:** the read-only bit is a courtesy. Root, or `chmod`, can
undo it, and git can rewrite history. The refusal to write over an id is
the rule; the log's own history is git's.
