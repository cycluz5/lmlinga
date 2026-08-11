/**
 * Non-Resident Family Planning workflow — visual evidence capture (QA helper).
 */
import { chromium } from 'playwright';
import path from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const outDir = path.resolve(
  __dirname,
  '../docs/qa/screenshots/health-records-non-resident-family-planning-phase1.2-scale'
);
fs.mkdirSync(outDir, { recursive: true });

const base = process.env.FP_NR_CAPTURE_BASE || 'http://127.0.0.1:8765';
const role = '?role=bns';

const viewports = [
  { name: '1440x900', width: 1440, height: 900 },
  { name: '1366x768', width: 1366, height: 768 },
  { name: '820x1180', width: 820, height: 1180 },
  { name: '768x1024', width: 768, height: 1024 },
  { name: '390x844', width: 390, height: 844 },
  { name: '360x800', width: 360, height: 800 },
];

const pages = [
  {
    slug: 'listing',
    path: `/health-records/family-planning/non-residents${role}`,
  },
  {
    slug: 'create-client',
    path: `/health-records/family-planning/non-residents/create${role}`,
  },
  {
    slug: 'client-show',
    path: `/health-records/family-planning/non-residents/roselyn-a-mendoza${role}`,
  },
  {
    slug: 'add-visit',
    path: `/health-records/family-planning/non-residents/roselyn-a-mendoza/visits/create${role}`,
  },
  {
    slug: 'edit-visit',
    path: `/health-records/family-planning/non-residents/roselyn-a-mendoza/visits/NR-FP-001/edit${role}`,
  },
];

const browser = await chromium.launch({ headless: true });

async function checkOverflow(page) {
  return page.evaluate(() => {
    const doc = document.documentElement;
    const body = document.body;
    return Math.max(doc.scrollWidth, body.scrollWidth) > doc.clientWidth + 1;
  });
}

for (const pageDef of pages) {
  for (const vp of viewports) {
    const page = await browser.newPage();
    await page.setViewportSize({ width: vp.width, height: vp.height });
    const target = `${base}${pageDef.path}`;
    const response = await page.goto(target, { waitUntil: 'networkidle', timeout: 60000 });
    const ok = response?.ok() ?? false;
    const overflow = await checkOverflow(page);
    const file = path.join(outDir, `${pageDef.slug}-${vp.name}.png`);
    await page.screenshot({ path: file, fullPage: true });
    console.log(
      `saved ${file} ok=${ok} pageOverflow=${overflow}`
    );
    await page.close();
  }
}

await browser.close();
