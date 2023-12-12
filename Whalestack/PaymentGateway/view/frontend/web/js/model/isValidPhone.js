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

                let shippingPhoneGuest = $('form div[name="shippingAddress.telephone"] input').val();
                let shippingPhoneMember = $('div.billing-address-details a').html();

                if (shippingPhoneGuest === null && shippingPhoneMember === null) {
                    // if both phone numbers can't be found, e.g. in One Page Checkout Extension, return here without checks
                    return true;
                }

                let result = null;

                if (shippingPhoneGuest !== '') {
                    result = this.validatePhoneNumber(shippingPhoneGuest);
                } else if (shippingPhoneMember !== '') {
                    result = this.validatePhoneNumber(shippingPhoneMember);
                }

                if (result.success === false) {
                    messageList.addErrorMessage({message: $t(result.message)});
                }

                return result.success;

            },

            validatePhoneNumber: function(phone) {

                if (phone == null) {
                    return { "success" : true }
                }

                if (phone.length > 16) {
                    return {
                        "success": false,
                        "message": "Phone number can be max. 16 digits."
                    }
                }

                return {
                    "success" : true
                }

            }

        };
    }

);