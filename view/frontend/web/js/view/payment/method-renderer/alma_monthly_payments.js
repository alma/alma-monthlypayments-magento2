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
        'ko',
        'jquery',
        'underscore',
        'moment',
        'mage/translate',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Catalog/js/price-utils',
        'uiRegistry',
    ],
    function (ko, $, _, moment, $t, Component, fullScreenLoader, priceUtils, registry) {
        'use strict';

        // This below is a workaround for a Magento bug: payment methods are not reordered when you navigate from
        // payment page to shipping and back and a payment method is removed/inserted. So we reorder them manually.
        registry.get('checkout.steps.billing-step.payment.payments-list', function (methodsList) {
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

                var almaIndex = _.findIndex(list, function (methodComponent) {
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

            initialize: function () {
                this._super();
                this.config = window.checkoutConfig.payment[this.item.method];
                this.paymentPlans = this.config.paymentPlans;
                this.selectedPlanKey = ko.observable(this.defaultPlan().key);
                this.selectedPlan = ko.computed(function () {
                    var key = this.selectedPlanKey();

                    return this.paymentPlans.find(function (plan) {
                        return plan.key === key;
                    }) || {};
                }, this);
                return this;
            },

            defaultPlan: function () {
                return this.paymentPlans.reduce(function (plan, result) {
                    if (plan.installmentsCount === 3 || plan.installmentsCount > result.installmentsCount) {
                        return plan;
                    }

                    return result;
                }, this.paymentPlans[0]);
            },

            getSinglePlanTitle: function (plan) {
                return $t('Pay in %1 installments').replace('%1', plan.installmentsCount);
            },
            
            getDescription: function () {
                return this.config.description;
            },

            getPlanLabel: function (plan) {
                const regexDeffered = new RegExp('^general:1:[0-9]{2}:0$');
                var label = $t('%1 installments').replace('%1', plan.installmentsCount);

                if (regexDeffered.test(plan.key)){
                    label = $t('In %1 days').replace('%1', plan.deferredDays);
                }
                return label;
            },

            formattedDate: function (ts) {
                return (new Date(ts * 1000)).toLocaleDateString(this.config.locale);
            },

            formattedPrice: function (cents) {
                return priceUtils.formatPrice(cents / 100, window.checkoutConfig.priceFormat);
            },

            cartTotal: function () {
                return priceUtils.formatPrice(window.checkoutConfig.quoteData.grand_total, window.checkoutConfig.priceFormat);
            },

            customerTotalCostAmount: function (cost) {
                return priceUtils.formatPrice(cost / 100, window.checkoutConfig.priceFormat);
            },

            hasAnnualInterestRate: function (rate) {
                return ( rate!=null && rate > 0 ? true : false );
            },

            totalPaid: function (cost) {
                return priceUtils.formatPrice(parseInt(window.checkoutConfig.quoteData.grand_total) + (cost/100), window.checkoutConfig.priceFormat);
            },

            getFeesMention: function (customerFee) {
                return $t('Including fees: %1').replace('%1', this.formattedPrice(customerFee));
            },

            getData: function () {
                return $.extend(
                    this._super(),
                    {
                        additional_data: {
                            selectedPlan: this.selectedPlanKey()
                        }
                    }
                );
            },

            afterPlaceOrder: function () {
                fullScreenLoader.startLoader();

                // Get payment page URL from checkoutConfig and redirect
                $.mage.redirect(this.config.redirectTo);
            }
        });
    }
);
