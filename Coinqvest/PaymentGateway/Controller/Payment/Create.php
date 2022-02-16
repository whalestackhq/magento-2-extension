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

        $quoteCurrency = $order->getOrderCurrencyCode();
        $exchangeRate = null;

        if ($this->helper->isCustomOrderCurrency($this->apiKey, $this->apiSecret, $quoteCurrency)) {

            $settlementCurrency = $this->scopeConfig->getValue('payment/coinqvest_paymentgateway/settlement_currency', ScopeInterface::SCOPE_STORE);

            if ($settlementCurrency == '0' || is_null($settlementCurrency)) {
                Api\CQLoggingService::write($response->responseBody, 'Please define a settlement currency in the COINQVEST payment extension.');
                $this->logger->critical('Please define a settlement currency in the COINQVEST payment extension.', ['exception' => $response->responseBody]);
                throw new LocalizedException(__($response->responseBody));
            }


            /**
             * Get the exchange rate between billing and settlement currency
             */

            $pair = array(
                'quoteCurrency' => $quoteCurrency,
                'baseCurrency' => $settlementCurrency
            );

            // override if settlement option is ORIGIN
            $displayCurrency = $this->scopeConfig->getValue('payment/coinqvest_paymentgateway/checkout_display_currency', ScopeInterface::SCOPE_STORE);

            if ($settlementCurrency == 'ORIGIN') {
                $pair = array(
                    'quoteCurrency' => $quoteCurrency,
                    'baseCurrency' => $displayCurrency
                );
            }

            $response = $client->get('/exchange-rate-global', $pair);
            if ($response->httpStatusCode != 200) {
                Api\CQLoggingService::write($response->responseBody, 'Exchange rate not available. Please try again');
                $this->logger->critical('Exchange rate not available. Please try again', ['exception' => $response->responseBody]);
                throw new LocalizedException(__($response->responseBody));
            }

            $response = json_decode($response->responseBody);
            $exchangeRate = $response->exchangeRate;

            if ($exchangeRate == null || $exchangeRate == 0) {
                Api\CQLoggingService::write($response->responseBody, 'Conversion problem. Please contact the vendor.');
                $this->logger->critical('Conversion problem. Please contact the vendor.', ['exception' => $response->responseBody]);
                throw new LocalizedException(__($response->responseBody));
            }

            // set the new billing currency accordingly
            $quoteCurrency = ($settlementCurrency == 'ORIGIN') ? $displayCurrency : $settlementCurrency;

        }

        /**
         * Build the checkout object
         */

        $displayMethod = $this->scopeConfig->getValue('payment/coinqvest_paymentgateway/price_display_method', ScopeInterface::SCOPE_STORE);
        $settlementCurrency = $this->scopeConfig->getValue('payment/coinqvest_paymentgateway/settlement_currency', ScopeInterface::SCOPE_STORE);
        $checkoutLanguage = $this->scopeConfig->getValue('payment/coinqvest_paymentgateway/checkout_language', ScopeInterface::SCOPE_STORE);

        if ($displayMethod == 'simple') {

            $checkout = $this->buildSimpleCheckoutObject($order, $quoteCurrency);

        } else {

            $checkout = $this->buildDetailedCheckoutObject($order, $quoteCurrency);

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

            if ($order->getGrandTotal() != $data['total']) {
                $checkout = $this->buildSimpleCheckoutObject($order, $quoteCurrency);
            }

        }


        $checkout['settlementCurrency'] = ($settlementCurrency == '0' || is_null($settlementCurrency)) ? null : $settlementCurrency;
        $checkout['checkoutLanguage'] = ($checkoutLanguage == '0' || is_null($checkoutLanguage)) ? null : $checkoutLanguage;
        $checkout['webhook'] = $this->urlBuilder->getUrl('coinqvest/payment/webhook');
        $checkout['links']['cancelUrl'] = $this->urlBuilder->getUrl('coinqvest/payment/cancel', ['order_id' => $order->getId()]);
        $checkout['links']['returnUrl'] = $this->urlBuilder->getUrl('coinqvest/payment/success');
        $checkout['charge']['customerId'] = $customerId;


        /**
         * Override the charge object with new exchange rate values
         * Add a charge item that describes the use of the currency exchange rate
         */

        if (!is_null($exchangeRate)) {

            $checkout = $this->overrideCheckoutValues($checkout, $exchangeRate, $displayCurrency);

            $newLineItem = array(
                'description' => sprintf(__('Exchange Rate 1 %1s = %2s %3s'), $order->getOrderCurrencyCode(), $this->numberFormat(1/$exchangeRate, 7), $quoteCurrency),
                'netAmount' => 0
            );
            if (isset($checkout['charge']['shippingCostItems'])) {
                array_push($checkout['charge']['shippingCostItems'], $newLineItem);
            } else {
                $checkout['charge']['shippingCostItems'][] = $newLineItem;
            }

        }

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
            "currency" => $quoteCurrency,
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
            "currency" => $quoteCurrency,
            "lineItems" => $lineItems,
            "discountItems" => !empty($discountItems) ? $discountItems : null,
            "shippingCostItems" => !empty($shippingCostItems) ? $shippingCostItems : null,
            "taxItems" => !empty($taxItems) ? $taxItems : null
        );

        return $checkout;

    }

    private function overrideCheckoutValues($checkout, $exchangeRate, $displayCurrency) {

        $checkout['charge']['currency'] = ($checkout['settlementCurrency'] == 'ORIGIN') ? $displayCurrency : $checkout['settlementCurrency'];

        if (isset($checkout['charge']['lineItems']) && !empty($checkout['charge']['lineItems'])) {
            foreach ($checkout['charge']['lineItems'] as $key => $item) {
                $checkout['charge']['lineItems'][$key]['netAmount'] = $this->numberFormat($item['netAmount'] / $exchangeRate, 7);
            }
        }
        if (isset($checkout['charge']['discountItems']) && !empty($checkout['charge']['discountItems'])) {
            foreach ($checkout['charge']['discountItems'] as $key => $item) {
                $checkout['charge']['discountItems'][$key]['netAmount'] = $this->numberFormat($item['netAmount'] / $exchangeRate, 7);
            }
        }
        if (isset($checkout['charge']['shippingCostItems']) && !empty($checkout['charge']['shippingCostItems'])) {
            foreach ($checkout['charge']['shippingCostItems'] as $key => $item) {
                $checkout['charge']['shippingCostItems'][$key]['netAmount'] = $this->numberFormat($item['netAmount'] / $exchangeRate, 7);
            }
        }

        return $checkout;

    }

    private function numberFormat($number, $decimals) {
        return number_format($number, $decimals, '.', '');
    }


}
