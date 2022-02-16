<?php

namespace Coinqvest\PaymentGateway\Model\Source;

use Magento\Store\Model\ScopeInterface;
use Coinqvest\PaymentGateway\Api;
use Coinqvest\PaymentGateway\Helper\Data;

class CheckoutDisplayCurrencies
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;
    protected $apiKey;
    protected $apiSecret;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Data $helper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;

        $this->apiKey = $this->scopeConfig->getValue('payment/coinqvest_paymentgateway/api_key', ScopeInterface::SCOPE_STORE);
        $this->apiSecret = $this->scopeConfig->getValue('payment/coinqvest_paymentgateway/api_secret', ScopeInterface::SCOPE_STORE);
    }

    public function toOptionArray()
    {
        return $this->getDisplayCurrencies();
    }

    public function getDisplayCurrencies()
    {
        if (empty($this->apiKey) || empty($this->apiSecret)) {

            return array(
                array('value' => 'USD', 'label' => 'USD - US Dollar')
            );

        }

        return $this->helper->getSettlementCurrencies($this->apiKey, $this->apiSecret);

    }


}
