# Decision log

The **why**, and the **alternatives that were rejected**, behind choices that
are not obvious from the code they produced. Newest first, headed
`## YYYY-MM-DD: what was settled`, with a `---` between entries. Entries are
not edited when they stop being true; a reversal gets a newer entry.

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

Accessibility Report already serves `/accessibility` with a statement of
its own, derived from its latest report. The brief's market check called
that a "passing mention"; it is a working feature with a route, a tag and
config. Two addons registering one path is a collision the second to boot
loses silently.

**Kept both.** Grange takes `/accessibility-statement` by default and the
config file says why. A site with both picks one to link to.

**Rejected:** taking `/accessibility` and documenting the clash (the clash
is silent, so documentation is the only warning); refusing to boot when
the report is present (Grange must work beside it, and the report's
statement is a different document with a different source of truth).

**Open:** whether Grange should replace the report's statement in a later
release of the report, or read the report's conformance as a further
source. Not decided here.

---

## 2026-09-15: `grange:check` imports fixes from the integrations

The brief says fixes from the other addons "feed the evidence log
automatically". The only place a write can happen without a person's
action and without a page view causing a write is a command, and
`grange:check` is the command a site already runs on a schedule or in CI.

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
