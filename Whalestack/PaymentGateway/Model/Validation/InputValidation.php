<?php

namespace Whalestack\PaymentGateway\Model\Validation;

use Magento\Store\Model\ScopeInterface;
use Whalestack\PaymentGateway\Api;

class InputValidation extends \Magento\Framework\App\Config\Value
{

    public function beforeSave()
    {
        // Submitted form fields:
        $whalestackData = $this->getData()['groups']['whalestack_paymentgateway']['fields'];

        $apiKey = $whalestackData['api_key']['value'];
        $apiSecret = $whalestackData['api_secret']['value'];

        // check API auth settings
        $client = new Api\WsMerchantClient($apiKey, $apiSecret);
        if (!$this->isAuth($client)) {
            throw new \Magento\Framework\Exception\ValidatorException(__('Whalestack - Api key and/or secret are wrong. Please double check.'));
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