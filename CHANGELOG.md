Changelog
=========

v3.0.0
------
* feature: change vendor name to almapay
* fix: get product error on product page  when cache is enabled
* feature: update alma widget to 2.12.3

v2.9.0
------
* fix : multi store management
* feature : use only test or prod api Key
* feature : change payment methode configuration in back office

v2.8.2
------
* Wait for share of checkout legal

v2.8.1
------
* Fix Alma is not defined in back office
* Change widget version 2.11.1

v2.8.0
------
* Add B2B compatibility
* Add cancel order by ipn ( need configuration in alma dashboard )
* i18n add share of checkout translations

v2.7.0
------
* Add share of checkout
* Add online invoice refund

v2.6.2
------
* Fix virtual cart compatibility

v2.6.1
------
* Fix CDN version 2.8.0

v2.6.0
------
* Add rejected payment url in config back office.
* Add failure_return_url in payment payload.
* Add failre return controler page.
* Add languages : de_AT,en_GB,EN_IE,fr_BE,fr_LU,nl_BE

v2.5.0
------
* Change badge price for configurable product
* Cancel pending order with Alma payment page return button
* Fix isFullyConfigured missing function in config

v2.4.0
------
* Fix shipping cost in checkout page
* Fix badge price for front without cents
* Refactor helpers

v2.3.2
------
* Fix eligibility initialisation for : isEligible() must be of the type boolean


v2.3.1
------
* Fix alma-php-client requirement
* I18n

v2.3.0
------
* Feature add payment uppon trigger
* Fix tax grand total in p>4
* Fix white space in translatation "Your cart total : "
* Feature add module version on enable comment 
* Feature add collect logs in back office
* Feature add control for min and max value for each plan

v2.2.1
------
* Catch eligibility exception
* Remove quote init in constructor
* Change quoteHelper return type

v2.2.0
------
* Add quote selector for eligibility and remove checkout session.
* Fix get alma payment Url
* Remove customer data in eligibility
* Use custom class for back-office display
* Fixes total cart round credit cost
* Add Alma in back-office menu
* Split payment methods


v2.1.3
------

* Fixes compatibility issues

v2.1.2
------

* Fixes cart min and max alma eligibility
* Add graphQL eligibility function

v2.1.1
------

* Fixes product page secure render for 2.3.5

v2.1.0
------

* Add i18n badge V2
* Fixes fee plans and installments on checkout page


v2.0.0
------

* Add i18n with eligibility V2
* Fixes init quote with hasquote methode in session plugin
* Fixes circular dependency injection in gateway/config and Log

v1.4.1
------

* Incremental compatibility Fix on invalid payment into Session for Magento 2.4.2-p1 and later

v1.4.0
------

* Add Alma Paylater feature
* Add Alma Pnx payment plan from 5x to 12x

v1.3.1
------

* Add Alma 10x payment plan feature

v1.3.0
------

* Adds an Alma badge with eligibility/payment plans information on product pages
* Standardization of code

v1.2.1
------

* Fixes module's registration dir path, which in some situations prevented the payment plans admin config form to
  display correctly 

v1.2.0
------

* Improves admin configuration UI
* Updates Alma logo
* Fixes bug preventing Alma from being activated after saving its configuration for the first time
* Fixes Alma disappearing from payment methods when sort order is higher than number of methods
* Displays fullscreen loader while redirecting to Alma's payment page from checkout
* Adds support for multiple payment plans, each configurable with specific purchase amount bounds
* Adds Web API endpoint to check eligibility for the activated payment plans

v1.1.2
------

* Makes sure there is a CheckoutSession with an active quote when using the REST API

v1.1.1
------

* Removes `api_root` override that should not have been committed

v1.1.0
------

* Adds Web API endpoints to get an Alma URL for an order paid with Alma, and to validate such a payment
  upon customer return. See `etc/webapi.xml` for endpoints URLs and `Model/Api/Payment.php` for 
  implementation.
  
* Adds 3 API Configuration fields to override `return_url`, `ipn_callback_url` and `customer_cancel_url` 
  in created payments.

v1.0.7
------

* Order status fix

v1.0.6
------

* Order status fix

v1.0.5
------

* Fixes a bug with order status management on Magento 2.3.2
* Adds some data collection for risk/fraud prevention

v1.0.4
------

* Fixes obfuscated API keys values being retried as Alma API credentials


v1.0.3
------

* Fixes incorrect version requirements in Composer manifest

v1.0.2
------

* Fixes class not found `Throwable` on some PHP versions

v1.0.1
------

Let's start following semver.

* Switches logo image to SVG
* Adds User-Agent string containing the module's version, Magento version, PHP client and PHP versions, to all requests going to Alma's API.

v1.0.0
------

This version evolved for a while without any version bump 🤷‍♂️  
Features in the latest push to this release:

* Module can be configured in Test and Live mode
* A message displays below the cart and in the minicart to indicate whether the purchase is eligible to monthly installments
* The module adds a payment method to the checkout, which redirects the user to Alma's payment page.  
If everything goes right (i.e. Customer doesn't cancel, pays the right amount, ... ), the order is validated upon customer return.
