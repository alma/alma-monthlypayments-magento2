Alma Monthly Payments for Magento 2
===================================

This plugin adds a new payment method to Magento 2, which allows you to offer monthly payments to your customer using Alma.

## Description

[Alma](https://getalma.eu) is a service to provide merchants with an **easy** and **safe** monthly payments solution.  
Let your customers pay for their purchases at their own pace! You'll receive the funds instantly, and your customer will pay later over a few monthly instalments.

This plugin integrates Alma into Magento 2 by adding a new payment method that you can activate to offer monthly payments to your customers.

## Installation

### Prerequisites

You first need to create your merchant account on [dashboard.getalma.eu](https://dashboard.getalma.eu) and activate your account.

### Installing

#### Using Composer
The easiest way to install the extension is to use Composer:

```bash
$ composer require alma/alma-monthlypayments-magento2
$ bin/magento module:enable Alma_MonthlyPayments
$ bin/magento setup:upgrade && bin/magento setup:static-content:deploy
```

### Configuring the plugin

After installing the plugin, go to `Stores > Configuration > Sales > Payment Methods`.  
Find "Alma Monthly Payments" in the payment methods list to start configuring it.

Fill in the API keys for your account, which you can find on your dashboard\'s [security page](https://dashboard.getalma.eu/security).

You also have access to different settings to control what the plugin should display on the Cart, Mini-cart and Checkout pages.
We advise you to stay in \"Test\" mode until you\'re happy with your configuration and are ready to accept payments from your customers.

Once everything is properly set up, go ahead and switch to \"Live\" mode!
