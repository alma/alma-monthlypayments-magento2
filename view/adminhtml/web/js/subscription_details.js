define([
    'jquery'
], function ($) {
    'use strict';

    return function (data) {
        $(document).ready(function () {
            $('subscription-alma-iframe ').ready(function () {
                const payload = generateIframePayload(data);
                loadDataInIframe(payload);
            })
            window.addEventListener('message', (e) => {
                if (e.data.type === 'sendCancelSubscriptionToCms') {
                    console.log('Cancel subscription', e.data)
                    console.log(data)
                    window.location.href = 'http://adobe-commerce-a-2-4-6.local.test/backadm/'
                }
            })
            function generateIframePayload(data) {
               return {
                    token: '',
                    orderId: data.orderId,
                    orderReference: data.incrementId, // Order increment ID -> join with order_id
                    mode: data.mode, // test or live In collection
                    orderDate: data.orderDate, // Order date -> join with order_id
                    firstName: data.firstName, // Customer first name -> join
                    lastName: data.lastName, // Customer last name -> join
                    errorMessage: '',
                    successMessage: '',
                    cmsSubscriptions: getCmsSubscriptions(data.subscriptions),
                }
            }
            function getCmsSubscriptions(subscriptions) {
                return subscriptions.map(subscription => {
                    return {
                        id: subscription.entity_id,
                        productName: subscription.linked_product_name,
                        insuranceName: subscription.name,
                        status: subscription.subscription_state,
                        productPrice: subscription.linked_product_price,
                        subscriptionAmount: subscription.subscription_amount,
                        isRefunded: subscription.is_refunded,
                        reasonForCancelation: subscription.reason_of_cancelation,
                        dateOfCancelation: subscription.date_of_cancelation,
                        dateOfCancelationRequest: subscription.date_of_cancelation_request,
                        subscriptionBrokerId: subscription.subscription_broker_id,
                    }
                })
            }
            function loadDataInIframe (payload) {
                setTimeout(() =>
                {
                    if( typeof(getSubscriptionDatafromCms) !== 'undefined') {
                        getSubscriptionDatafromCms(payload)
                    } else {
                        loadDataInIframe(payload)
                    }
                } , 150);
            }
        });
    }
});
