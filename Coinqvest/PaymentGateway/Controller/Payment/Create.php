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
use Coinqvest\PaymentGateway\Helper\Data;

class Create extends Action
{
    private $checkoutSession;
    private $resultJsonFactory;
    private $logger;
    private $scopeConfig;
    protected $urlBuilder;
    protected $apiKey;
    protected $apiSecret;

    public function __construct(
        Context $context,
        Session $checkoutSession,
        JsonFactory $resultJsonFactory,
        UrlInterface $urlBuilder,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        Data $helper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->urlBuilder = $urlBuilder;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->apiKey = $this->scopeConfig->getValue('payment/coinqvest_paymentgateway/api_key', ScopeInterface::SCOPE_STORE);
        $this->apiSecret = $this->scopeConfig->getValue('payment/coinqvest_paymentgateway/api_secret', ScopeInterface::SCOPE_STORE);
        parent::__construct($context);
    }


    public function execute()
    {
        $order = $this->getOrder();

        /**
         * Init the COINQVEST API
         */

        $client = new Api\CQMerchantClient($this->apiKey, $this->apiSecret);

        /**
         * Create a customer first
         */

        $billingAddress = $order->getBillingAddress()->getData();

        $customer = array(
            'email' => $billingAddress['email'],
            'firstname' => !empty($billingAddress['firstname']) ? $billingAddress['firstname'] : null,
            'lastname' => !empty($billingAddress['lastname']) ? $billingAddress['lastname'] : null,
            'company' => !empty($billingAddress['company']) ? $billingAddress['company'] : null,
            'adr1' => !empty($billingAddress['street']) ? $billingAddress['street'] : null,
            'zip' => !empty($billingAddress['postcode']) ? $billingAddress['postcode'] : null,
            'city' => !empty($billingAddress['city']) ? $billingAddress['city'] : null,
            'countrycode' => !empty($billingAddress['country_id']) ? $billingAddress['country_id'] : null,
            'phonenumber' => !empty($billingAddress['telephone']) ? $billingAddress['telephone'] : null,
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
         * Check if order currency is a supported fiat or blockchain currency
         * If not, the settlement currency will then be used as the new billing currency
         */

        $billingCurrency = $order->getOrderCurrencyCode();
        $displayMethod = $this->scopeConfig->getValue('payment/coinqvest_paymentgateway/price_display_method', ScopeInterface::SCOPE_STORE);
        $settlementAsset = $this->scopeConfig->getValue('payment/coinqvest_paymentgateway/settlement_currency', ScopeInterface::SCOPE_STORE);
        $checkoutLanguage = $this->scopeConfig->getValue('payment/coinqvest_paymentgateway/checkout_language', ScopeInterface::SCOPE_STORE);

        /**
         * Build the checkout object
         */

        if ($displayMethod == 'simple') {

            $checkout = $this->buildSimpleCheckoutObject($order, $billingCurrency);

        } else {

            $checkout = $this->buildDetailedCheckoutObject($order, $billingCurrency);

            /**
             * Validate the checkout object
             * If Magento grand order total does not match CQs charge total, use simple checkout object
             * This might happen due to Magento's numerous tax settings
             */

            $response = $client->post('/checkout/validate-checkout-charge', $checkout);
            if ($response->httpStatusCode != 200)
            {
                Api\CQLoggingService::write($response->responseBody, 'COINQVEST checkout charge could not be validated.');
                $this->logger->critical('COINQVEST checkout charge could not be validated', ['exception' => $response->responseBody]);
                throw new LocalizedException(__($response->responseBody));
            }

            $data = json_decode($response->responseBody, true);

            // strip off trailing zeros from $order->getGrandTotal() (which has 4 decimals by default)
            $grandOrderTotal = (float)$order->getGrandTotal();
            // count decimals
            $decimals = (int) strpos(strrev($grandOrderTotal), ".");

            if ($grandOrderTotal != round($data['total'], $decimals)) {
                $checkout = $this->buildSimpleCheckoutObject($order, $billingCurrency);
            }
        }


        $checkout['settlementAsset'] = ($settlementAsset == '0' || is_null($settlementAsset)) ? null : $settlementAsset;
        $checkout['checkoutLanguage'] = ($checkoutLanguage == '0' || is_null($checkoutLanguage)) ? null : $checkoutLanguage;
        $checkout['webhook'] = $this->urlBuilder->getUrl('coinqvest/payment/webhook');
        $checkout['pageSettings']['cancelUrl'] = $this->urlBuilder->getUrl('coinqvest/payment/cancel', ['order_id' => $order->getId()]);
        $checkout['pageSettings']['returnUrl'] = $this->urlBuilder->getUrl('coinqvest/payment/success');
        $checkout['charge']['customerId'] = $customerId;

        /**
         * Send the checkout
         */

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

    private function buildSimpleCheckoutObject($order, $quoteCurrency)
    {
        $checkout['charge'] = array(
            "billingCurrency" => $quoteCurrency,
            "lineItems" => array(
                array(
                    "description" => "Order No. " . $order->getIncrementId(),
                    "netAmount" => $order->getGrandTotal()
                )
            )
        );

        return $checkout;
    }

    private function buildDetailedCheckoutObject($order, $quoteCurrency)
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
         *
         */

        if ($order->getDiscountAmount() != "0")
        {
            // $order->getDiscountAmount() includes tax, but $netAmount must be a net value
            $taxRate = $order->getAllVisibleItems()[0]->getTaxPercent();
            $discountAmount = abs($order->getDiscountAmount());

            $netAmount = (isset($taxRate) && $taxRate > 0) ? $discountAmount / (1 + ( $taxRate / 100)) : $discountAmount;

            $discountItem = array(
                "description" => $order->getDiscountDescription(),
                "netAmount" => $netAmount
            );
            array_push($discountItems, $discountItem);
        }

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
         * Tax items
         */

        $taxItem = array(
            "name" => "Tax",
            "percent" => $order->getAllVisibleItems()[0]->getTaxPercent() / 100
        );
        array_push($taxItems, $taxItem);

        /**
         * Put it all together
         */

        $checkout['charge'] = array(
            "billingCurrency" => $quoteCurrency,
            "lineItems" => $lineItems,
            "discountItems" => !empty($discountItems) ? $discountItems : null,
            "shippingCostItems" => !empty($shippingCostItems) ? $shippingCostItems : null,
            "taxItems" => !empty($taxItems) ? $taxItems : null
        );

        return $checkout;

    }

}
