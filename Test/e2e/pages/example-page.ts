import { type Page } from 'playwright/test';
import { env } from '../playwright.config.js';

export class ExamplePage {
  readonly url: string;
  readonly page: Page;

  constructor(page: Page) {
    this.page = page;
    this.url = `${env.CMS_BASE_URL}/${env.CMS_ADMIN_RELATIVE_URL}`;
  }

  async goto() {
    await this.page.goto(this.url);
  }

  async doNothing() {
    // This method is intentionally left blank
  }
}
