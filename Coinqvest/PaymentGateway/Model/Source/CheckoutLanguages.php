<?php

namespace Coinqvest\PaymentGateway\Model\Source;

use Magento\Store\Model\ScopeInterface;
use Coinqvest\PaymentGateway\Api;

class CheckoutLanguages
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;
    protected $apiKey;
    protected $apiSecret;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;

        $this->apiKey = $this->scopeConfig->getValue('payment/coinqvest_paymentgateway/api_key', ScopeInterface::SCOPE_STORE);
        $this->apiSecret = $this->scopeConfig->getValue('payment/coinqvest_paymentgateway/api_secret', ScopeInterface::SCOPE_STORE);
    }

    public function toOptionArray()
    {
        return $this->getCheckoutLanguages();
    }

    public function getCheckoutLanguages()
    {
        $languages = array(
            array('value' => '0', 'label' => 'Please select...'),
            array('value' => 'auto', 'label' => 'auto - Automatic')
        );

        if (!empty($this->apiKey) && !empty($this->apiSecret))
        {
            $client = new Api\CQMerchantClient($this->apiKey, $this->apiSecret);

            $response = $client->get('/languages');
            $langs = json_decode($response->responseBody);

            if ($response->httpStatusCode == 200)
            {
                foreach ($langs->languages as $lang)
                {
                    array_push(
                        $languages,
                        array('value' => $lang->languageCode, 'label' => $lang->languageCode . ' - ' . $lang->name)
                    );
                }
            }
        }
        return $languages;
    }

}
