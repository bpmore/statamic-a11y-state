import { voiceOverTest as test, type VoiceOverPlaywright as VoiceOver } from "@guidepup/playwright";
import { expect, type Page } from "@playwright/test";
import { mkdirSync, writeFileSync } from "node:fs";

/*
 * VoiceOver, driven the way a screen reader user moves through a page: by
 * heading, by form control, by table, and by keyboard focus. Every phrase
 * VoiceOver speaks is kept in test-results/transcripts/, so a person can
 * read what a user would have heard; the expectations below are the
 * minimum that must be in it.
 *
 * The phrasing differs between macOS versions ("Profile and claim, heading
 * level 2" or "heading level 2, Profile and claim"), so each check asks
 * only that one phrase carries both the name and the role.
 */

const MAX_STEPS = 16;

async function open(page: Page, voiceOver: VoiceOver, path: string) {
    await page.goto(path, { waitUntil: "load" });
    await page.locator("h1").first().waitFor();

    const snooze = page.getByRole("button", { name: "Snooze", exact: true });
    if (await snooze.isVisible().catch(() => false)) {
        await snooze.click();
    }

    // Guidepup waits for <body> to be visible before moving VoiceOver into
    // the page. Statamic's control panel draws everything in fixed boxes, so
    // its body is 0px tall and never counts as visible: the first run waited
    // out its whole timeout here. One pixel of height changes nothing
    // VoiceOver reads.
    await page.addStyleTag({ content: "body { min-height: 1px; }" });

    await voiceOver.navigateToWebContent();

    // VoiceOver can still be reading the page title when the first command
    // arrives; the first run's opening walk lost its headings that way.
    await page.waitForTimeout(1000);
}

/** Repeats one VoiceOver command and keeps what it says, until it says nothing new. */
async function walk(voiceOver: VoiceOver, command: unknown, steps = MAX_STEPS): Promise<string[]> {
    const spoken: string[] = [];

    for (let i = 0; i < steps; i++) {
        await voiceOver.perform(command as never);
        const phrase = (await voiceOver.lastSpokenPhrase()).trim();

        if (phrase === "" || phrase === spoken[spoken.length - 1]) {
            break;
        }

        spoken.push(phrase);
    }

    return spoken;
}

/**
 * Whether one phrase carries every part. Spaces are compared as spaces:
 * VoiceOver hands back a non-breaking space as it is, and says it the same.
 */
function said(spoken: string[], ...parts: (string | RegExp)[]): boolean {
    const plain = (text: string) => text.replace(/\s+/g, " ").toLowerCase();

    return spoken.some((phrase) => parts.every((part) => (typeof part === "string" ? plain(phrase).includes(plain(part)) : part.test(phrase))));
}

function keep(name: string, transcript: Record<string, string[]>) {
    // One file per attempt, so a retry does not overwrite what the first try heard.
    const retry = test.info().retry;
    mkdirSync("./test-results/transcripts", { recursive: true });
    writeFileSync(`./test-results/transcripts/${name}${retry ? `-retry-${retry}` : ""}.json`, JSON.stringify(transcript, null, 2));
    test.info().attach(`${name} transcript`, { body: JSON.stringify(transcript, null, 2), contentType: "application/json" });
}

test.describe("A11y State with VoiceOver in Safari's engine", () => {
    test("statement editor", async ({ page, voiceOver }) => {
        const transcript: Record<string, string[]> = {};

        await open(page, voiceOver, "/cp/utilities/a11y-state");
        transcript.headings = await walk(voiceOver, voiceOver.keyboardCommands.findNextHeading);

        await open(page, voiceOver, "/cp/utilities/a11y-state");
        transcript.controls = await walk(voiceOver, voiceOver.keyboardCommands.findNextControl, 24);

        await open(page, voiceOver, "/cp/utilities/a11y-state");
        transcript.table = await walk(voiceOver, voiceOver.keyboardCommands.findNextTable, 1);

        await page.locator('[role="region"]').first().focus();
        await voiceOver.perform(voiceOver.keyboardCommands.describeItemWithKeyboardFocus);
        transcript.scrollRegionWithFocus = [await voiceOver.lastSpokenPhrase()];

        // The refusal: stop on its heading, then read on.
        await open(page, voiceOver, "/cp/utilities/a11y-state");
        transcript.attest = [];
        for (let i = 0; i < MAX_STEPS; i++) {
            await voiceOver.perform(voiceOver.keyboardCommands.findNextHeading);
            const phrase = await voiceOver.lastSpokenPhrase();
            // Only the heading itself ends the search: VoiceOver sometimes
            // says "Last heading" in the middle of a page.
            if (phrase.includes("Attest to full conformance")) {
                transcript.attest.push(phrase);
                break;
            }
        }
        for (let i = 0; i < 4; i++) {
            await voiceOver.next();
            transcript.attest.push(await voiceOver.lastSpokenPhrase());
        }

        keep("1-statement-editor", transcript);

        // When VoiceOver starts on the title it says "You are currently on a
        // heading level 1." instead of reading it.
        expect.soft(said(transcript.headings, "A11y State", "heading level 1") || said(transcript.headings, "currently on a heading level 1"), "page title is a level 1 heading").toBe(true);
        expect.soft(said(transcript.headings, "Profile and claim", "heading level 2"), "panel title is a level 2 heading").toBe(true);
        expect.soft(said(transcript.headings, "Known exceptions", "heading level 2")).toBe(true);
        expect.soft(said(transcript.headings, "Attest to full conformance", "heading level 2")).toBe(true);
        expect.soft(said(transcript.controls, "Jurisdiction profile"), "the profile menu has its label").toBe(true);
        expect.soft(said(transcript.controls, "Partially conformant"), "the status radios have their labels").toBe(true);
        expect.soft(said(transcript.controls, "Commitment to accessibility", "Required here because the W3C model asks for it"), "a required field's note agrees with its label").toBe(true);
        expect.soft(said(transcript.controls, "not required by the regime"), "no field says required and not required").toBe(false);
        expect.soft(said(transcript.table, "Known exceptions: 2 open"), "the table is named by its caption, with its space").toBe(true);
        expect.soft(said(transcript.scrollRegionWithFocus, "Known exceptions"), "the scroll area takes focus and says what it holds").toBe(true);
        expect.soft(said(transcript.attest, "Not available"), "the refusal is read after its heading").toBe(true);
    });

    test("evidence log", async ({ page, voiceOver }) => {
        const transcript: Record<string, string[]> = {};

        await open(page, voiceOver, "/cp/utilities/a11y-state/evidence");
        transcript.headings = await walk(voiceOver, voiceOver.keyboardCommands.findNextHeading);

        await open(page, voiceOver, "/cp/utilities/a11y-state/evidence");
        transcript.controls = await walk(voiceOver, voiceOver.keyboardCommands.findNextControl, 12);

        await open(page, voiceOver, "/cp/utilities/a11y-state/evidence");
        transcript.table = await walk(voiceOver, voiceOver.keyboardCommands.findNextTable, 1);

        await page.locator('[role="region"]').first().focus();
        await voiceOver.perform(voiceOver.keyboardCommands.describeItemWithKeyboardFocus);
        transcript.scrollRegionWithFocus = [await voiceOver.lastSpokenPhrase()];

        keep("2-evidence", transcript);

        expect.soft(said(transcript.headings, "records", "heading level 2")).toBe(true);
        expect.soft(said(transcript.headings, "Record something", "heading level 2")).toBe(true);
        expect.soft(said(transcript.controls, "Action")).toBe(true);
        expect.soft(said(transcript.controls, "Criterion")).toBe(true);
        expect.soft(said(transcript.table, "Evidence records")).toBe(true);
        expect.soft(said(transcript.scrollRegionWithFocus, "Evidence records")).toBe(true);
    });

    test("feedback", async ({ page, voiceOver }) => {
        const transcript: Record<string, string[]> = {};

        await open(page, voiceOver, "/cp/utilities/a11y-state/feedback");
        transcript.headings = await walk(voiceOver, voiceOver.keyboardCommands.findNextHeading);

        await open(page, voiceOver, "/cp/utilities/a11y-state/feedback");
        transcript.table = await walk(voiceOver, voiceOver.keyboardCommands.findNextTable, 1);

        keep("3-feedback", transcript);

        expect.soft(said(transcript.headings, "submission", "heading level 2")).toBe(true);
        expect.soft(said(transcript.table, "Feedback submissions")).toBe(true);
    });

    test("public statement", async ({ page, voiceOver }) => {
        const transcript: Record<string, string[]> = {};

        await open(page, voiceOver, "/accessibility-statement");
        transcript.headings = await walk(voiceOver, voiceOver.keyboardCommands.findNextHeading);

        await open(page, voiceOver, "/accessibility-statement");
        transcript.lists = await walk(voiceOver, voiceOver.keyboardCommands.findNextList, 3);

        keep("4-public-statement", transcript);

        expect.soft(said(transcript.headings, "Accessibility statement", "heading level 1")).toBe(true);
        expect.soft(said(transcript.headings, "Known exceptions", "heading level 2")).toBe(true);
        expect.soft(said(transcript.headings, "About this statement", "heading level 2")).toBe(true);
        expect.soft(said(transcript.lists, /list/i)).toBe(true);
    });
});
