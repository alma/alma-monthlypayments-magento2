import { test as base, createBdd } from 'playwright-bdd';
import { ExamplePage } from '../pages/example-page';

type MyFixtures = {
  // Page objects
  examplePage: ExamplePage;
};
export const test = base.extend<MyFixtures>({
  // Page objects
  examplePage: async ({ page }, use) => {
    const examplePage = new ExamplePage(page);
    await use(examplePage);
  },
});

export const { Given, When, Then } = createBdd(test);
