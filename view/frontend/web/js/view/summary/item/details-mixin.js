define([
    'Magento_Customer/js/customer-data'
], function (customerData) {
    'use strict';

    var mixin = {

        /**
         *
         * @param {Object} quoteItem
         * @return {String}
         */
        getNameUnsanitizedHtml: function (quoteItem) {
            var cartData = customerData.get('cart')();
            var carItems = (cartData && Array.isArray(cartData.items)) ? cartData.items : [];
            for (let item of carItems) {
                if (item.item_id === quoteItem.item_id && item.isInsuranceProduct) {
                    return item.product_name;
                }
            }
            return this._super();
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});
