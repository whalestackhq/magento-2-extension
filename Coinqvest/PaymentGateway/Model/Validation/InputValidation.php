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

        // check API auth settings
        $client = new Api\CQMerchantClient($apiKey, $apiSecret);
        if (!$this->isAuth($client)) {
            throw new \Magento\Framework\Exception\ValidatorException(__('COINQVEST - Api key and/or secret are wrong. Please double check.'));
        }

        $this->setValue($this->getValue());

        parent::beforeSave();
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