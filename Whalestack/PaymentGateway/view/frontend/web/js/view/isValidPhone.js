define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Whalestack_PaymentGateway/js/model/isValidPhone'
    ],
    function (Component, additionalValidators, phoneValidation) {
        'use strict';
        additionalValidators.registerValidator(phoneValidation);
        return Component.extend({});
    }
);