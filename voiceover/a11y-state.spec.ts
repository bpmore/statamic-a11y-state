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

    await voiceOver.navigateToWebContent();
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

function said(spoken: string[], ...parts: (string | RegExp)[]): boolean {
    return spoken.some((phrase) => parts.every((part) => (typeof part === "string" ? phrase.toLowerCase().includes(part.toLowerCase()) : part.test(phrase))));
}

function keep(name: string, transcript: Record<string, string[]>) {
    mkdirSync("./test-results/transcripts", { recursive: true });
    writeFileSync(`./test-results/transcripts/${name}.json`, JSON.stringify(transcript, null, 2));
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

        // The refusal: the heading, then what follows it.
        await open(page, voiceOver, "/cp/utilities/a11y-state");
        const toAttest = await walk(voiceOver, voiceOver.keyboardCommands.findNextHeading);
        transcript.attest = [toAttest[toAttest.length - 1] ?? ""];
        for (let i = 0; i < 4; i++) {
            await voiceOver.next();
            transcript.attest.push(await voiceOver.lastSpokenPhrase());
        }

        keep("1-statement-editor", transcript);

        expect.soft(said(transcript.headings, "A11y State", "heading level 1"), "page title is a level 1 heading").toBe(true);
        expect.soft(said(transcript.headings, "Profile and claim", "heading level 2"), "panel title is a level 2 heading").toBe(true);
        expect.soft(said(transcript.headings, "Known exceptions", "heading level 2")).toBe(true);
        expect.soft(said(transcript.headings, "Attest to full conformance", "heading level 2")).toBe(true);
        expect.soft(said(transcript.controls, "Jurisdiction profile"), "the profile menu has its label").toBe(true);
        expect.soft(said(transcript.controls, "Partially conformant"), "the status radios have their labels").toBe(true);
        expect.soft(said(transcript.table, "Known exceptions"), "the table is named by its caption").toBe(true);
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
