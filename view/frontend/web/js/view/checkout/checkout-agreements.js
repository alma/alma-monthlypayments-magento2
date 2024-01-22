/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'ko',
    'jquery',
    'uiComponent',
    'mage/translate',
    'Magento_Customer/js/customer-data'
], function (ko, $, Component, $t, customerData) {
    'use strict';
    return Component.extend({
        agreements: [
            {
                checkboxText: '',
                agreementId: '999',
                mode: '1'
            }
        ],
        defaults: {
            template: 'Alma_MonthlyPayments/checkout/checkout-agreements'
        },
        /**
         * build a unique id for the term checkbox
         *
         * @param {Object} context - the ko context
         * @param {Number} agreementId
         */
        getCheckboxId: function (context, agreementId) {
            var paymentMethodName = '',
                paymentMethodRenderer = context.$parents[1];

            // corresponding payment method fetched from parent context
            if (paymentMethodRenderer) {
                // item looks like this: {title: "Check / Money order", method: "checkmo"}
                paymentMethodName = paymentMethodRenderer.item ?
                    paymentMethodRenderer.item.method : '';
            }

            return 'agreement_' + paymentMethodName + '_' + agreementId;
        },
        getCheckboxesText: function () {
            var noticeFileUrl = ''
            var ipidFileUrl = ''
            var ficFileUrl = ''
            var cart = customerData.get('cart');
            for (var [key, item] of Object.entries(cart().items)) {
                if (item.hasInsurance) {
                    item.insuranceFiles.map((file) => {
                        switch (file.type) {
                            case 'notice-document':
                                noticeFileUrl = file.url
                                break;
                            case 'fic-document':
                                ficFileUrl = file.url
                                break;
                            case 'ipid-document':
                                ipidFileUrl = file.url
                                break;
                            default:
                                console.log(`Sorry, type not exist ${file.type}.`);
                        }
                    })
                }
            }
            return $t("By accepting to subscribe to Alma insurance, I confirm my thorough review, acceptance, and retention of the general terms outlined in the <a href='{0}' target='_blank' >information booklet</a> and the <a href='{1}' target='_blank' >insurance product details</a>. Additionally, I consent to receiving contractual information by e-mail for the purpose of securely storing it in a durable format.").replace('{0}', noticeFileUrl).replace('{1}', ficFileUrl);
        },

        /**
         * Checks if agreement required
         */
        isAgreementRequired: function () {
            var cart = customerData.get('cart');
            for (var [key, item] of Object.entries(cart().items)) {
                if (item.hasInsurance) {
                    return true;
                }
            }


            return false;
        },

        isVisible: 1
    });
});
