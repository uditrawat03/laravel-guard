import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

const sections = {
  overview: 'Overview',
  findings: 'Findings',
  scans: 'Scan history',
  baselines: 'Baselines',
  rules: 'Rule catalog',
  runtime: 'Runtime',
  doctor: 'Doctor',
};

test('package dashboard routes render without serious accessibility violations', async ({ page }) => {
  for (const [section, heading] of Object.entries(sections)) {
    await page.goto(section === 'overview' ? '/laravel-guard' : `/laravel-guard/${section}`);
    await expect(page.locator('main h1')).toHaveText(heading);
    await expect(page.locator('nav[aria-label="Security dashboard"]')).toBeVisible();

    const results = await new AxeBuilder({ page }).analyze();
    const blocking = results.violations.filter(({ impact }) => impact === 'critical' || impact === 'serious');
    expect(blocking, JSON.stringify(blocking, null, 2)).toEqual([]);
  }
});

test('rule guidance is keyboard operable and exposes complete remediation', async ({ page }) => {
  await page.goto('/laravel-guard/rules');
  const summary = page.getByText('View guidance').first();
  await summary.focus();
  await expect(summary).toBeFocused();
  await page.keyboard.press('Enter');

  const rule = summary.locator('xpath=ancestor::article');
  await expect(rule.getByText('Potentially vulnerable')).toBeVisible();
  await expect(rule.getByText('Safer pattern')).toBeVisible();
  await expect(rule.getByText('False-positive review')).toBeVisible();
  await expect(rule.getByText('Narrow suppression')).toBeVisible();

  const outline = await summary.evaluate((element) => getComputedStyle(element).outlineStyle);
  expect(outline).not.toBe('none');
});

test('desktop and mobile layouts do not overflow the viewport', async ({ page }) => {
  await page.goto('/laravel-guard/rules');
  const dimensions = await page.evaluate(() => ({
    viewport: document.documentElement.clientWidth,
    content: document.documentElement.scrollWidth,
  }));
  expect(dimensions.content).toBeLessThanOrEqual(dimensions.viewport + 1);

  await expect(page.locator('.guard-sidebar')).toBeVisible();
  await expect(page.locator('.guard-pagination')).toBeVisible();
});