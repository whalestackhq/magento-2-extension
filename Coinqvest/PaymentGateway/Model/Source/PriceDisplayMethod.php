<?php

namespace Coinqvest\PaymentGateway\Model\Source;

class PriceDisplayMethod
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'detailed', 'label' => 'Detailed - All items, discounts, tax, shipping cost'),
            array('value' => 'simple', 'label' => 'Simple - Grand order total only')
        );
    }
}
