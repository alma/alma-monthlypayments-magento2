/**
 * 2018 Alma / Nabla SAS
 *
 * THE MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and
 * to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
 * Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @author    Alma / Nabla SAS <contact@getalma.eu>
 * @copyright 2018 Alma / Nabla SAS
 * @license   https://opensource.org/licenses/MIT The MIT License
 *
 */

/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/full-screen-loader',
        'uiRegistry',
        'underscore',
    ],
    function ($, Component, fullScreenLoader, registry, _) {
        'use strict';

        // This below is a workaround for a Magento bug: payment methods are not reordered when you navigate from
        // payment page to shipping and back and a payment method is removed/inserted. So we reorder them manually.
        registry.get('checkout.steps.billing-step.payment.payments-list', function(methodsList) {
            var region = methodsList.regions['payment-methods-items-default'];

            if (!region) {
                return;
            }

            function reorderMethods() {
                var list = region.peek(),
                    expectedPosition = Math.min(
                        list.length,
                        window.checkoutConfig.payment['alma_monthly_payments'].sortOrder
                    ) - 1;

                var almaIndex = _.findIndex(list, function(methodComponent) {
                    return methodComponent.item.method === 'alma_monthly_payments';
                });

                if (almaIndex >= 0 && almaIndex !== expectedPosition) {
                    region.valueWillMutate();

                    var component = list[almaIndex];
                    list.splice(almaIndex, 1);
                    list.splice(expectedPosition, 0, component);

                    region.valueHasMutated();
                }
            }

            region.subscribe(reorderMethods);
            reorderMethods();
        });


        return Component.extend({
            redirectAfterPlaceOrder: false,
            defaults: {
                template: 'Alma_MonthlyPayments/payment/form',
            },

            getCode: function() {
                return 'alma_monthly_payments';
            },

            afterPlaceOrder: function () {
                fullScreenLoader.startLoader();

                // Get payment page URL from checkoutConfig and redirect
                $.mage.redirect(window.checkoutConfig.payment[this.getCode()].redirectTo);
            }
        });
    }
);
