define([
        'jquery',
        'Magento_Payment/js/view/payment/cc-form'
    ],
    function ($, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Hiecor_PaymentMethod/payment/hiecorcheckout'
            },

            context: function() {
                return this;
            },

            getCode: function() {
                return 'hiecor_paymentmethod';
            },

            isActive: function() {
                return true;
            }
        });
    }
);