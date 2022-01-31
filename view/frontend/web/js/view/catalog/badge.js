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

        function initWidget(merchantId, apiMode, containerId, purchaseAmount,locale, plans) {
            console.log(merchantId)
            console.log(apiMode)
            console.log(containerId)
            console.log(purchaseAmount)
            console.log(locale)
            console.log(plans)
            var widgets = Alma.Widgets.initialize(merchantId, apiMode);
            console.log(widgets);
            widgets.add(Alma.Widgets.PaymentPlans, {
                container: '#' + containerId,
                purchaseAmount: purchaseAmount,
                locale: locale,
                plans: plans
            });
        }

        function formatPrice(priceHtml)
        {
            var price = priceHtml.replace(/[^\d]/g,"");
            var qty = $('#qty').val();
            if(!qty.match(/^\d+$/) || !(qty > 0))
            {
                qty = 1;
            }
            return price * qty;
        }

        function getPriceFromContainer(priceContainer)
        {
            if(priceContainer !== undefined && priceContainer !== null)
            {
                var priceHtml = priceContainer.html();
                console.log(priceHtml);

                if(priceHtml !== undefined && priceHtml !== null)
                {
                    return formatPrice(priceHtml);
                }
            }
            return false;
        }

        function getPrice() {
            if(config.useQuantityForWidgetPrice){
                console.log(config.useQuantityForWidgetPrice)
                console.log(`#product-price-${config.productId}.price`)
                var priceContainer = $(`#product-price-${config.productId} .price`);
                var price = getPriceFromContainer(priceContainer);
                console.log(price)
                if(price > 0){ return price; }

                priceContainer = $(`#price-including-tax-product-price-${config.productId} .price`);
                price = getPriceFromContainer(priceContainer);
                console.log(price)
                if(price > 0){ return price; }
                console.error('Price container not found, fallback to price without qty installments')
            }
            return config.productId;
        }

        function updateWidget() {
            console.log('update')
            var price = getPrice();
            if(price !== false && price > 0){
                console.log('Alma.ApiMode[config.activeMode]')
                console.log(Alma.ApiMode[config.activeMode])
                initWidget(
                    config.merchandId,
                    Alma.ApiMode[config.activeMode],
                'alma-widget',
                    price,
                    config.locale,
                    config.jsonPlans
            );
            }
        }

        var callback = function(mutationsList) {
            for(var mutation of mutationsList) {
                if (mutation.type == 'childList')
                {
                    updateWidget();
                }
            }
        }
        console.log('init')
        var targetNode = document.getElementById(`product-price-${config.productId}`);
        if(targetNode === null || targetNode === undefined)
        {
            targetNode = document.getElementById(`price-including-tax-product-price-${config.productId}`);
        }else{
        }
        if(targetNode !== undefined && targetNode !== null)
        {
            console.log('init 2 ')
            var observer = new MutationObserver(callback);
            // https://developer.mozilla.org/fr/docs/Web/API/MutationObserver#MutationObserverInit
            observer.observe(targetNode, { childList: true });
            $('#qty').change(function() {
                updateWidget();
            });
        }else{
            console.log('init3')

            updateWidget();
        }
        console.log('init 1 ')
        updateWidget();
    };
});
