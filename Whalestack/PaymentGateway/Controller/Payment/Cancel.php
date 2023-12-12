<?php

namespace Whalestack\PaymentGateway\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;

class Cancel extends Action
{
    private $checkoutSession;

    public function __construct(
        Context $context,
        Session $checkoutSession
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
    }

    public function execute()
    {
        $order = $this->checkoutSession->getLastRealOrder();

        if ($order->getId() && ! $order->isCanceled()) {
            $order->registerCancellation('Canceled by Customer')->save();
        }

        $this->checkoutSession->restoreQuote();
        $this->_redirect('checkout/cart');
    }
}