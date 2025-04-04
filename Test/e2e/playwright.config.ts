import { defineConfig } from "@playwright/test";
import { defineBddConfig } from "playwright-bdd";
import 'dotenv/config';
import { cleanEnv, url, str } from "envalid";

/**
 * Validate environment variables.
 * See https://www.npmjs.com/package/envalid.
 */
export const env = cleanEnv(process.env, {
  CMS_BASE_URL: url(),
  CMS_ADMIN_RELATIVE_URL: str(),
  CMS_ADMIN_LOGIN: str(),
  CMS_ADMIN_PASSWORD: str(),
});

/**
 * playwright-bdd configuration.
 * See https://vitalets.github.io/playwright-bdd.
 */
const testDir = defineBddConfig({
  steps: ["./steps/*.ts", "./helpers/fixtures.ts"],
  outputDir: ".bdd",
  featuresRoot: './features',
});

/**
 * Report Portal configuration.
 * See https://github.com/reportportal/agent-js-playwright
 */
const RPconfig = {
  apiKey: process.env.REPORT_PORTAL_API_KEY,
  endpoint: process.env.REPORT_PORTAL_ENDPOINT,
  project: 'integrations',
  launch: 'adobe-commerce E2E tests',
  skippedIssue: false,
  uploadTrace: 'true',
  uploadVideo: 'true',
  includeTestSteps: 'true',
  attributes: [
    {
      key: 'env',
      value: process.env.ENVIRONMENT ? process.env.ENVIRONMENT : 'local',
    },
  ],
  description: 'Playwright BDD E2E tests',
}
if (process.env.ENVIRONMENT) {
  RPconfig.description += `\nEnvironment: ${process.env.ENVIRONMENT}`
}
if (process.env.GITHUB_RUN_URL) {
  RPconfig.description += `\nGithub run: ${process.env.GITHUB_RUN_URL}`
}
if (process.env.GITHUB_SHA) {
  RPconfig.description += `\nGithub commit: ${process.env.GITHUB_SHA}`
}
if (process.env.GITHUB_REF_NAME) {
  RPconfig.description += `\nGithub branch: ${process.env.GITHUB_REF_NAME}`
}

/**
 * See https://playwright.dev/docs/test-configuration.
 */
export default defineConfig({
  /* Run tests in files in parallel */
  fullyParallel: true,
  /* Timeout for each test */
  timeout: 120_000,
  /* Timeout for each expect assertion */
  expect: {
    timeout: 30_000,
  },
  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: !!process.env.CI,
  /* Retry on CI only */
  retries: process.env.CI ? 1 : 0,
  /* Parallelize tests on CI only */
  workers: process.env.CI ? 4 : 1,
  /**
   * Reporter to use. See https://playwright.dev/docs/test-reporters
   * Use ReportPortal reporter if enabled, list on CI, and HTML reporter locally.
   */
  reporter: process.env.REPORT_PORTAL_ENABLED === "true"
    ? [["@reportportal/agent-js-playwright", RPconfig]]
    : process.env.CI
    ? "list"
    : "html",
  /* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
  use: {
    /* See https://playwright.dev/docs/trace-viewer */
    trace: "retain-on-failure",
    video: "retain-on-failure",
    screenshot: "only-on-failure",
  },
  outputDir: 'test-results',
  projects: [
    {
      name: "adobe-commerce",
      use: { browserName: "chromium" },
      testDir: testDir,
    },
  ],
});
