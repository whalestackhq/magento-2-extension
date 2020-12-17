<?php

namespace Coinqvest\PaymentGateway\Model\Validation;

class InputValidation extends \Magento\Framework\App\Config\Value
{
    public function beforeSave()
    {
        $field_id = $this->getData('field_config/id');
        $label = $this->getData('field_config/label');

        if ($field_id == 'api_key')
        {
            if ($this->getValue() != '' && strlen($this->getValue()) != 12)
            {
                throw new \Magento\Framework\Exception\ValidatorException(__('COINQVEST ' . $label . ' seems to be wrong. Please double check.'));
            }
        }

        if ($field_id == 'api_secret')
        {
            if ($this->getValue() != '' && strlen($this->getValue()) != 29)
            {
                throw new \Magento\Framework\Exception\ValidatorException(__('COINQVEST ' . $label . ' seems to be wrong. Please double check.'));
            }
        }

        $this->setValue($this->getValue());

        parent::beforeSave();
    }
}