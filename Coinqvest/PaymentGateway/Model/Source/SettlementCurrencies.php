<?php

namespace Coinqvest\PaymentGateway\Model\Source;

use Magento\Store\Model\ScopeInterface;
use Coinqvest\PaymentGateway\Api;

class SettlementCurrencies
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;
    protected $apiKey;
    protected $apiSecret;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;

        $this->apiKey = $this->scopeConfig->getValue('payment/coinqvest_paymentgateway/api_key', ScopeInterface::SCOPE_STORE);
        $this->apiSecret = $this->scopeConfig->getValue('payment/coinqvest_paymentgateway/api_secret', ScopeInterface::SCOPE_STORE);
    }

    public function toOptionArray()
    {
        return $this->getSettlementCurrencies();
    }

    public function getSettlementCurrencies()
    {
        $currencies = array(
            array('value' => '0', 'label' => 'Please select...')
        );

        /**
         * Init COINQVEST API
         */

        if (!empty($this->apiKey) && !empty($this->apiSecret))
        {
            $client = new Api\CQMerchantClient(
                $this->apiKey,
                $this->apiSecret
            );

            $response = $client->get('/fiat-currencies');
            $fiat_currencies = json_decode($response->responseBody);

            if ($response->httpStatusCode == 200)
            {
                foreach ($fiat_currencies->fiatCurrencies as $currency)
                {
                    array_push(
                        $currencies,
                        array('value' => $currency->assetCode, 'label' => $currency->assetCode . ' - ' . $currency->assetName)
                    );
                }

            }

            $response = $client->get('/blockchains');
            $chains = json_decode($response->responseBody);

            if ($response->httpStatusCode == 200)
            {
                foreach ($chains->blockchains as $blockchain)
                {
                    array_push(
                        $currencies,
                        array('value' => $blockchain->nativeAssetCode, 'label' => $blockchain->nativeAssetCode . ' - ' . $blockchain->nativeAssetName)
                    );
                }

            }

        }

        return $currencies;
    }


}
