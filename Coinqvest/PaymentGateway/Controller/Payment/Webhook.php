<?php

namespace Coinqvest\PaymentGateway\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Model\Order;
use Coinqvest\PaymentGateway\Api;

class Webhook extends Action
{
    private $checkoutSession;
    private $file;
    private $jsonResultFactory;
    private $scopeConfig;
    private $searchCriteriaBuilder;
    private $orderRepository;
    private $orderCollectionFactory;

    public function __construct(
        Context $context,
        Session $checkoutSession,
        File $file,
        JsonFactory $jsonResultFactory,
        ScopeConfigInterface $scopeConfig,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderRepositoryInterface $orderRepository,
        CollectionFactory $orderCollectionFactory
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->file = $file;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->scopeConfig = $scopeConfig;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderRepository = $orderRepository;
        $this->orderCollectionFactory = $orderCollectionFactory;

        $this->execute();
    }

    public function execute()
    {
        try {

            $payload = $this->file->read('php://input');

            /**
             * Get request headers and validate
             */

            $request_headers = array_change_key_case($this->get_request_headers(), CASE_UPPER);

            if (!$this->validate_webhook($request_headers, $payload))
            {
                $result = $this->jsonResultFactory->create();
                $result->setHttpResponseCode(401);
                $result->setData(['success' => false, 'message' => __('Webhook validation failed.')]);
                return $result;
            }

            $payload = json_decode($payload, true);

            /**
             * Webhook format validation - for old webhook format which is not used anymore
             */

            if (!isset($payload['eventType'])) {
                $result = $this->jsonResultFactory->create();
                $result->setHttpResponseCode(400);
                $result->setData(['success' => false, 'message' => __('Something went wrong.')]);
                exit;
            }

            /**
             * Find Magento order by Coinqvest checkout id
             */

            $checkoutId = (isset($payload['data']['checkout'])) ? $payload['data']['checkout']['id'] : $payload['data']['refund']['checkoutId'];

            $order = $this->getOrder($checkoutId);

            if (!$order)
            {
                $result = $this->jsonResultFactory->create();
                $result->setHttpResponseCode(400);
                $result->setData(['success' => false, 'message' => __('Could not find matching order.')]);
                return $result;
            }

            $this->updateOrderState($order, $payload);

            $result = $this->jsonResultFactory->create();
            $result->setHttpResponseCode(200);
            $result->setData(['success' => true]);

            return $result;

        } catch (\Exception $e) {

            $result = $this->jsonResultFactory->create();
            $result->setHttpResponseCode(400);
            $result->setData(['error_message' => __('Webhook receive error.')]);
            return $result;

        }

    }

    public function updateOrderState($order, $payload)
    {

        switch ($payload['eventType']) {

            case 'CHECKOUT_COMPLETED':

                if (!in_array($order->getStatus(), array('complete', 'processing'))) {
                    $checkout = $payload['data']['checkout'];
                    $paymentDetailsPage = 'https://www.coinqvest.com/en/payment/checkout-id/' . $checkout['id'];

                    $order->addStatusHistoryComment(__('Order PAID via COINQVEST. See payment details <a href="' .  $paymentDetailsPage. '" target="_blank">here</a>.'));
                    $order->setState(Order::STATE_PROCESSING);
                    $order->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING));
                }
                break;

            case 'CHECKOUT_UNDERPAID':

                if (!in_array($order->getStatus(), array('complete', 'processing'))) {
                    $checkout = $payload['data']['checkout'];
                    $paymentDetailsPage = 'https://www.coinqvest.com/en/unresolved-charge/checkout-id/' . $checkout['id'];

                    $order->addStatusHistoryComment(__('COINQVEST payment was underpaid by customer. See details and options to resolve it <a href="' .  $paymentDetailsPage. '" target="_blank">here</a>.'));
                    $order->setState(Order::STATE_HOLDED);
                    $order->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_HOLDED));
                }
                break;

            case 'UNDERPAID_ACCEPTED':

                if (!in_array($order->getStatus(), array('complete', 'processing'))) {
                    $checkout = $payload['data']['checkout'];
                    $paymentDetailsPage = 'https://www.coinqvest.com/en/payment/checkout-id/' . $checkout['id'];
                    $underpaid_accepted_price = $checkout['settlementAmountReceived'] . ' ' . $order->getOrderCurrencyCode();
                    $comment = sprintf('Underpaid by customer, but payment manually accepted at %1$s and completed. Find payment details <a href="%2$s" target="_blank">here</a>.', $underpaid_accepted_price, $paymentDetailsPage);

                    $order->addStatusHistoryComment($comment);
                    $order->setState(Order::STATE_PROCESSING);
                    $order->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING));
                }
                break;

            case 'REFUND_COMPLETED':

                $refund = $payload['data']['refund'];
                $context = $payload['data']['refund']['context'];

                if (in_array($context, array('COMPLETED_CHECKOUT', 'UNDERPAID_CHECKOUT'))) {

                    $paymentDetailsPage = 'https://www.coinqvest.com/en/refund/' . $refund['id'];
                    $comment = sprintf('Order amount was refunded successfully to customer. Find payment details <a href="%s" target="_blank">here</a>.', $paymentDetailsPage);

                    $order->addStatusHistoryComment($comment);
                    $order->setState(Order::STATE_CLOSED);
                    $order->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_CLOSED));
                }
                break;

            default:

                Api\CQLoggingService::write($payload, 'Unresolved payload event for order id ' . $order->getId());

        }

        $order->save();

    }


    /**
     * Gets the incoming request headers. Some servers are not using
     * Apache and "getallheaders()" will not work so we may need to
     * build our own headers.
     */
    public function get_request_headers()
    {
        if (!function_exists('getallheaders'))
        {
            $headers = array();
            foreach ($_SERVER as $name => $value ) {
                if ('HTTP_' === substr($name, 0, 5)) {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
            return $headers;
        } else {
            return getallheaders();
        }
    }


    /**
     * Validate the webhook request
     */
    private function validate_webhook($request_headers, $payload)
    {
        if (!isset($request_headers['X-WEBHOOK-AUTH'])) {
            return false;
        }

        $sig = $request_headers['X-WEBHOOK-AUTH'];

        $api_secret = $this->scopeConfig->getValue('payment/coinqvest_paymentgateway/api_secret');

        $sig2 = hash('sha256', $api_secret . $payload);

        if ($sig === $sig2) {
            return true;
        }

        return false;
    }


    /**
     * Get order by Coinqvest checkout id
     */
    private function getOrder($checkoutId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('coinqvest_checkout_id', $checkoutId, 'eq')->create();

        $orderList = $this->orderRepository->getList($searchCriteria)->getItems();

        $order = reset($orderList) ? reset($orderList) : null;

        return $order;
    }

}