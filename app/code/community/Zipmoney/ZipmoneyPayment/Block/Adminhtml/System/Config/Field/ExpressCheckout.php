<?php
/**
 * @category  Aligent
 * @package   zipmoney
 * @author    Andi Han <andi@aligent.com.au>
 * @copyright 2014 Aligent Consulting.
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.aligent.com.au/
 */

class Zipmoney_ZipmoneyPayment_Block_Adminhtml_System_Config_Field_ExpressCheckout extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected $_expressCheckoutEnabled  = null;
    protected $_expressCartEnabled      = null;
    protected $_expressProductEnabled   = null;

    protected function _construct()
    {
        parent::_construct();

        $this->_getExpressCheckoutEnabled();
        $this->_getExpressCartEnabled();
        $this->_getExpressProductEnabled();
    }

    protected function _getExpressCheckoutEnabled()
    {
        if ($this->_expressCheckoutEnabled === null) {
            $vPath = Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_EXPRESS_CHECKOUT_EXPRESS_CHECKOUT_ACTIVE;
            $this->_expressCheckoutEnabled = Mage::getSingleton('adminhtml/config_data')->getConfigDataValue($vPath);
        }
        return $this->_expressCheckoutEnabled;
    }

    protected function _getExpressCartEnabled()
    {
        if ($this->_expressCartEnabled === null) {
            $vPath = Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_EXPRESS_CHECKOUT_CART_EXPRESS_BUTTON_ACTIVE;
            $this->_expressCartEnabled = Mage::getSingleton('adminhtml/config_data')->getConfigDataValue($vPath);
        }
        return $this->_expressCartEnabled;
    }

    protected function _getExpressProductEnabled()
    {
        if ($this->_expressProductEnabled === null) {
            $vPath = Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_EXPRESS_CHECKOUT_PRODUCT_EXPRESS_BUTTON_ACTIVE;
            $this->_expressProductEnabled = Mage::getSingleton('adminhtml/config_data')->getConfigDataValue($vPath);
        }
        return $this->_expressProductEnabled;
    }

    /**
     * Override Mage_Adminhtml_Block_System_Config_Form_Field::_getElementHtml
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $vJsString = '';
        $vFunctionBody = '';
        $vInitFunctionBody = '';
        $vElementId = $element->getHtmlId();
        switch ($vElementId) {
            case 'payment_zipmoney_express_checkout_express_checkout_active':
                $vFunctionBody = 'expressWidgetController.onExpressEnabledChange(this.value)';
                $vInitFunctionBody = $this->__getInitConfigJs();
                break;
            case 'payment_zipmoney_express_checkout_cart_express_button_active':
                $vFunctionBody = 'expressWidgetController.onExpressCartChange(this.value)';
                break;
            case 'payment_zipmoney_express_checkout_product_express_button_active':
                $vFunctionBody = 'expressWidgetController.onExpressProductChange(this.value)';
                break;
            default:
                // should never go to here
                break;
        }

        if ($vFunctionBody) {
            $vJsString = '
                $("' . $vElementId . '").observe("change", function () {
                    ' . $vFunctionBody . '
                });
            ';
        }

        if ($vInitFunctionBody) {
            $vJsString = $vJsString . $vInitFunctionBody;
        }
        if ($vJsString) {
            $vHtml = parent::_getElementHtml($element) . $this->helper('adminhtml/js')
                    ->getScript('document.observe("dom:loaded", function() {' . $vJsString . '});');
        } else {
            $vHtml = parent::_getElementHtml($element);
        }

        return $vHtml;
    }

    /**
     * Get Js used to initialise the fields based
     *
     * @return string
     */
    private function __getInitConfigJs()
    {
        $vInitFunctionBody = 'expressWidgetController.initExpressButtonConfig(' . $this->_getExpressCheckoutEnabled() . ');';
        $vInitFunctionBody = $vInitFunctionBody
            . 'expressWidgetController.initWidgetCartConfig('
            . $this->_getExpressCheckoutEnabled()
            . ', '
            . $this->_getExpressCartEnabled()
            . ');';
        $vInitFunctionBody = $vInitFunctionBody
            . 'expressWidgetController.initWidgetProductConfig('
            . $this->_getExpressCheckoutEnabled()
            . ', '
            . $this->_getExpressProductEnabled()
            . ');';
        return $vInitFunctionBody;
    }
}