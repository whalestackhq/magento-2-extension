<?php

namespace Whalestack\PaymentGateway\Model\Payment;

class Whalestackmethod extends \Magento\Payment\Model\Method\AbstractMethod
{
    const CODE = 'whalestack_paymentgateway';

    protected $_code = "whalestack_paymentgateway";

    public function isAvailable(
        \Magento\Quote\Api\Data\CartInterface $quote = null
    ) {

        $apiKey = $this->_scopeConfig->getValue(
            'payment/whalestack_paymentgateway/api_key',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $apiSecret = $this->_scopeConfig->getValue(
            'payment/whalestack_paymentgateway/api_secret',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!$apiKey || !$apiSecret) {
            return false;
        }
        return parent::isAvailable($quote);
    }
}