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
require.config({
    paths: {
        'inPage/Alma': 'https://cdn.jsdelivr.net/npm/@alma/in-page@2.x/dist/index.umd'
    }
});
/*browser:true*/
/*global define*/
define(
    [
        'inPage/Alma',
        'mage/storage',
        'ko',
        'jquery',
        'underscore',
        'mage/translate',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Catalog/js/price-utils',
        'Magento_Customer/js/customer-data',
        'uiRegistry',
        'Magento_Checkout/js/model/quote',
        'Magento_Ui/js/model/messageList',
    ],
    function (Alma, storage, ko, $, _, $t, Component, fullScreenLoader, priceUtils, customerData ,registry, quote, messageList) {
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
                self = this;
                self.reloadCartSection();
                this._super();
                this.almaSectionName = 'alma_section';

                this.config = window.checkoutConfig.payment[this.item.method];
                this.almaSection = ko.observable(customerData.get(self.almaSectionName)());

                this.checkedPaymentMethod = ko.observable('');
                this.lastSelectedPlanKey =  ko.observable({});
                this.lastSelectedPlan = ko.observable({});

                // eslint-disable-next-line max-len
                ['merged','paynow','installments','spread','deferred'].forEach((paymentOption)=>this.initObservablesAndComputedFor(paymentOption));
            },

            initObservablesAndComputedFor : function (paymentOption) {
                var paymentCode = this.getCode() + '_' + paymentOption;
                this[`${paymentOption}PaymentCode`] = paymentCode;

                this[`${paymentOption}PaymentMethod`] = ko.observable(false);
                this[`${paymentOption}PaymentPlans`] = ko.observable([]);

                if(this.almaSection().paymentMethods[paymentOption]) {
                    // -- Init Installments computed based on section
                    this[`${paymentOption}PaymentMethod`] = ko.computed(() => customerData.get(self.almaSectionName)().paymentMethods[paymentOption]);
                    this[`${paymentOption}PaymentPlans`] = ko.computed(() => customerData.get(self.almaSectionName)().paymentMethods[paymentOption].paymentPlans);
                    // -- Init selected plan for payment schedule display
                    var defaultPlan = '';
                    if (this[`${paymentOption}PaymentPlans`]()[0] !== undefined) {
                    defaultPlan = this[`${paymentOption}PaymentPlans`]()[0];
                    }
                    this[`${paymentOption}SelectedPlanKey`] = ko.observable(defaultPlan.key);
                    this[`${paymentOption}SelectedPlan`] = ko.computed(() =>
                        self.selectedPlan(self[`${paymentOption}SelectedPlanKey`](),
                            self[`${paymentOption}PaymentPlans`](),
                            paymentCode)
                    );
                    this[`${paymentOption}IsChecked`] = ko.computed(() => self.fallbackIsChecked(paymentCode));
                }
            },

            selectedPlan : function (key,plans,paymentCode) {
                var currentSelectedPlan = [];
                if (key == undefined){
                    return currentSelectedPlan;
                }
                currentSelectedPlan = plans.find(function (plan) {
                    return plan.key === key;
                });
                currentSelectedPlan.paymentCode = paymentCode;
                if(self.checkedPaymentMethod() === paymentCode){
                    self.lastSelectedPlanKey()[paymentCode] = currentSelectedPlan.key;
                    self.lastSelectedPlan()[paymentCode] = currentSelectedPlan;
                }
                return currentSelectedPlan;
            },

            fallbackIsChecked : function (paymentCode) {
                var isChecked = false;
                if(( this.isChecked() == null|| this.isChecked() === this.getCode() ) && this.checkedPaymentMethod() === paymentCode){
                    isChecked = true;
                }
                return isChecked;
            },
            getPlanLabel: function (plan) {
                const regexDeferred = /^general:1:[\d]{2}:0$/;
                var label = $t('%1 installments').replace('%1', plan.installmentsCount);

                if (regexDeferred.test(plan.key)){
                    label = $t('In %1 days').replace('%1', plan.deferredDays);
                }
                return label;
            },
            triggerIsEnable: function() {
                return window.checkoutConfig.payment[this.item.method].triggerEnable;
            },
            onTriggerLabel: function(numInstallment) {
                var label = $t('%1 month later').replace('%1', numInstallment);
                if (numInstallment==0){
                    label = window.checkoutConfig.payment[this.item.method].triggerLabel;
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
                return priceUtils.formatPrice(this.totals().base_grand_total, window.checkoutConfig.priceFormat);
            },

            customerTotalCostAmount: function (cost) {
                return priceUtils.formatPrice(cost / 100, window.checkoutConfig.priceFormat);
            },

            hasAnnualInterestRate: function (rate) {
                return ( rate!=null && rate > 0 ? true : false );
            },

            totalPaid: function (cost) {
                return priceUtils.formatPrice(parseFloat(this.totals().base_grand_total) + (cost/100), window.checkoutConfig.priceFormat);
            },

            getFeesMention: function (customerFee) {
                return $t('Including fees: %1').replace('%1', this.formattedPrice(customerFee));
            },
            getSelectedPlanForCurrentPaymentMethod:function(){
                const currentPaymentMethod = self.checkedPaymentMethod();
                return self.lastSelectedPlanKey()[currentPaymentMethod];
            },
            getData: function () {
                return $.extend(
                    this._super(),
                    {
                        additional_data: {
                            selectedPlan:  this.getSelectedPlanForCurrentPaymentMethod()
                        }
                    }
                );
            },
            selectAlmaPaymentMethod : function() {
                self.unMountInPage();
                const currentPaymentMethod = self.checkedPaymentMethod();
                if( self.lastSelectedPlan()[currentPaymentMethod].inPageAllowed) {
                    self.selectInstallments(self.lastSelectedPlan()[currentPaymentMethod]);
                }
                self.selectPaymentMethod();
                return true;
            },
            selectInstallments : function(plan){
                if(!plan.inPageAllowed) {
                    return true;
                }
                self.unMountInPage();
                const merchantId = self.config.merchantId
                const totalInCent = self.totals().grand_total * 100
                const installmentsCount = plan.installmentsCount
                const environment = self.config.activeMode
                const locale = self.config.locale
                self.inPage = Alma.initialize({
                    'merchantId': merchantId,
                    'amountInCents': totalInCent,
                    'installmentsCount': installmentsCount,
                    'selector': '#alma-in-page'+'-'+self.checkedPaymentMethod(),
                    'environment': environment.toUpperCase(),
                    'locale': locale.substr(0, 2)
                })
                return true;
            },
            afterPlaceOrder: function () {
                fullScreenLoader.startLoader();
                const redirectUrl = this.config.redirectTo;
                const currentPaymentMethod = self.checkedPaymentMethod();
                if (self.lastSelectedPlan()[currentPaymentMethod].inPageAllowed) {
                    storage.post(
                        redirectUrl,
                        JSON.stringify({'planKey': self.lastSelectedPlan()[currentPaymentMethod].key}),
                        true
                    ).done(
                        function (response) {
                            self.inPage.startPayment(
                                {
                                    paymentId: response.paymentId,
                                    onUserCloseModal : () => {
                                        self.inPageCancelOrder();
                                    }
                                }
                            );
                        }
                    ).fail(
                        function (response) {
                            self.reloadCartSection();
                            messageList.addErrorMessage({ message: $t(response.responseJSON.message) });
                            fullScreenLoader.stopLoader();
                            console.log(response);
                        }
                    );
                } else {
                    $.mage.redirect(redirectUrl);
                }
            },
            unMountInPage : function(){
                if( self.inPage !== undefined) {
                    self.inPage.unmount();
                }
            },
            reloadCartSection : function() {
                var sections = ['cart'];
                customerData.invalidate(sections);
                customerData.reload(sections, true);
            },
            inPageCancelOrder: function (){
                storage.post(
                    this.config.inPageCancelUrl,
                    '',
                    true
                ).done(
                    function (response){
                        self.reloadCartSection();
                    }
                ).fail(
                    function (response){
                        self.reloadCartSection();
                    }
                );
                fullScreenLoader.stopLoader();
            }
        });
    }
);
