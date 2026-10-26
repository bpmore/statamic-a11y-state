import { test as setup, expect, type Page } from "@playwright/test";

/*
 * Signs in and fills a demo statement through the control panel the way a
 * person would, so the VoiceOver walk has headings, two known problems, a
 * refused attestation, evidence records and a feedback submission to read.
 * No screen reader runs here.
 */

const email = process.env.VO_EMAIL ?? "voiceover@example.test";
const password = process.env.VO_PASSWORD ?? "";

async function snoozeLicensingAlert(page: Page) {
    const snooze = page.getByRole("button", { name: "Snooze", exact: true });
    if (await snooze.isVisible().catch(() => false)) {
        await snooze.click();
    }
}

setup("sign in and fill a demo statement", async ({ page }) => {
    await page.goto("/cp/auth/login");
    await page.locator('input[name="email"]').fill(email);
    await page.locator('input[name="password"]').fill(password);
    await page.getByRole("button", { name: "Continue" }).click();
    await page.waitForURL(/\/cp(\/dashboard)?$/);
    await snoozeLicensingAlert(page);

    await page.goto("/cp/utilities/a11y-state");
    await page.locator("main h1").waitFor();

    const field = (name: string) => page.locator(`[name="${name}"]`);
    await field("profile").selectOption("ada_title_ii");
    await field("disclosures[organization]").fill("Riverbend County");
    await field("disclosures[commitment]").fill("Riverbend County is committed to making its website usable by every resident.");
    await field("disclosures[standard]").fill("WCAG 2.1 level AA");
    await field("disclosures[contact]").fill("accessibility@riverbend-county.example");
    await field("disclosures[assessment_date]").fill(new Date().toISOString().slice(0, 10));
    await page.getByLabel(/Partially conformant/).check();

    const severity = async (pattern: RegExp) => {
        const options = await field("new_exception[severity]").locator("option").allInnerTexts();
        await field("new_exception[severity]").selectOption({ label: options.find((o) => pattern.test(o)) });
    };

    await field("new_exception[description]").fill("The home page image carousel cannot be paused.");
    await severity(/serious/i);
    await field("new_exception[criterion]").fill("2.2.2");
    await field("new_exception[page]").fill("/");
    await page.getByRole("button", { name: "Save statement" }).click();
    await page.locator("main h1").waitFor();

    await field("new_exception[description]").fill("Older meeting minutes are scanned PDFs with no text layer.");
    await severity(/moderate/i);
    await field("new_exception[criterion]").fill("1.1.1");
    await field("new_exception[page]").fill("/meetings/archive");
    await page.getByRole("button", { name: "Save statement" }).click();
    await page.locator("main h1").waitFor();

    await expect(page.locator("main")).toContainText("2 open, 0 resolved");

    await page.goto("/cp/utilities/a11y-state/evidence");
    const record = page.locator("#a11y-state-record");
    const actions = await record.locator('select[name="action"]').locator("option").allInnerTexts();
    await record.locator('select[name="action"]').selectOption({ label: actions.find((o) => /assess/i.test(o)) });
    await record.locator('[name="criterion"]').fill("1.4.3");
    await record.locator('[name="subject"]').fill("/permits");
    await record.locator('[name="notes"]').fill("Checked text contrast on the permits pages with a colour meter.");
    await page.getByRole("button", { name: "Write the record" }).click();
    await expect(page.locator("main h2").first()).toContainText("records");

    await page.context().storageState({ path: "./test-results/.auth.json" });
});
