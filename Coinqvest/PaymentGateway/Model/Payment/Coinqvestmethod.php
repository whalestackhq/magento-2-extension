<?php

namespace Coinqvest\PaymentGateway\Model\Payment;

class Coinqvestmethod extends \Magento\Payment\Model\Method\AbstractMethod
{
    const CODE = 'coinqvest_paymentgateway';

    protected $_code = "coinqvest_paymentgateway";

    public function isAvailable(
        \Magento\Quote\Api\Data\CartInterface $quote = null
    ) {

        $apiKey = $this->_scopeConfig->getValue(
            'payment/coinqvest_paymentgateway/api_key',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $apiSecret = $this->_scopeConfig->getValue(
            'payment/coinqvest_paymentgateway/api_secret',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!$apiKey || !$apiSecret) {
            return false;
        }
        return parent::isAvailable($quote);
    }
}