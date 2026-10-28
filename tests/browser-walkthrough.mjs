// Browser walkthrough of the settings page and the live site, in headless Chromium.
// Run it against a FRESH Playground server: the first save on a new install is the case that
// once lost the ID (WordPress runs the sanitize callback twice when the option is created).
// Setup once: npm i playwright && npx playwright install chromium   (in any scratch folder)
// Run: BASE=http://127.0.0.1:9411 node tests/browser-walkthrough.mjs   -> exits 1 on any failure
import { chromium } from "playwright";

const BASE = process.env.BASE || "http://127.0.0.1:9411";
let failures = 0;
const expect = (ok, what) => { console.log(`${ok ? "PASS" : "FAIL"}: ${what}`); if (!ok) failures++; };
import { mkdirSync } from "node:fs";
const OUT = process.env.SHOTS || "/tmp/repx-wp-shots/";
mkdirSync(OUT, { recursive: true });
const SNIPPET = '<script src="https://js.storylane.io/js/v1/repx.js" data-id="mbtx79fn0yum" async></script>';
const log = (...a) => console.log(...a);

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
const consoleMsgs = [];
page.on("console", (m) => consoleMsgs.push(`${m.type()}: ${m.text()}`));
const repxRequests = [];
page.on("response", (r) => { if (/storylane\.io/.test(r.url())) repxRequests.push(`${r.status()} ${r.url()}`); });

// Step 3: settings page, paste the full snippet, enable, save.
await page.goto(`${BASE}/wp-admin/options-general.php?page=storylane-repx`, { waitUntil: "domcontentloaded" });
// Playground auto-login may redirect first; land on the settings page.
if (!page.url().includes("page=storylane-repx")) await page.goto(`${BASE}/wp-admin/options-general.php?page=storylane-repx`);
log("STEP3 page title:", await page.locator("h1").first().innerText());
await page.fill("#storylane-repx-script", SNIPPET);
await page.click("#submit");
await page.waitForLoadState("domcontentloaded");
expect((await page.locator("#storylane-repx-status").innerText()).trim() === "RepX is live on this site", "status shows RepX is live after first save");
const notice = await page.locator(".notice, .updated, .error").allInnerTexts();
log("STEP3 notices:", JSON.stringify(notice));
const v3 = await page.inputValue("#storylane-repx-script");
expect(v3 === SNIPPET, `first save shows the RepX script back (got "${v3}")`);
await page.screenshot({ path: OUT + "1-settings-saved.png", fullPage: true });

// Step 4: bad value is refused, old value kept.
await page.fill("#storylane-repx-script", "hello world");
await page.click("#submit");
await page.waitForLoadState("domcontentloaded");
log("STEP4 notices:", JSON.stringify(await page.locator(".notice, .updated, .error").allInnerTexts()));
const v4 = await page.inputValue("#storylane-repx-script");
expect(v4 === SNIPPET, `bad value refused, old script kept (got "${v4}")`);
await page.screenshot({ path: OUT + "2-bad-value.png", fullPage: true });

// Switch: flipping it changes the status text before saving; saving takes RepX off the site.
await page.click('label[for="storylane-repx-enabled"]');
const offText = (await page.locator("#storylane-repx-status").innerText()).trim();
expect(offText === "RepX is off", `switch flips the status text (got "${offText}")`);
await page.click("#submit");
await page.waitForLoadState("domcontentloaded");
const offHtml = await (await fetch(`${BASE}/?nocache=${Date.now()}`, { headers: { cookie: "playground_auto_login_already_happened=1" } })).text();
expect(!offHtml.includes("repx.js"), "after switching off and saving, RepX is not on the site");
await page.screenshot({ path: OUT + "2b-switched-off.png", fullPage: true });
await page.click('label[for="storylane-repx-enabled"]');
await page.click("#submit");
await page.waitForLoadState("domcontentloaded");

// Step 5: the site itself.
const anon = await browser.newContext({ viewport: { width: 1280, height: 900 } });
await anon.addCookies([{ name: "playground_auto_login_already_happened", value: "1", url: BASE }]);
const site = await anon.newPage();
site.on("console", (m) => consoleMsgs.push(`site ${m.type()}: ${m.text()}`));
site.on("response", (r) => { if (/storylane\.io/.test(r.url())) repxRequests.push(`${r.status()} ${r.url()}`); });
const res = await site.goto(`${BASE}/`, { waitUntil: "load" });
const html = await res.text();
const tags = html.match(/<script[^>]*repx\.js[^>]*>/g) || [];
expect(tags.length === 1, `exactly one RepX tag on the site (found ${tags.length})`);
expect(/data-source="wordpress"/.test(tags[0] || ""), "tag carries data-source=wordpress");
tags.forEach((t) => log("   ", t));
expect(html.indexOf(tags[0] || "~") > html.indexOf("</head>"), "tag is in the footer");
await site.waitForTimeout(12000);
const widget = await site.evaluate(() => {
  const frames = [...document.querySelectorAll("iframe")].map((f) => f.src);
  const nodes = [...document.querySelectorAll("[id*=repx i],[class*=repx i],[id*=storylane i],[class*=storylane i]")]
    .map((n) => `${n.tagName.toLowerCase()}#${n.id}.${String(n.className).slice(0, 60)}`);
  return { frames, nodes };
});
expect(widget.frames.some((f) => f.includes("app.storylane.io/repx/mbtx79fn0yum")), "RepX widget loaded");
log("STEP5 widget elements:", JSON.stringify(widget.nodes));
log("STEP5 storylane network responses:", JSON.stringify(repxRequests, null, 1));
await site.screenshot({ path: OUT + "3-site.png" });

log("CONSOLE (errors/warnings and storylane mentions):");
consoleMsgs.filter((m) => /error|warn|repx|storylane/i.test(m)).slice(0, 25).forEach((m) => log("   ", m));
await browser.close();
console.log(failures ? `FAILED: ${failures}` : "ALL PASSED");
process.exit(failures ? 1 : 0);
