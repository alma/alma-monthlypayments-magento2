/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
], function ($) {
    'use strict';

    /**
     *
     * @param   {string} priceHtml The price extracted from HTML
     * @returns {number} 1 for flaot or 100 for integer
     */
    function getCentMultiplier (priceHtml){
        var multiplier = 1;
        var countSeparator = priceHtml.match(/[.,]/g) || [];
        if (countSeparator.length == 0 || (countSeparator.length == 1 && (/[.,][\d]{3}/g).test(priceHtml))){
            multiplier = 100;
        }
        return multiplier;
    }
    return {

        /**
         *
         * @param priceHtml The price extracted from HTML
         * @returns {number} price in cent
         */
        convertHtmlPriceToCent : function (priceHtml){
        var centMultiplier = getCentMultiplier(priceHtml);
        var price = priceHtml.replace(/[^\d]/g,"");
        return price * centMultiplier;
        }
    };
});
