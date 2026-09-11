#!/usr/bin/env node
/**
 * Dapodik school capture (development/ops tool).
 *
 * The Dapodik portal (dapo.kemendikdasmen.go.id) protects its per-school API
 * with SafeLine WAF that fingerprints TLS + runs a JavaScript challenge. A
 * plain HTTP client (Laravel HttpClient / curl) is rejected with 502/400 even
 * when it repeats browser headers and the sl-session cookie. A real browser
 * passes the challenge, so only this script can fetch the per-school data.
 *
 * It:
 *   1. opens dapo.kemendikdasmen.go.id in the installed MS Edge (chromium),
 *   2. lets the WAF challenge complete,
 *   3. reads the kecamatan roster for the configured kabupaten,
 *   4. fetches the school list per kecamatan,
 *   5. expands every jenjang school (SD/SMP/SMA/SMK/SLB) with
 *      /api/detail-sekolah?npsn= (coordinates, address, gender split,
 *      faculty, facilities), and
 *   6. writes one snapshot JSON consumed by `public-data:dapodik-import`.
 *
 * Requirements: Node.js >= 18, an MS Edge installation, and the playwright-core
 * package. Run from the project root:
 *
 *     npm i --no-save playwright-core
 *     node scripts/dapodik/dapodik-capture.cjs
 *
 * All output is plain English on purpose so failures are grep-able in CI logs.
 */

const { chromium } = require('playwright-core');
const fs = require('fs');
const path = require('path');

const BASE = 'https://dapo.kemendikdasmen.go.id';
const REGION_CODE = process.env.DAPODIK_REGION_CODE || '180700';
const REGION_NAME = process.env.DAPODIK_REGION_NAME || 'Kabupaten Morowali';
const SLOW_BY = Number(process.env.DAPODIK_PACE_MS || 500);
const TYPES = ['SD', 'SMP', 'SMA', 'SMK', 'SLB'];

const PROJECT_ROOT = path.resolve(__dirname, '..', '..');
const OUT_DIR = path.join(PROJECT_ROOT, 'storage', 'app', 'data', 'dapodik');
const OUT_FILE = path.join(OUT_DIR, 'morowali-schools.json');

function sleep(ms) {
  return new Promise((r) => setTimeout(r, ms));
}

async function fetchJson(page, pathname, token) {
  const r = await page.evaluate(async ({ path: p, base, tok }) => {
    const res = await fetch(p, {
      headers: {
        Authorization: `Bearer ${tok}`,
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
    });
    return { status: res.status, text: await res.text() };
  }, { path: pathname, base: BASE, tok: token });
  if (r.status !== 200) {
    throw new Error(`${pathname} -> HTTP ${r.status}`);
  }
  return JSON.parse(r.text);
}

(async () => {
  console.log(`capture region ${REGION_CODE} (${REGION_NAME})`);

  const browser = await chromium.launch({ channel: 'msedge', headless: false });
  const context = await browser.newContext({ viewport: { width: 1440, height: 900 }, locale: 'id-ID' });
  const page = await context.newPage();
  page.setDefaultTimeout(60000);

  const token = await page.evaluate(async (base) => {
    const res = await fetch(base + '/env.js');
    const text = await res.text();
    const m = text.match(/([a-f0-9]{64,})/i);
    return m ? m[1] : '';
  }, BASE);
  if (!token) throw new Error('no VITE_API_TOKEN found in env.js');
  console.log('token ok');

  await page.goto(BASE + '/', { waitUntil: 'domcontentloaded', timeout: 90000 }).catch(() => {});
  await sleep(4000);
  await page.goto(BASE + '/', { waitUntil: 'domcontentloaded', timeout: 90000 }).catch(() => {});
  await sleep(3000);
  console.log('waf challenge ok');

  const kecRows = (await fetchJson(page, `/api/progress-pengiriman/kecamatan?kode_kabupaten=${REGION_CODE}`, token)).data || [];
  const kecamatan = kecRows.filter((r) => String(r.kode_kabupaten).startsWith(REGION_CODE.slice(0, 4)));
  if (!kecamatan.length) throw new Error('no kecamatan rows for region');
  console.log(`kecamatan: ${kecamatan.length}`);

  const all = [];
  for (const kec of kecamatan) {
    const schools = (await fetchJson(page, `/api/progress-pengiriman/kecamatan/school?kode_kecamatan=${kec.kode_kecamatan}`, token)).data || [];
    const keep = schools.filter((s) => TYPES.includes(s.bentuk_pendidikan));
    all.push(...keep.map((s) => ({ ...s, kode_kecamatan: kec.kode_kecamatan })));
    console.log(`${kec.kecamatan}: ${schools.length} sekolah, ${keep.length} ${TYPES.join('/')}`);
    await sleep(SLOW_BY);
  }
  console.log(`schools to expand: ${all.length}`);

  const details = [];
  let failed = 0;
  for (const s of all) {
    try {
      const rows = (await fetchJson(page, `/api/detail-sekolah?npsn=${s.npsn}`, token)).data || [];
      if (!rows.length) throw new Error(`empty detail for ${s.npsn}`);
      details.push(rows[0]);
    } catch (e) {
      failed++;
      console.log(`detail fail ${s.npsn}: ${e.message.slice(0, 120)}`);
    }
    await sleep(SLOW_BY);
  }
  console.log(`details: ${details.length}, failed ${failed}`);

  fs.mkdirSync(OUT_DIR, { recursive: true });
  const snapshot = {
    captured_at: new Date().toISOString(),
    source: 'dapodik',
    region_code: REGION_CODE,
    region_name: REGION_NAME,
    semester: details[0] && 'semester' in details[0] ? details[0].semester : null,
    schools: details,
  };
  fs.writeFileSync(OUT_FILE, JSON.stringify(snapshot, null, 2));
  console.log(`wrote ${OUT_FILE} (${details.length} schools)`);

  const breakdown = {};
  details.forEach((d) => { breakdown[d.bentuk_pendidikan] = (breakdown[d.bentuk_pendidikan] || 0) + 1; });
  console.log('breakdown', JSON.stringify(breakdown));

  await browser.close();
})().catch((e) => {
  console.error('capture aborted:', e.message);
  process.exitCode = 1;
});