define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Coinqvest_PaymentGateway/js/model/isValidPhone'
    ],
    function (Component, additionalValidators, phoneValidation) {
        'use strict';
        additionalValidators.registerValidator(phoneValidation);
        return Component.extend({});
    }
);