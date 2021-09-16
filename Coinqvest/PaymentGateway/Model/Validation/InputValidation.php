<?php

namespace Coinqvest\PaymentGateway\Model\Validation;

use Magento\Store\Model\ScopeInterface;
use Coinqvest\PaymentGateway\Api;

class InputValidation extends \Magento\Framework\App\Config\Value
{

    public function beforeSave()
    {
        // Submitted form fields:
        $cqData = $this->getData()['groups']['coinqvest_paymentgateway']['fields'];

        $apiKey = $cqData['api_key']['value'];
        $apiSecret = $cqData['api_secret']['value'];
        $settlementCurrency = $cqData['settlement_currency']['value'];

        // check API auth settings
        $client = new Api\CQMerchantClient($apiKey, $apiSecret);
        if (!$this->isAuth($client)) {
            throw new \Magento\Framework\Exception\ValidatorException(__('COINQVEST - Api key and/or secret are wrong. Please double check.'));
        }

        // check if shop currency is a supported fiat currency
        $shopCurrencies = array(
            $this->getStore()->getBaseCurrencyCode(),
            $this->getStore()->getCurrentCurrencyCode(),
            $this->getStore()->getDefaultCurrencyCode()
        );

        $fiats = $this->getFiatCurrencies($client);

        // if any of BaseCurrency, CurrentCurrency or DefaultCurrency is not in supported fiats, make merchant set a settlement currency
        if ($settlementCurrency == '0') {
            foreach ($shopCurrencies as $shopCurrency) {
                if (!array_key_exists($shopCurrency, $fiats)) {
                    throw new \Magento\Framework\Exception\ValidatorException(__('COINQVEST - Please select a settlement currency.'));
                }
            }
        }

        $this->setValue($this->getValue());

        parent::beforeSave();
    }


    protected function getStore()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
        return $storeManager->getStore();
    }

    protected function getFiatCurrencies($client)
    {
        $currencies = array();
        $response = $client->get('/fiat-currencies');
        if ($response->httpStatusCode == 200) {
            $fiats = json_decode($response->responseBody);
            foreach ($fiats->fiatCurrencies as $currency) {
                $currencies[$currency->assetCode] = $currency->assetCode . ' - ' .$currency->assetName;
            }

        }
        return $currencies;
    }

    protected function isAuth($client)
    {
        $response = $client->get('/auth-test');
        if ($response->httpStatusCode != 200) {
            return false;
        }
        return true;
    }
}