<?php
namespace Coinqvest\PaymentGateway\Block;

class Payment extends \Magento\Sales\Block\Order\Info
{
    public function getCoinqvestTxId()
    {
        return $this->getOrder()->getPayment()->getCoinqvestTxId();
    }
}