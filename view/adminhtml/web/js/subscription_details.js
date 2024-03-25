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

                    cancelSubscription(e.data.cmsSubscription.subscriptionId, e.data.cmsSubscription.subscriptionBrokerId, e.data.reasonContent)
                }
            })

            function cancelSubscription(subscriptionId, brokerId, reasonContent = '') {
                $.ajax({
                    url: data.controllerCancelUrl,
                    type: 'POST',
                    data: {
                        subscriptionId: subscriptionId,
                        cancelReason: reasonContent
                    },
                    success: function (result) {
                        console.log(result.message)
                        sendNotificationToIFrame([
                            {subscriptionBrokerId: brokerId, newStatus: result.state},
                        ])
                    },
                    error: function (result) {
                        console.log('Error', result)
                    }
                });
            }

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
                    console.log(subscription);
                    return {
                        id: subscription.entity_id,
                        productName: subscription.linked_product_name,
                        productPrice: subscription.linked_product_price,
                        insuranceName: subscription.name,
                        status: subscription.subscription_state,
                        subscriptionId: subscription.subscription_id,
                        subscriptionBrokerId: subscription.subscription_broker_id,
                        subscriptionAmount: subscription.subscription_amount,
                        isRefunded: subscription.is_refunded === '1',
                        reasonForCancelation: subscription.reason_of_cancelation,
                        dateOfCancelation: subscription.date_of_cancelation,
                        dateOfCancelationRequest: subscription.date_of_cancelation_request,
                    }
                })
            }

            function loadDataInIframe(payload) {
                setTimeout(() => {
                    if (typeof (getSubscriptionDatafromCms) !== 'undefined') {
                        getSubscriptionDatafromCms(payload)
                    } else {
                        loadDataInIframe(payload)
                    }
                }, 250);
            }
        });
    }
});
