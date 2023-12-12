<?php

namespace Whalestack\PaymentGateway\Model\Source;

use Magento\Store\Model\ScopeInterface;
use Whalestack\PaymentGateway\Helper\Data;

class SettlementAssets
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

        $this->apiKey = $this->scopeConfig->getValue('payment/whalestack_paymentgateway/api_key', ScopeInterface::SCOPE_STORE);
        $this->apiSecret = $this->scopeConfig->getValue('payment/whalestack_paymentgateway/api_secret', ScopeInterface::SCOPE_STORE);
    }

    public function toOptionArray()
    {
        return $this->getSettlementAssets();
    }

    public function getSettlementAssets()
    {
        // Set default values
        if (empty($this->apiKey) || empty($this->apiSecret))
        {
            return array(
                array('value' => 'USDC', 'label' => 'USDC - USD Coin')
            );
        }

        $assets =$this->helper->getSettlementAssets($this->apiKey, $this->apiSecret);
        array_unshift($assets , array('value' => '0', 'label' => 'Please select...'));
        array_push($assets, array('value' => 'ORIGIN', 'label' => 'ORIGIN - Settle to the cryptocurrency your client pays with'));

        return $assets;
    }


}
