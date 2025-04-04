import { expect } from '@playwright/test';
import { Given, When, Then } from '../helpers/fixtures.js';

/* eslint-disable no-empty-pattern */ // Ignore this rule as this is a test file
Given('there is nothing', async ({}) => {});

When('nothing happens', async ({examplePage}) => {
    examplePage.doNothing();
});

Then('nothing should happen', async ({examplePage}) => {
    expect(examplePage).toBeTruthy();
});
