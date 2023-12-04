/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/validation'
], function ($) {
    'use strict';

    var agreementsInputPath = '.payment-method._active .alma-validation-input input';

    return {
        /**
         * Validate checkout agreements
         *
         * @returns {Boolean}
         */
        validate: function (hideError) {
            var isValid = true;

            if ($(agreementsInputPath).length === 0) {
                return true;
            }

            $(agreementsInputPath).each(function (index, element) {
                if (!$.validator.validateSingleElement(element, {
                    errorElement: 'div',
                    hideError: hideError || false
                })) {
                    isValid = false;
                }
            });

            return isValid;
        }
    };
});
