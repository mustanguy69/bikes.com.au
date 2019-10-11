<?php

class Zipmoney_ZipmoneyPayment_Block_Standard_Form extends Mage_Payment_Block_Form {

    protected $_methodCode = 'zipmoneypayment';
    public $assets_object;
    protected $_config;

    protected function _construct() {
        $this->assets_object = new Varien_Object();
        if(Mage::getStoreConfig('payment/zipmoney_merchant_info/popupurl')){
            $linkpopup = Mage::getStoreConfig('payment/zipmoney_merchant_info/popupurl');
        }
        else{
            $linkpopup = 'https://d3k1w8lx8mqizo.cloudfront.net/Integration/popup/popup.html';
        }

        $message = '';
        $min = Mage::getStoreConfig('payment/zipmoney_checkout/minimum_total');
        $max = Mage::getStoreConfig('payment/zipmoney_checkout/maximum_total');
        $showmessage = Mage::getStoreConfig('payment/zipmoney_checkout/display_message');
        $message_notice = Mage::getStoreConfig('payment/zipmoney_checkout/message');
        $total = Mage::getModel('checkout/cart')->getQuote()->getGrandTotal();



        if (Mage::getStoreConfig('payment/zipmoney_checkout/displaydetail')) {
            $message_ = Mage::helper('zipmoneypayment')->__(Mage::getStoreConfig('payment/zipmoney_checkout/detailmessage'));
            $message .= '<b>' . $message_ . ' </b><a target="_blank" href="/zipmoney" id="zipmoney-learn-more" class="zip-hover"  zm-widget="popup"  zm-popup-asset="checkoutdialog">';
            $message .= Mage::helper('zipmoneypayment')->__('Learn more...');
            $message .= '</a><script>if(window.$zmJs!=undefined) window.$zmJs._collectWidgetsEl(window.$zmJs);</script>';
        }
        if (($total < $min || $total > $max) && $showmessage == '1') {
            $message.='<span style="color: red; display: block">' . $message_notice . "</span>";
        }
        $mark = Mage::getConfig()->getBlockClassName('core/template');
        $mark = new $mark;
        $mark->setTemplate('zipmoney/zipmoneypayment/mark.phtml');
        $this->setTemplate('zipmoney/zipmoneypayment/redirect.phtml')
                ->setRedirectMessage($message);

        return parent::_construct();
    }

    public function getMethodCode() {
        return $this->_methodCode;
    }

}
