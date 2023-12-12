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
                type: 'whalestack_paymentgateway',
                component: 'Whalestack_PaymentGateway/js/view/payment/method-renderer/whalestack-method'
            }
        );
        return Component.extend({});
    }
);