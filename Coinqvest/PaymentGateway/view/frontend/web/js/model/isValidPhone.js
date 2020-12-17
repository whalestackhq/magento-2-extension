define(
    [
        'jquery',
        'mage/translate',
        'Magento_Ui/js/model/messageList',
        'mage/validation'
    ],
    function ($, $t, messageList) {
        'use strict';
        return {

            validate: function () {

                var validPhone = true;

                var shippingPhoneGuest = $('form div[name="shippingAddress.telephone"] input').val();
                var shippingPhoneMember = $('div.billing-address-details a').html();

                if (shippingPhoneGuest !== '') {

                    var res = this.validatePhoneNumber(shippingPhoneGuest);

                    validPhone = res.success;
                    if (validPhone === false) {
                        messageList.addErrorMessage({message: $t(res.message)});
                    }
                } else if (shippingPhoneMember !== '') {

                    var res = this.validatePhoneNumber(shippingPhoneMember);

                    validPhone = res.success;
                    if (validPhone === false) {
                        messageList.addErrorMessage({message: $t(res.message)});
                    }

                }

                return validPhone;
            },

            validatePhoneNumber: function(phone) {

                if (phone.length < 10 || phone.length > 16) {

                    return {
                        "success": false,
                        "message": "Phone number must be between 10 and 16 digits."
                    }

                } else if (/[^\d\+\-\(\)\s\.\/\#]/.test(phone)) {

                    return {
                        "success": false,
                        "message": "Phone number has not the right format."
                    }

                } else {

                     return {
                         "success" : true
                     }
                }

            }

        };
    }

);