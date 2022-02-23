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
        'Magento_Customer/js/customer-data',
        'uiRegistry',
        'Magento_Checkout/js/model/quote',
    ],
    function (ko, $, _, moment, $t, Component, fullScreenLoader, priceUtils, customerData ,registry,quote) {
        'use strict';
        var self;
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
        });


        return Component.extend({
            redirectAfterPlaceOrder: false,
            defaults: {
                template: 'Alma_MonthlyPayments/payment/form',
            },
            totals: quote.getTotals(),

            initialize: function () {

                self=this;
                this._super();
                this.totals.subscribe(this.reloadObserver.bind(this));

                this.paymentCode = this.getCode();
                this.installmentsCode = this.getCode()+'_installments';
                this.spreadCode = this.getCode()+'_spread';
                this.deferredCode = this.getCode()+'_deferred';
                this.almaSectionName = 'alma_section';

                this.almaInstallmentsPaymentMethod = ko.observable(false);
                this.almaSpreadPaymentMethod = ko.observable(false);
                this.almaDeferredPaymentMethod = ko.observable(false);
                this.almaMergedPaymentMethod = ko.observable(false);

                this.almaInstallmentsPaymentPlans = ko.observable([]);
                this.almaSpreadPaymentPlans = ko.observable([]);
                this.almaDeferredPaymentPlans = ko.observable([]);
                this.almaMergedPaymentPlans = ko.observable([]);


                this.config = window.checkoutConfig.payment[this.item.method];
                this.almaSection = ko.observable(customerData.get(self.almaSectionName)())

                this.checkedPaymentMethod = ko.observable('');
                this.lastSelectedPlanKey =  ko.observable('');


                /**
                 * Init Installments observables and computed
                 */
                if(this.almaSection().paymentMethods.installments){
                    // -- Init checked payment Method if empty
                    if(this.checkedPaymentMethod()== '') {
                        this.checkedPaymentMethod = ko.observable(this.installmentsCode)
                    }

                    // -- Init Installments computed based on section
                    this.almaInstallmentsPaymentMethod = ko.computed(function() {
                        return customerData.get(self.almaSectionName)().paymentMethods.installments;
                    })
                    this.almaInstallmentsPaymentPlans = ko.computed(function() {
                        return customerData.get(self.almaSectionName)().paymentMethods.installments.paymentPlans
                    })

                    // -- Init selected plan for payment schedule display
                    this.installmentsSelectedPlanKey = ko.observable(this.defaultInstallmentsPlan().key);
                    this.installmentsSelectedPlan = ko.computed(function () {
                        var key = self.installmentsSelectedPlanKey();

                        var currentSelectedPlan = self.almaInstallmentsPaymentPlans().find(function (plan) {
                            return plan.key === key;
                        });
                        if(self.checkedPaymentMethod() == self.installmentsCode){
                            self.lastSelectedPlanKey = currentSelectedPlan.key;
                        }
                        return currentSelectedPlan;
                    })

                    // -- Init selected computed for display active paymentMethod
                    this.isCheckedInstallments = ko.computed(function() {
                        var isChecked = false;
                        if(( self.isChecked() == null|| self.isChecked() == self.paymentCode ) &&  self.checkedPaymentMethod() == self.installmentsCode){
                            isChecked = true;
                        }
                        return isChecked;
                    })
                }

                /**
                 * Init Spread observables and computed
                 */
                if(this.almaSection().paymentMethods.spread){
                    // -- Init checked payment Method if empty
                    if(this.checkedPaymentMethod()== '') {
                        this.checkedPaymentMethod = ko.observable(this.spreadCode)
                    }

                    // -- Init spread computed based on section
                    this.almaSpreadPaymentMethod = ko.computed(function() {
                        return customerData.get(self.almaSectionName)().paymentMethods.spread;
                    })
                    this.almaSpreadPaymentPlans = ko.computed(function() {
                        return customerData.get(self.almaSectionName)().paymentMethods.spread.paymentPlans
                    })

                    // -- Init selected plan for payment schedule display
                    this.spreadSelectedPlanKey = ko.observable(this.defaultSpreadPlan().key);
                    this.spreadSelectedPlan = ko.computed(function () {

                        var key = self.spreadSelectedPlanKey();

                        var currentSelectedPlan = self.almaSpreadPaymentPlans().find(function (plan) {
                            return plan.key === key;
                        });
                        if (self.checkedPaymentMethod() == self.spreadCode){
                            self.lastSelectedPlanKey = currentSelectedPlan.key;
                        }
                        return currentSelectedPlan;
                    })


                    // -- Init selected computed for display active paymentMethod
                    this.isCheckedSpread = ko.computed(function() {
                        var isChecked = false;
                        if(( self.isChecked() == null || self.isChecked() == self.paymentCode ) && self.checkedPaymentMethod() == self.spreadCode){
                            isChecked = true;
                        }
                        return isChecked;
                    })
                }

                /**
                 * Init deferred observables and computed
                 */
                if(this.almaSection().paymentMethods.deferred){
                    // -- Init checked payment Method if empty
                    if(this.checkedPaymentMethod()== ''){
                        this.checkedPaymentMethod = ko.observable(this.deferredCode)
                    }

                    // -- Init deferred computed based on section
                    this.almaDeferredPaymentMethod = ko.computed(function() {
                        return customerData.get(self.almaSectionName)().paymentMethods.deferred;
                    })
                    this.almaDeferredPaymentPlans = ko.computed(function() {
                        return customerData.get(self.almaSectionName)().paymentMethods.deferred.paymentPlans
                    })

                    // -- Init selected plan for payment schedule display
                    this.deferredSelectedPlanKey = ko.observable(this.defaultDeferredPlan().key);
                    this.deferredSelectedPlan = ko.computed(function () {
                        var key = self.deferredSelectedPlanKey();

                        var currentSelectedPlan = self.almaDeferredPaymentPlans().find(function (plan) {
                            return plan.key === key;
                        });
                        if(self.checkedPaymentMethod() == self.deferredCode){
                            self.lastSelectedPlanKey = currentSelectedPlan.key;
                        }
                        return currentSelectedPlan;
                    })

                    // -- Init selected computed for display active paymentMethod
                    this.isCheckedDeferred = ko.computed(function() {
                        var isChecked = false;
                        if(( self.isChecked() == null|| self.isChecked() == self.paymentCode ) && self.checkedPaymentMethod() == self.deferredCode){
                            isChecked = true;
                        }
                        return isChecked;
                    })
                }

                /**
                 * Init Merged observables and computed
                 */
                if(this.almaSection().paymentMethods.merged){
                    // -- Init checked payment Method if empty
                    if(this.checkedPaymentMethod()== ''){
                        this.checkedPaymentMethod = ko.observable(this.paymentCode)
                    }

                    // -- Init merged computed based on section
                    this.almaMergedPaymentMethod = ko.computed(function() {
                        return customerData.get(self.almaSectionName)().paymentMethods.merged;
                    })
                    this.almaMergedPaymentPlans = ko.computed(function() {
                        return customerData.get(self.almaSectionName)().paymentMethods.merged.paymentPlans
                    })

                    // -- Init selected plan for payment schedule display
                    this.mergedSelectedPlanKey = ko.observable(this.defaultMergedPlan().key);
                    this.mergedSelectedPlan = ko.computed(function () {
                        var key = self.mergedSelectedPlanKey();

                        var currentSelectedPlan = self.almaMergedPaymentPlans().find(function (plan) {
                            return plan.key === key;
                        });
                        if(self.checkedPaymentMethod() == self.paymentCode){
                            self.lastSelectedPlanKey = currentSelectedPlan.key;
                        }
                        return currentSelectedPlan;
                    })

                    // -- Init selected computed for display active paymentMethod
                    this.isCheckedMerged = ko.computed(function() {
                        var isChecked = false;
                        if(( self.isChecked() == null|| self.isChecked() == self.paymentCode ) && self.checkedPaymentMethod() == self.paymentCode){
                            isChecked = true;
                        }
                        return isChecked;
                    })
                }
            },

            getInstallmentsPaymentCode : function(){
                return this.installmentsCode;
            },
            defaultInstallmentsPlan : function(){
                return this.almaInstallmentsPaymentPlans()[0];
            },

            getSpreadPaymentCode : function(){
                return this.spreadCode;
            },
            defaultSpreadPlan : function(){
                return this.almaSpreadPaymentPlans()[0];
            },

            getDeferredPaymentCode : function(){
                return this.deferredCode;
            },
            defaultDeferredPlan : function(){
                return this.almaDeferredPaymentPlans()[0];
            },

            getMergedPaymentCode : function(){
                return this.paymentCode;
            },
            defaultMergedPlan : function(){
                return this.almaMergedPaymentPlans()[0];
            },


            reloadObserver: function(){
                this.reloadAlmaSection();
            },
            reloadAlmaSection:function (){
            customerData.invalidate([self.almaSectionName])
            customerData.reload([self.almaSectionName])
            },

            getPlanLabel: function (plan) {
                const regexDeferred = /^general:1:[\d]{2}:0$/;
                var label = $t('%1 installments').replace('%1', plan.installmentsCount);

                if (regexDeferred.test(plan.key)){
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
                return priceUtils.formatPrice(this.totals().grand_total, window.checkoutConfig.priceFormat);
            },

            customerTotalCostAmount: function (cost) {
                return priceUtils.formatPrice(cost / 100, window.checkoutConfig.priceFormat);
            },

            hasAnnualInterestRate: function (rate) {
                return ( rate!=null && rate > 0 ? true : false );
            },

            totalPaid: function (cost) {
                return priceUtils.formatPrice(parseFloat(window.checkoutConfig.quoteData.grand_total) + (cost/100), window.checkoutConfig.priceFormat);
            },

            getFeesMention: function (customerFee) {
                return $t('Including fees: %1').replace('%1', this.formattedPrice(customerFee));
            },
            getData: function () {
                return $.extend(
                    this._super(),
                    {
                        additional_data: {
                            selectedPlan:  this.lastSelectedPlanKey
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
