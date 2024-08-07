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
            return $t("I hereby acknowledge my acceptance to subscribe to the insurance offered by Alma. In doing so, I confirm that I have previously reviewed the <a href='{1}' target='_blank' > information notice, which constitutes the general conditions</a>, the <a href='{2}' target='_blank' >insurance product information document</a>, and <a href='{3}' target='_blank' >the pre-contractual information and advice sheet</a> I ahead to it without reservation and agree to electronically sign the various documents forming my contract, if applicable. I expressly consent to the collection and use of my personal data for the purpose of subscribing to and managing my insurance contract(s).").replace('{1}', noticeFileUrl).replace('{2}', ipidFileUrl).replace('{3}', ficFileUrl);
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
