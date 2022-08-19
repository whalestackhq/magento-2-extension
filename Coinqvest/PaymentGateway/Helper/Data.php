<?php

namespace Coinqvest\PaymentGateway\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Coinqvest\PaymentGateway\Api;

class Data extends AbstractHelper
{
    public function getSettlementAssets($apiKey, $apiSecret)
    {
        $assets = array();

        if (!empty($apiKey) && !empty($apiSecret))
        {
            $client = new Api\CQMerchantClient($apiKey, $apiSecret);

            $response = $client->get('/assets');
            if ($response->httpStatusCode == 200)
            {
                $items = json_decode($response->responseBody);
                foreach ($items->assets as $asset)
                {
                    array_push(
                        $assets,
                        array('value' => $asset->assetCode, 'label' => $asset->assetCode . ' - ' . $asset->name)
                    );
                }
            }
        }
        return $assets;
    }

    protected function getStore()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
        return $storeManager->getStore();
    }

    public function writeToLog($data, $title = null)
    {
        $logFile = $_SERVER["DOCUMENT_ROOT"] . '/vendor/coinqvest/paymentgateway/Log/Coinqvest.log';

        $type = file_exists($logFile) ? 'a' : 'w';
        $file = fopen($logFile, $type);
        fputs($file, date('r', time()) . ' ====' . $title . '====' . PHP_EOL);
        fputs($file, date('r', time()) . ' ' . print_r($data, true) . PHP_EOL);
        fclose($file);
    }

}