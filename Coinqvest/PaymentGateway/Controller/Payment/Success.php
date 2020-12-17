<?php

namespace Coinqvest\PaymentGateway\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\OrderFactory;

class Success extends Action
{
    private $checkoutSession;
    private $orderFactory;
    private $resultPageFactory;

    public function __construct(
        Context $context,
        Session $checkoutSession,
        PageFactory $resultPageFactory,
        OrderFactory $orderFactory
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        if ($this->checkoutSession->getLastRealOrderId())
        {
            $this->_redirect('checkout/onepage/success', ['_secure' => true]);
        }
    }
}


