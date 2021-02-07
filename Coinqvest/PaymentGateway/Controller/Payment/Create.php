<?php

namespace Coinqvest\PaymentGateway\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use Coinqvest\PaymentGateway\Api;

class Create extends Action
{
    private $checkoutSession;
    private $resultJsonFactory;
    private $logger;
    private $scopeConfig;
    protected $urlBuilder;

    public function __construct(
        Context $context,
        Session $checkoutSession,
        JsonFactory $resultJsonFactory,
        UrlInterface $urlBuilder,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->urlBuilder = $urlBuilder;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    public function execute()
    {
        $order = $this->getOrder();

        /**
         * Init the COINQVEST API
         */

        $apiKey = $this->scopeConfig->getValue('payment/coinqvest_paymentgateway/api_key', ScopeInterface::SCOPE_STORE);
        $apiSecret = $this->scopeConfig->getValue('payment/coinqvest_paymentgateway/api_secret', ScopeInterface::SCOPE_STORE);

        $client = new Api\CQMerchantClient(
            $apiKey,
            $apiSecret
        );

        /**
         * Create a customer first
         */

        $billingAddress = $order->getBillingAddress()->getData();

        $customer = array(
            'email' => $billingAddress['email'],
            'firstname' => $billingAddress['firstname'],
            'lastname' => $billingAddress['lastname'],
            'company' => $billingAddress['company'],
            'adr1' => $billingAddress['street'],
            'zip' => $billingAddress['postcode'],
            'city' => $billingAddress['city'],
            'countrycode' => $billingAddress['country_id'],
            'phonenumber' => $billingAddress['telephone'],
            'meta' => array(
                'source' => 'Magento',
                'customerId' => $order->getCustomerId()
            )
        );

        $response = $client->post('/customer', array('customer' => $customer));

        if ($response->httpStatusCode != 200)
        {
            Api\CQLoggingService::write($response->responseBody, 'COINQVEST customer could not be created');
            $this->logger->critical('COINQVEST customer could not be created', ['exception' => $response->responseBody]);
            throw new LocalizedException(__($response->responseBody));
        }

        $data = json_decode($response->responseBody, true);
        $customerId = $data['customerId']; // use this to associate a checkout with this customer

        /**
         * Build the checkout object
         */

        $displayMethod = $this->scopeConfig->getValue('payment/coinqvest_paymentgateway/price_display_method', ScopeInterface::SCOPE_STORE);
        $settlementCurrency = $this->scopeConfig->getValue('payment/coinqvest_paymentgateway/settlement_currency', ScopeInterface::SCOPE_STORE);
        $checkoutLanguage = $this->scopeConfig->getValue('payment/coinqvest_paymentgateway/checkout_language', ScopeInterface::SCOPE_STORE);

        if ($displayMethod == 'simple') {

            $checkout = $this->buildSimpleCheckoutObject($order);

        } else {

            $checkout = $this->buildDetailedCheckoutObject($order);
        }

        $checkout['settlementCurrency'] = ($settlementCurrency == '0' || is_null($settlementCurrency)) ? null : $settlementCurrency;
        $checkout['checkoutLanguage'] = ($checkoutLanguage == '0' || is_null($checkoutLanguage)) ? null : $checkoutLanguage;
        $checkout['webhook'] = $this->urlBuilder->getUrl('coinqvest/payment/webhook');
        $checkout['links']['cancelUrl'] = $this->urlBuilder->getUrl('coinqvest/payment/cancel', ['order_id' => $order->getId()]);
        $checkout['links']['returnUrl'] = $this->urlBuilder->getUrl('coinqvest/payment/success');
        $checkout['charge']['customerId'] = $customerId;

        $response = $client->post('/checkout/hosted', $checkout);

        if ($response->httpStatusCode != 200)
        {
            Api\CQLoggingService::write($response->responseBody, 'COINQVEST checkout failed');
            $this->logger->critical('COINQVEST checkout failed', ['exception' => $response->responseBody]);
            throw new LocalizedException(__($response->responseBody));
        }

        $data = json_decode($response->responseBody, true);

        /**
         * Update order with Coinqvest Checkout Id
         */

        $order->setCoinqvestCheckoutId($data['id']);
        $order->save();


        /**
         * The checkout was created, redirect user to hosted checkout page
         */

        $url = $data['url'];
        $result = $this->resultJsonFactory->create();
        return $result->setData(['redirectUrl' => $url]);

    }



    private function getOrder()
    {
        return $this->checkoutSession->getLastRealOrder();
    }

    private function buildSimpleCheckoutObject($order)
    {
        $checkout['charge'] = array(
            "currency" => $order->getOrderCurrencyCode(),
            "lineItems" => array(
                array(
                    "description" => "Magento Order #" . $order->getIncrementId(),
                    "netAmount" => $order->getGrandTotal()
                )
            )
        );

        return $checkout;
    }

    private function buildDetailedCheckoutObject($order)
    {
        $lineItems = array();
        $shippingCostItems = array();
        $taxItems = array();
        $discountItems = array();

        /**
         * Line items
         */

        foreach ($order->getAllVisibleItems() as $item)
        {
            $lineItem = array(
                "description" => $item->getName(),
                "netAmount" => $item->getBasePrice(),
                "quantity" => (int)$item->getQtyOrdered(),
                "productId" => $item->getProductId()
            );
            array_push($lineItems, $lineItem);
        }

        /**
         * Discount items
         */

        if ($order->getDiscountAmount() != "0")
        {
            $discountItem = array(
                "description" => $order->getDiscountDescription(),
                "netAmount" => abs($order->getDiscountAmount())
            );
            array_push($discountItems, $discountItem);
        }

        /**
         * Tax items
         */

        $taxItem = array(
            "name" => "Tax",
            "percent" => $order->getAllVisibleItems()[0]->getTaxPercent() / 100
        );
        array_push($taxItems, $taxItem);

        /**
         * Shipping cost items
         */

        $shippingCostItem = array(
            "description" => $order->getShippingDescription(),
            "netAmount" => $order->getShippingAmount(),
            "taxable" => $order->getShippingTaxAmount() > 0 ? true : false
        );
        array_push($shippingCostItems, $shippingCostItem);

        /**
         * Put it all together
         */

        $checkout['charge'] = array(
            "currency" => $order->getOrderCurrencyCode(),
            "lineItems" => $lineItems,
            "discountItems" => !empty($discountItems) ? $discountItems : null,
            "shippingCostItems" => !empty($shippingCostItems) ? $shippingCostItems : null,
            "taxItems" => !empty($taxItems) ? $taxItems : null
        );

        return $checkout;

    }
}
