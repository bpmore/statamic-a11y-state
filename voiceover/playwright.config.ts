import { devices } from "@playwright/test";
import { withGuidepup } from "@guidepup/playwright";

/*
 * Two projects. "seed" signs in and fills a demo statement through the real
 * control panel with no screen reader running. "voiceover" then reuses that
 * session and walks each screen with VoiceOver in WebKit, Safari's engine,
 * because Safari is what a VoiceOver user is most likely to be using.
 *
 * withGuidepup runs tests one at a time in a headed browser: VoiceOver can
 * only follow one window.
 */
export default withGuidepup({
    // Long enough for a slow VoiceOver, short enough that a stall cannot
    // use up the job: the first run spent 30 minutes on one wait.
    timeout: 4 * 60 * 1000,
    reportSlowTests: null,
    retries: process.env.CI ? 1 : 0,
    outputDir: "./test-results",
    reporter: process.env.CI ? [["github"], ["list"], ["html", { open: "never" }]] : "list",
    use: {
        baseURL: process.env.BASE_URL ?? "http://127.0.0.1:8765",
    },
    projects: [
        {
            name: "seed",
            testMatch: /seed\.setup\.ts/,
            use: { ...devices["Desktop Safari"] },
        },
        {
            name: "voiceover",
            testMatch: /.*\.spec\.ts/,
            dependencies: ["seed"],
            use: { ...devices["Desktop Safari"], storageState: "./test-results/.auth.json" },
        },
    ],
});
