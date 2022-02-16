<?php

namespace Coinqvest\PaymentGateway\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Coinqvest\PaymentGateway\Helper\Data;

class Disable extends \Magento\Config\Block\System\Config\Form\Field
{
    private $scopeConfig;
    protected $apiKey;
    protected $apiSecret;

    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        Data $helper,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->apiKey = $this->scopeConfig->getValue('payment/coinqvest_paymentgateway/api_key', ScopeInterface::SCOPE_STORE);
        $this->apiSecret = $this->scopeConfig->getValue('payment/coinqvest_paymentgateway/api_secret', ScopeInterface::SCOPE_STORE);
        parent::__construct($context, $data);
    }



//    protected function _getElementHtml(AbstractElement $element)
//    {
//
////        if(1 == 1){
//            $element->setDisabled('disabled');
////            $element->setStyle('display:none');
////            $element->addClass('hide');
////        }
//
////        $element->setDisabled('disabled');
//        return $element->getElementHtml();
//
////        $html = null;
////
////        return $this->_decorateRowHtml($element, $html);
//
//
//    }


    protected function _decorateRowHtml($element, $html)
    {

        $style = ' style="display: none"';

        if($this->helper->isCustomStoreCurrency($this->apiKey, $this->apiSecret)){
            $style = null;
        }

        return '<tr id="row_' . $element->getHtmlId() . '"' . $style .'>' . $html . '</tr>';
    }


}