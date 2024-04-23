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
            var carItems = customerData.get('cart')().items;
            for (let item of carItems){
                if(item.item_id == quoteItem.item_id) {
                    if (item.isInsuranceProduct){
                        return item.product_name;
                    }
                    return this._super();
                }
            }
            return this._super();
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});
