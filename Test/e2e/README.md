# Playwright BDD Testing for E2E tests

## Introduction

This project uses [Playwright](https://playwright.dev/) for end-to-end testing. Playwright is a Node.js library to automate Chromium, Firefox, and WebKit with a single API. It enables reliable end-to-end testing for modern web apps.

Additionally, we use the [playwright-bdd](https://github.com/vitalets/playwright-bdd) library by Vitalets, which integrates Playwright with BDD-style testing. This allows us to write tests in a more human-readable format using Gherkin syntax.

## Running Tests

To run the tests, you can use the following tasks:

### Set local environment variables

Add a file `.env` in this folder and set the required environment variables. 
Please refer to the `env` const defined in the `playwright.config.ts` file for the list of required environment variables.
You can also run `task test`, and an explicit error message will be shown if any of the required environment variables are missing.

### Run All Tests

```sh
task test
```

This command will execute all the tests in the project.

### Run Tests with UI

```sh
task test:ui
```

This command will run the tests with the Playwright Test Runner UI, which provides a visual interface to see the test results and debug failures.

## Writing Tests

### Feature Files

Tests are written in Gherkin syntax in `.feature` files located in the `features` directory. Each feature file contains one or more scenarios that describe the behavior of the application.

### Step Definitions

Step definitions are implemented in `.ts` files located in the `steps` directory. Each step definition maps a Gherkin step to a Playwright action.
You can list all the available step definitions by running the following command:

```sh
task test:list
```

### Page Objects

Tests are using Page Object Model (POM) pattern to separate the test logic from the page logic. Page objects are located in the `pages` directory.

## More Details

- For more information about Playwright, visit the [official documentation](https://playwright.dev/docs/intro).
- For more details on playwright-bdd, check out the [GitHub repository](https://github.com/vitalets/playwright-bdd).
- For more informations about Alma Playwright BDD E2E tests, visit the Notion page [here](https://www.notion.so/almapay/Playwright-BDD-E2E-tests-1cb18a22216a80f6af77fcc7aa7d2d78?pvs=4).
