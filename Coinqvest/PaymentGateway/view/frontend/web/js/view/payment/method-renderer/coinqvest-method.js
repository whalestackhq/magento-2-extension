define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'mage/url',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function ($, Component, url, customerData, errorProcessor, fullScreenLoader) {
        'use strict';
        return Component.extend({
            redirectAfterPlaceOrder: false,
            defaults: {
                template: 'Coinqvest_PaymentGateway/payment/coinqvest-form'
            },
            getMailingAddress: function () {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            },
            afterPlaceOrder: function () {
                var custom_controller_url = url.build('coinqvest/payment/create');

                $.post(custom_controller_url, 'json')
                    .done(function (response) {
                        window.location.href = response.redirectUrl;
                    })
                    .fail(function (response) {
                        errorProcessor.process(response, this.messageContainer);
                    })
                    .always(function () {
                        fullScreenLoader.stopLoader();
                    });
            },
            getCoinqvestLogo: function () {
                if (window.checkoutConfig.payment.coinqvest_logo[this.item.method] === "1") {
                    return require.toUrl('Coinqvest_PaymentGateway/img/coinqvest-icon.png');
                }
            },
            getCoinqvestMessage: function () {
                return window.checkoutConfig.payment.coinqvest_message[this.item.method];
            }
        });
    }
);
