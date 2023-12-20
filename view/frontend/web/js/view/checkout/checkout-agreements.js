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
                checkboxText: $t('By accepting to subscribe to Alma insurance, I confirm my thorough review, acceptance, and retention of the general terms outlined in the information booklet and the insurance product details. Additionally, I consent to receiving contractual information by e-mail for the purpose of securely storing it in a durable format.'),
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

        /**
         * Checks if agreement required
         */
        isAgreementRequired: function () {
            var cart = customerData.get('cart');
            for (var [key, item] of Object.entries(cart().items)){
                if (item.hasInsurance) {
                    return true;
                }
            }


            return false;
        },

        isVisible: 1
    });
});
