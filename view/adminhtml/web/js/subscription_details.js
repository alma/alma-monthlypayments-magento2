require([
    'jquery'
], function ($) {
    'use strict';
    $(document).ready(function () {
        $('subscription-alma-iframe ').ready(function () {
            const payload = {
                token: 'jslsn8kjsqb3nskq45',
                orderId: '1234567',
                orderReference: 'd4cb55a9',
                mode: 'test',
                orderDate: '2024-01-05',
                firstName: 'Jane',
                lastName: 'Doe',
                errorMessage: '',
                successMessage: '',
                cmsSubscriptions: [
                    {
                        id: 1,
                        productName: 'Product 1',
                        insuranceName: 'panne + casse + vol',
                        status: 'started',
                        productPrice: 15099,
                        subscriptionAmount: 5130,
                        isRefunded: false,
                        reasonForCancelation: '',
                        dateOfCancelation: '',
                        dateOfCancelationRequest: '',
                        subscriptionBrokerId: '1451bfc6',
                    }
                ],
            }
            loadDataInIframe(payload);
        })
        window.addEventListener('message', (e) => {
            if (e.data.type === 'sendCancelSubscriptionToCms') {
                console.log('Cancel subscription', e.data)
                window.location.href = 'http://adobe-commerce-a-2-4-6.local.test/backadm/'
            }
        })
    });

    const loadDataInIframe = (payload) => {
        setTimeout(() =>
        {
            if( typeof(getSubscriptionDatafromCms) !== 'undefined') {
                getSubscriptionDatafromCms(payload)
            } else {
                loadDataInIframe(payload)
            }
        } , 150)
    }
});
