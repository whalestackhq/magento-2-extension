define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'coinqvest_paymentgateway',
                component: 'Coinqvest_PaymentGateway/js/view/payment/method-renderer/coinqvest-method'
            }
        );
        return Component.extend({});
    }
);