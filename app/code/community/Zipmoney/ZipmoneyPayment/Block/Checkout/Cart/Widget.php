<?php

class Zipmoney_ZipmoneyPayment_Block_Checkout_Cart_Widget extends Zipmoney_ZipmoneyPayment_Block_Checkout_Cart_Abstract {

    protected $_design      = 'general';
    protected $_position    = 'below';

    public function __construct() {
        parent::__construct();
        $this->_asset_type = Zipmoney_ZipmoneyPayment_Helper_Widget::WIDGET_ASSET_TYPE_CART;

        if ($this->_isZipMoneyPaymentActive()) {
            if ($this->_isShowExpressButton($this->_design, $this->_position)) {
                if ($this->_isAllowedToCheckout()) {
                    $this->setTemplate('zipmoney/zipmoneypayment/checkout/onepage/cart/expresspayment.phtml');
                } else {
                    $this->setTemplate('zipmoney/zipmoneypayment/widget/cart.phtml');
                }
            } else if ($this->_isWidgetCartActive()) {
                $this->setTemplate('zipmoney/zipmoneypayment/widget/cart.phtml');
            }
        }
    }

    public function isShow()
    {
        return $this->isShowBasedOnConditions($this->_design, $this->_position);
    }
}