Alma Monthly Payments for Magento 2
===================================

This plugin adds a new payment method to Magento 2, which allows you to offer monthly payments to your customer using Alma.

## Description

[Alma](https://getalma.eu) is a service to provide merchants with an **easy** and **safe** monthly payments solution.  
Let your customers pay for their purchases at their own pace! You'll receive the funds instantly, and your customer will pay later over a few monthly instalments.

This plugin integrates Alma into Magento 2 by adding a new payment method that you can activate to offer monthly payments to your customers.

## Requirements

### Compatibility
- **Adobe Commerce (Magento) versions 2.3.5 to 2.4.7**: Fully compatible with the latest version of our module.
- **Adobe Commerce (Magento) versions 2.2.8 to 2.3.5**: Compatible with module version **2.8.2**.
- **Adobe Commerce (Magento) versions lower than 2.2.8**: **Partially compatible** with module version **2.8.2**.
- **PHP**: Compatible with versions `7.1` to `8.1`

## Installation

### Account Setup (Required)

Before configuring the module, you need to create your merchant account on [dashboard.getalma.eu](https://dashboard.getalma.eu).

1. Go to [registration page](https://dashboard.getalma.eu/new-register) and create an account.
2. Retrieve your API key from the dashboard.
3. Use these credentials in the module configuration.

### Method: Composer Installation (Recommended)
1. Run the following command in your Magento root directory:
   ```bash
   $ composer require almapay/alma-monthlypayments-magento2
    ```
2. Enable the module:
   ```bash
   $ bin/magento module:enable Alma_MonthlyPayments
    ```

3. Run setup upgrade and compile:
   ```bash
   $ bin/magento setup:upgrade
   $ bin/magento setup:di:compile
   $ bin/magento setup:static-content:deploy
   $ bin/magento cache:flush
    ```

### Configuring the plugin

After installing the plugin, go to `Stores > Configuration > Sales > Payment Methods`.  
Find "Alma Monthly Payments" in the payment methods list to start configuring it.

Fill in the API keys for your account, which you can find on your dashboard\'s [security page](https://dashboard.getalma.eu/security).

You also have access to different settings to control what the plugin should display on the Cart, Mini-cart and Checkout pages.
We advise you to stay in \"Test\" mode until you\'re happy with your configuration and are ready to accept payments from your customers.

Once everything is properly set up, go ahead and switch to \"Live\" mode!

## Support
If you encounter any issues or have questions, feel free to contact us at [support@getalma.eu](mailto:support@getalma.eu.).
