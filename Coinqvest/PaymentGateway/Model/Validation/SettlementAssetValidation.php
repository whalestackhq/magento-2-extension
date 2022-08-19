<?php

namespace Coinqvest\PaymentGateway\Model\Validation;

use Magento\Store\Model\ScopeInterface;

class SettlementAssetValidation extends \Magento\Framework\App\Config\Value
{

    public function beforeSave()
    {
        // Submitted form field
        $settlementAsset = $this->getData()['groups']['coinqvest_paymentgateway']['fields']['settlement_currency']['value'];

        if ($settlementAsset == "0") {
            throw new \Magento\Framework\Exception\ValidatorException(__('COINQVEST - Please choose a settlement asset.'));
        }

        $this->setValue($this->getValue());

        parent::beforeSave();
    }

}