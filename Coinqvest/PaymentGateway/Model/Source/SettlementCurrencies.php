<?php

namespace Coinqvest\PaymentGateway\Model\Source;

use Magento\Store\Model\ScopeInterface;
use Coinqvest\PaymentGateway\Helper\Data;

class SettlementCurrencies
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
        return $this->getSettlementCurrencies();
    }

    public function getSettlementCurrencies()
    {

        // Set default values
        if (empty($this->apiKey) || empty($this->apiSecret))
        {
            return array(
                array('value' => 'USD', 'label' => 'USD - US Dollar')
            );

        }

        $currencies = $this->helper->getSettlementCurrencies($this->apiKey, $this->apiSecret);

        // add this to top of array, if currency is supported by CQ
        if (!$this->helper->isCustomStoreCurrency($this->apiKey, $this->apiSecret)) {
            array_unshift($currencies , array('value' => '0', 'label' => 'Please select...'));
        }

        // add ORIGIN option to end of array
        array_push($currencies, array('value' => 'ORIGIN', 'label' => 'ORIGIN - Settle to the cryptocurrency your client pays with'));

        return $currencies;
    }


}
