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
    function convertHtmlPriceToCent(priceHtml) {
        var centMultiplier = getCentMultiplier(priceHtml);
        var price = priceHtml.replace(/[^\d]/g,"");
        return price * centMultiplier;
    }

    var qty = '1';
    var qtyNode = document.getElementById('qty');
    qtyNode.addEventListener('input',function (e) {
        qty = e.target.value;
    });

    var widgetSku = '';

    function getProductBasePrice(basePrice, productId) {
        let finalPrice = basePrice;
        const productPriceBlock = $('#product-price-' +productId +' .price');
        if (productPriceBlock.length){
            finalPrice = convertHtmlPriceToCent(productPriceBlock.html())
        }
        return finalPrice.toString();
    }

    return {
        setSkuForWifget : function (sku){
            widgetSku = sku;
        },
        refreshWidget: function (basePrice, productID, productName, merchantId, quoteId, sessionId){
            const insuranceSelected = false;
            var finalPrice = getProductBasePrice(basePrice, productID);
            getProductDataForApiCall(
                widgetSku,
                finalPrice,
                productName,
                merchantId,
                qty,
                quoteId,
                sessionId,
                insuranceSelected
            )
        }
    };
});
