<?php

namespace Coinqvest\PaymentGateway\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Coinqvest\PaymentGateway\Api;

class Data extends AbstractHelper
{

    public function isCustomStoreCurrency($apiKey, $apiSecret)
    {
        if (empty($apiKey) && empty($apiSecret))
        {
            return false;
        }

        // check if shop currency is a supported fiat or blockchain currency
        $shopCurrencies = array(
            $this->getStore()->getBaseCurrencyCode(),
            $this->getStore()->getCurrentCurrencyCode(),
            $this->getStore()->getDefaultCurrencyCode()
        );

        $client = new Api\CQMerchantClient($apiKey, $apiSecret);

        $supportedCurrencies = $this->getSupportedCurrencies($client);

        // if any of BaseCurrency, CurrentCurrency or DefaultCurrency is not in supported currencies, display the field 'Checkout Page Display Currency'
        foreach ($shopCurrencies as $shopCurrency) {
            if (!in_array($shopCurrency, $supportedCurrencies)) {
                return true;
            }
        }

        return false;

    }

    public function isCustomOrderCurrency($apiKey, $apiSecret, $currencyCode)
    {
        if (empty($apiKey) && empty($apiSecret))
        {
            return false;
        }

        $client = new Api\CQMerchantClient($apiKey, $apiSecret);
        $supportedCurrencies = $this->getSupportedCurrencies($client);

        if (!in_array($currencyCode, $supportedCurrencies)) {
            return true;
        }

        return false;

    }

    public function getSettlementCurrencies($apiKey, $apiSecret)
    {

        $currencies = array();

        /**
         * Init COINQVEST API
         */

        if (!empty($apiKey) && !empty($apiSecret))
        {
            $client = new Api\CQMerchantClient($apiKey, $apiSecret);

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



    protected function getStore()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
        return $storeManager->getStore();
    }


    protected function getSupportedCurrencies($client)
    {

        $currencies = array();

        $response = $client->get('/fiat-currencies');
        $fiat_currencies = json_decode($response->responseBody);

        if ($response->httpStatusCode == 200)
        {
            foreach ($fiat_currencies->fiatCurrencies as $currency)
            {
                array_push($currencies, $currency->assetCode);
            }

        }

        $response = $client->get('/blockchains');
        $chains = json_decode($response->responseBody);

        if ($response->httpStatusCode == 200)
        {
            foreach ($chains->blockchains as $blockchain)
            {
                array_push($currencies, $blockchain->nativeAssetCode);
            }

        }

        return $currencies;

    }


    public function writeToLog($data, $title = null)
    {
        $logFile = $_SERVER["DOCUMENT_ROOT"] . '/app/code/Coinqvest/PaymentGateway/Log/Coinqvest.log';

        $type = file_exists($logFile) ? 'a' : 'w';
        $file = fopen($logFile, $type);
        fputs($file, date('r', time()) . ' ====' . $title . '====' . PHP_EOL);
        fputs($file, date('r', time()) . ' ' . print_r($data, true) . PHP_EOL);
        fclose($file);
    }

}