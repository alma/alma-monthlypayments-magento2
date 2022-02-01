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
], function (Alma,$,priceUtils) {
    'use strict';

    return function (config) {

        var widgets = Alma.Widgets.initialize(config.merchantId, Alma.ApiMode[config.activeMode]);
        var qtyNode = document.getElementById('qty');
        qtyNode.addEventListener("input",function(){updateWidget()});

        moveToCustomPosition();
        updateWidget();

        function moveToCustomPosition(){
            if(config.customDisplay.hasCustomPosition && $(config.customDisplay.customContainerSelector) != undefined ){
                var position = 'append';
                if(config.customDisplay.isPrepend) {
                    position = 'prepend'
                }
                $(config.customDisplay.customContainerSelector)[position]($('#'+config.containerId));
            }
        };

        function updateWidget(){
            widgets.add(
                Alma.Widgets.PaymentPlans, {
                    container: '#' + config.containerId,
                    purchaseAmount: getPrice(),
                    locale: config.locale,
                    plans: config.jsonPlans
                }
            );
        };

        function getPrice() {
            var price = config.productPrice;
            if(config.useQuantityForWidgetPrice){
                var priceContainer = $(`#product-price-${config.productId} .price`);
                if (!priceContainer.length){
                    // Only if tax config diplay with and without tax
                    priceContainer = $(`#price-including-tax-product-price-${config.productId} .price`);
                }
                var frontPrice = getPriceFromContainer(priceContainer);
                if( frontPrice > 0){
                    price = frontPrice;
                }
            }
            return price ;
        };

        function getPriceFromContainer(priceContainer)
        {
            if(priceContainer !== undefined && priceContainer !== null)
            {
                var priceHtml = priceContainer.html();
                if(priceHtml !== undefined && priceHtml !== null)
                {
                    return formatPrice(priceHtml);
                }
            }
            return false;
        };

        function formatPrice(priceHtml)
        {
            var price = priceHtml.replace(/[^\d]/g,"");
            var qty = $('#qty').val();
            if(!qty.match(/^\d+$/) || !(qty > 0))
            {
                qty = 1;
            }
            return price * qty;
        };
    };
});
