<?php

namespace Whalestack\PaymentGateway\Model\Validation;

use Magento\Store\Model\ScopeInterface;

class SettlementAssetValidation extends \Magento\Framework\App\Config\Value
{

    public function beforeSave()
    {
        // Submitted form field
        $settlementAsset = $this->getData()['groups']['whalestack_paymentgateway']['fields']['settlement_currency']['value'];

        if ($settlementAsset == "0") {
            throw new \Magento\Framework\Exception\ValidatorException(__('Whalestack - Please choose a settlement asset.'));
        }

        $this->setValue($this->getValue());

        parent::beforeSave();
    }

}