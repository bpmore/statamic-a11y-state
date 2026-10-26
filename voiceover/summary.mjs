// Writes what VoiceOver said on each screen to the GitHub run's summary
// page, so a person can read it without downloading anything.
import { appendFileSync, existsSync, readdirSync, readFileSync } from "node:fs";

const dir = "./test-results/transcripts";
const out = process.env.GITHUB_STEP_SUMMARY;
const lines = ["## What VoiceOver said", ""];

if (!existsSync(dir)) {
    lines.push("No transcripts were written. See the test log above.");
} else {
    for (const file of readdirSync(dir).filter((f) => f.endsWith(".json")).sort()) {
        lines.push(`### ${file.replace(/\.json$/, "").replace(/^\d-/, "").replace(/-/g, " ")}`, "");

        for (const [step, phrases] of Object.entries(JSON.parse(readFileSync(`${dir}/${file}`, "utf8")))) {
            lines.push(`**${step}**`, "");
            for (const phrase of phrases) {
                lines.push(`- ${String(phrase).replace(/\n/g, " ")}`);
            }
            lines.push("");
        }
    }
}

if (out) {
    appendFileSync(out, lines.join("\n") + "\n");
} else {
    console.log(lines.join("\n"));
}
