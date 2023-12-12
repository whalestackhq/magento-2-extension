<?php
namespace Whalestack\PaymentGateway\Block;

class Payment extends \Magento\Sales\Block\Order\Info
{
    public function getWhalestackTxId()
    {
        return $this->getOrder()->getPayment()->getWhalestackTxId();
    }
}