define([
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
        'use strict';

        rendererList.push(
            {
                type: 'hiecor_paymentmethod',
                component: 'Hiecor_PaymentMethod/js/view/payment/method-renderer/hiecorcheckout'
            }
        );

        /** Add view logic here if needed */
        return Component.extend({});
    });
