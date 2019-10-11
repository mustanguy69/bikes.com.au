<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Cart
 */


class Amasty_Cart_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getTime()
    {
        return Mage::getStoreConfig('amcart/general/time');
    }

    public function useProductPage()
    {
        return Mage::getStoreConfig('amcart/general/use_product_page');
    }

    public function getProductButton()
    {
        return Mage::getStoreConfig('amcart/general/product_button');
    }

    public function getDisplayAlign()
    {
        return Mage::getStoreConfig('amcart/display/align');
    }

    public function displayProductImage()
    {
        return Mage::getStoreConfig('amcart/display/disp_image');
    }

    public function displayChangeQty()
    {
        return Mage::getStoreConfig('amcart/display/display_qty');
    }

    public function displayGoToCheckout()
    {
        return Mage::getStoreConfig('amcart/display/checkout');
    }

    public function displayCount()
    {
        return Mage::getStoreConfig('amcart/display/disp_count');
    }

    public function displaySumm()
    {
        return Mage::getStoreConfig('amcart/display/disp_sum');
    }

    public function getConfigurableName()
    {
        return Mage::getStoreConfig('amcart/configurable/name');
    }

    public function isRelatedBlockEnabled()
    {
        return Mage::getStoreConfig('amcart/selling/related');
    }

    public function getConfigurableImage()
    {
        return Mage::getStoreConfig('amcart/configurable/image');
    }

    public function jsParam($obj)
    {
        $param = array(
            'send_url' => $obj->getSendUrl(),
            'update_url' => $obj->getUpdateUrl(),
            'src_image_progress' => $obj->getSkinUrl('images/amasty/loading.gif'),
            'enable_minicart' => Mage::getStoreConfig('amcart/general/minicart'),
            'type_loading' => Mage::getStoreConfig('amcart/display/type_loading'),
            'error' => $this->__(' ↑ This is a required field.'),
            'align' => $this->getDisplayAlign(),
            'form_key' => Mage::getSingleton('core/session')->getFormKey(),
            'is_product_view' => Mage::registry('current_product') ? 1 : 0,
            'top_cart_selector' => Mage::getStoreConfig('amcart/reloading/selector'),
            'buttonClass' => Mage::getStoreConfig('amcart/general/button_selector'),
            'linkcompare' => (int)Mage::getStoreConfig('amcart/general/linkcompare'),
            'wishlist' => (int)Mage::getStoreConfig('amcart/general/wishlist')
        );

        if (Mage::registry('current_product')) {
            $param['product_id'] = Mage::registry('current_product')->getId();
        }

        if (Mage::registry('current_category')) {
            $param['current_category'] = Mage::registry('current_category')->getUrl();
        }

        return Zend_Json::encode($param);
    }

    public function getItemId($product)
    {
        $item = Mage::getSingleton('checkout/session')->getQuote()->getItemByProduct($product);
        if ($item) {
            return $item->getId();
        }

        return 0;
    }

    function getLeftButtonColor()
    {
        return "#" . Mage::getStoreConfig('amcart/visual/left_button');
    }

    function getRightButtonColor()
    {
        return "#" . Mage::getStoreConfig('amcart/visual/right_button');
    }

    function getBackgroundColor()
    {
        return "#" . Mage::getStoreConfig('amcart/visual/background_color');
    }

    function getHeaderBackgroundColor()
    {
        return "#" . Mage::getStoreConfig('amcart/visual/header_color');
    }

    function getTextColor()
    {
        return "#" . Mage::getStoreConfig('amcart/visual/text');
    }

    function getButtonTextColor()
    {
        return "#" . Mage::getStoreConfig('amcart/visual/button_text');
    }

    function colourBrightness($hex, $percent)
    {
        // Work out if hash given
        $hash = '';
        if (stristr($hex, '#')) {
            $hex = str_replace('#', '', $hex);
            $hash = '#';
        }
        /// HEX TO RGB
        $rgb = array(hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)));
        //// CALCULATE
        for ($i = 0; $i < 3; $i++) {
            // See if brighter or darker
            if ($percent > 0) {
                // Lighter
                $rgb[$i] = round($rgb[$i] * $percent) + round(255 * (1 - $percent));
            } else {
                // Darker
                $positivePercent = $percent - ($percent * 2);
                $rgb[$i] = round($rgb[$i] * $positivePercent) + round(0 * (1 - $positivePercent));
            }
            // In case rounding up causes us to go to 256
            if ($rgb[$i] > 255) {
                $rgb[$i] = 255;
            }
        }
        //// RBG to Hex
        $hex = '';
        for ($i = 0; $i < 3; $i++) {
            // Convert the decimal digit to hex
            $hexDigit = dechex($rgb[$i]);
            // Add a leading zero if necessary
            if (strlen($hexDigit) == 1) {
                $hexDigit = "0" . $hexDigit;
            }
            // Append to the hex string
            $hex .= $hexDigit;
        }
        return $hash . $hex;
    }

    /**
     * @return string
     */
    public function getCartCount()
    {
        $summaryQty = Mage::getSingleton('checkout/cart')->getSummaryQty();

        $text = ((int) $summaryQty === 1) ? $this->__('There is') : $this->__('There are');
        $text .= ' <a href="' . $this->_getUrl('checkout/cart')
            . '" id="am-a-count" title="' . $this->__("View cart") . '" class="link">'
            . $summaryQty;

        $text .= ((int) $summaryQty === 1) ? $this->__(' item') : $this->__(' items');

        $text .= '</a> ' . $this->__('in your cart.');

        return $text;
    }

    /**
     * @return string
     */
    public function getSubtotalText()
    {
        $cart = Mage::getSingleton('checkout/cart');
        $text = Mage::helper('checkout')->formatPrice($this->getSubtotal($cart));

        if ($subtotalInclTax = $this->getSubtotalInclTax($cart)) {
            $text .= '<br />(' . Mage::helper('checkout')->formatPrice($subtotalInclTax)
                . ' ' . Mage::helper('tax')->getIncExcText(true) . ')';
        }

        return $text;
    }

    /**
     * @param $cart
     * @param bool $skipTax
     * @return int
     */
    protected function getSubtotal($cart, $skipTax = true)
    {
        $subtotal = 0;
        $totals = $cart->getQuote()->getTotals();
        $config = Mage::getSingleton('tax/config');
        if (isset($totals['subtotal'])) {
            if ($config->displayCartSubtotalBoth()) {
                if ($skipTax) {
                    $subtotal = $totals['subtotal']->getValueExclTax();
                } else {
                    $subtotal = $totals['subtotal']->getValueInclTax();
                }
            } elseif ($config->displayCartSubtotalInclTax()) {
                $subtotal = $totals['subtotal']->getValueInclTax();
            } else {
                $subtotal = $totals['subtotal']->getValue();
                if (!$skipTax && isset($totals['tax'])) {
                    $subtotal += $totals['tax']->getValue();
                }
            }
        }
        return $subtotal;
    }

    /**
     * @param $cart
     * @return int
     */
    protected function getSubtotalInclTax($cart)
    {
        if (!Mage::getSingleton('tax/config')->displayCartSubtotalBoth()) {
            return 0;
        }

        return $this->getSubtotal($cart, false);
    }
}
