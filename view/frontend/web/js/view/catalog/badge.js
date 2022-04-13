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
        'widgets/Alma': 'https://cdn.jsdelivr.net/npm/@alma/widgets@2.x.x/dist/widgets.umd'
    }
});
define([
    'widgets/Alma',
    'jquery',
    'Magento_Catalog/js/price-utils'
], function (Alma,$,priceUtils){
    'use strict';

    return function (config){
        var priceContainer = getHtmlPriceContainer(config.productId,false);
        priceContainer.on('DOMSubtreeModified', function () {
            updateWidget();
        });
        var widgets = Alma.Widgets.initialize(config.merchantId, Alma.ApiMode[config.activeMode]);
        var qtyNode = document.getElementById('qty');
        qtyNode.addEventListener("input",function(){updateWidget()});

        moveToCustomPosition(config.customDisplay,config.containerId);
        updateWidget();

        function updateWidget(){
            widgets.add(
                Alma.Widgets.PaymentPlans, {
                    container: '#' + config.containerId,
                    purchaseAmount: getPrice(config.productPrice,config.useQuantityForWidgetPrice,config.productId),
                    locale: config.locale,
                    plans: config.jsonPlans
                }
            );
        }
    }

    /**
     *
     * @param customDisplay Widget config define in view/frontend/templates/catalog/product/view.phtml
     * @param baseContainerId Base container who contain the widget
     */
    function moveToCustomPosition(customDisplay,baseContainerId){
        if(customDisplay.hasCustomPosition && $(customDisplay.customContainerSelector) != undefined ){
            var position = 'append';
            if(customDisplay.isPrepend) {
                position = 'prepend'
            }
            $(customDisplay.customContainerSelector)[position]($('#'+baseContainerId));
        }
    }

    /**
     *
     * @param productPrice Php product price in cent - not good for configurable products
     * @param useQuantityForWidgetPrice back office setup
     * @param productId product Id
     * @returns {number} price in cent
     */
    function getPrice(productPrice,useQuantityForWidgetPrice,productId){
        var price = productPrice;
        if(useQuantityForWidgetPrice){
            var priceContainer = getHtmlPriceContainer(productId,'price');
            var frontPrice = getPriceFromContainer(priceContainer);

            if( frontPrice > 0){
                price = frontPrice;
            }
        }
        return price ;
    }

    /**
     *
     * @param productId Id of product for Id concatenation
     * @param subClass Sub class to select
     * @returns {*}
     */
    function getHtmlPriceContainer(productId,subClass = false){
        var classToSelect = ''
        if (subClass){
            classToSelect = '.'+subClass;
        }
        var priceContainer = $(`#product-price-${productId} ${classToSelect}`);
        if (!priceContainer.length){
            // Only if tax config is diplay with and without tax
            priceContainer = $(`#price-including-tax-product-price-${productId} ${classToSelect}`);
        }
        return priceContainer;
    }

    /**
     *
     * @param priceContainer Container with the price to extract
     * @returns {number} price in cent
     */
    function getPriceFromContainer(priceContainer){
        var price = 0;
        if(priceContainer !== undefined && priceContainer !== null && priceContainer.html() !== undefined && priceContainer.html() !== null)
        {
            price = getPricePerQty(convertHtmlPriceToCent(priceContainer.html()));
        }
        return price;
    }

    /**
     *
     * @param priceHtml The price extracted from HTML
     * @returns {number} price in cent
     */
    function convertHtmlPriceToCent(priceHtml){
        var centMultiplier = getCentMultiplier(priceHtml);
        var price = priceHtml.replace(/[^\d]/g,"");
        return price * centMultiplier;
    }

    /**
     *
     * @param {number} priceInCent Html price in cent
     * @returns {number} final price for qty
     */
    function getPricePerQty(priceInCent){
        var qty = $('#qty').val();
        if(!qty.match(/^\d+$/) || (qty <= 0))
        {
            qty = 1;
        }
        return priceInCent * qty;
    }

    /**
     *
     * @param   {string} priceHtml The price extracted from HTML
     * @returns {number} 1 for flaot or 100 for integer
     */
    function getCentMultiplier(priceHtml){
        var multiplier = 1;
        var countSeparator = priceHtml.match(/[.,]/g) || [];
        if (countSeparator.length == 0 || (countSeparator.length == 1 && (/[.,][\d]{3}/g).test(priceHtml))){
            multiplier = 100;
        }
        return multiplier;
    }

});
