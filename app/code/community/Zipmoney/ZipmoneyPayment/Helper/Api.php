<?php
/**
 * @category  Aligent
 * @package   zipmoney
 * @author    Andi Han <andi@aligent.com.au>
 * @copyright 2014 Aligent Consulting.
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.aligent.com.au/
 */

class Zipmoney_ZipmoneyPayment_Helper_Api extends Mage_Core_Helper_Abstract
{
    const ACTION_RESPONSE_TYPE_GET_SHIPPING_METHODS     = 'get_shipping_methods';
    const ACTION_RESPONSE_TYPE_GET_QUOTE_DETAILS        = 'get_quote_details';
    const ACTION_RESPONSE_TYPE_CONFIRM_SHIPPING_METHOD  = 'confirm_shipping_method';
    const ACTION_RESPONSE_TYPE_PRODUCT_REDIRECT         = 'product_redirect';
    const ACTION_RESPONSE_TYPE_CART_REDIRECT            = 'cart_redirect';
    const ACTION_RESPONSE_TYPE_CHECKOUT_REDIRECT        = 'checkout_redirect';
    const ACTION_RESPONSE_TYPE_CONFIRM_ORDER            = 'confirm_order';
    const ACTION_RESPONSE_TYPE_FINALISE_ORDER           = 'finalise_order';
    const ACTION_RESPONSE_TYPE_ORDER_STATUS             = 'order_status';
    const ACTION_RESPONSE_TYPE_SUCCESS_PAGE             = 'success_page';
    const ACTION_RESPONSE_TYPE_CANCEL_QUOTE             = 'cancel_quote';
    const ACTION_RESPONSE_TYPE_DECLINED_APP             = 'decline_app';
    const ACTION_RESPONSE_TYPE_UNDER_REVIEW_PAGE        = 'under_review_page';
    const ACTION_RESPONSE_TYPE_ERROR_PAGE               = 'error_page';

    const API_ERROR_CODE_OK                             = 0;
    const API_ERROR_CODE_GENERAL_ERROR                  = 100;
    const API_ERROR_CODE_QUOTE_CHANGED                  = 101;
    const API_ERROR_CODE_ORDER_NOT_IN_REQUEST           = 102;
    const API_ERROR_CODE_NO_ORDER_ITEMS_IN_REQUEST      = 103;
    

    const CONFIGURABLE_PRODUCT_IMAGE= 'checkout/cart/configurable_product_image';
    const USE_PARENT_IMAGE          = 'parent';

    protected $_oApi = null;
    protected $_oApiTimeout = 60;
    protected $_vMerchantId = null;
    protected $_vMerchantKey = null;
    protected $_vMerchantPublicKey = null;
    protected $_vMerchantEnv = null;


    public function getMerchantId($bForceUpdate = false)
    {
        $vPath = Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_PAYMENT_ID;
        if($bForceUpdate || $this->_vMerchantId === null) {
            $this->_vMerchantId = Mage::getModel('zipmoneypayment/config')->getConfigByCurrentScope($vPath);
        }
        return $this->_vMerchantId;
    }

    public function getMerchantKey($bForceUpdate = false)
    {
        $vPath = Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_PAYMENT_KEY;
        if($bForceUpdate || $this->_vMerchantKey === null) {
            $this->_vMerchantKey = Mage::getModel('zipmoneypayment/config')->getConfigByCurrentScope($vPath);
        }
        return $this->_vMerchantKey;
    }
    
    public function getMerchantPublicKey($bForceUpdate = false)
    {
        $vPath = Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_PAYMENT_PUBLIC_KEY;
        if($bForceUpdate) {
            $this->_vMerchantPublicKey = trim(Mage::getStoreConfig($vPath));
        } else {
            if($this->_vMerchantPublicKey === null) {
                $this->_vMerchantPublicKey = trim(Mage::getStoreConfig($vPath));
            }
        }
        return $this->_vMerchantPublicKey;
    }

    public function getEnv($bForceUpdate = false)
    {
        $vPath = Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_PAYMENT_ENVIRONMENT;
        if($bForceUpdate) {
            $this->_vMerchantEnv = trim(Mage::getStoreConfig($vPath));
        } else {
            if($this->_vMerchantEnv === null) {
                $this->_vMerchantEnv = trim(Mage::getStoreConfig($vPath));
            }
        }
        return $this->_vMerchantEnv;
    }

    /**
     * Get zipMoney API object
     *
     * @return null|ZipMoney_Api
     */
    public function getApi()
    {
        if($this->_oApi == null) {
            $vPath = Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_PAYMENT_ENVIRONMENT;
            $vEnv = Mage::getModel('zipmoneypayment/config')->getConfigByCurrentScope($vPath);      // environment: test / live
            $vMerchantId = Mage::getSingleton('zipmoneypayment/storeScope')->getMerchantId();
            $vMerchantKey = Mage::getSingleton('zipmoneypayment/storeScope')->getMerchantKey();
            $this->_oApi = new ZipMoney_Api($vEnv, $vMerchantId, $vMerchantKey);

            $vPath = Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_DEVELOPER_SETTINGS_TIMEOUT;
            $this->_oApiTimeout = Mage::getModel('zipmoneypayment/config')->getConfigByCurrentScope($vPath);
        }
        return $this->_oApi;
    }

    /**
     * Call zipMoney API endpoint
     *
     * @param $vEndpoint
     * @param $aRequestData
     * @return mixed|null|Zend_Http_Response
     */
    public function callZipApi($vEndpoint, $aRequestData)
    {
        $oLogging = Mage::helper('zipmoneypayment/logging');
        $oLogging->writeLog($this->__('Endpoint: ' . $vEndpoint), Zend_Log::DEBUG);
        $oLogging->writeLog(json_encode($aRequestData), Zend_Log::DEBUG);
        $oApi = $this->getApi();
        $oResponse = $oApi->callZipApi($vEndpoint, json_encode($aRequestData), $this->_oApiTimeout);
        return $oResponse;
    }

    /**
     * Check availability of merchant_id and merchant_key
     *
     * @param $vMerchantId
     * @param $vMerchantKey
     * @return bool
     */
    public function isApiKeysValid($vMerchantId, $vMerchantKey)
    {
        if($vMerchantId && $vMerchantKey) {
            if($vMerchantId == $this->getMerchantId()
                && $vMerchantKey == $this->getMerchantKey()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the body of response from zipMoney
     *
     * @param $oResponse
     * @return mixed|null
     */
    public function getResponseData($oResponse)
    {
        $oLogging = Mage::helper('zipmoneypayment/logging');
        if ($oResponse && $oResponse->isSuccessful()) {
            $aResponseData = json_decode($oResponse->getBody(), true);
            $oLogging->writeLog($this->__('Success response from zipMoney.'), Zend_Log::INFO);
        } else {
            $aResponseData = null;
            $oLogging->writeLog($this->__('Failure response from zipMoney.'), Zend_Log::WARN);
            $oLogging->writeLog($oResponse->getBody(), Zend_Log::DEBUG);
        }
        return $aResponseData;
    }

    protected function _getGenderText($iGender)
    {
        $vGender = Mage::getModel('customer/customer')->getResource()
            ->getAttribute('gender')
            ->getSource()
            ->getOptionText($iGender);
        return $vGender;
    }

    /**
     * Get data for consumer section in json from existing customer
     *
     * @param $oCustomer
     * @return array|null
     */
    public function getCustomerData($oCustomer)
    {
        if(!$oCustomer || !$oCustomer->getId()) {
            return null;
        }
        $oLogCustomer = Mage::getModel('log/customer')->loadByCustomer($oCustomer);
        $aCustomerData = Array();
        if(Mage::helper('customer')->isLoggedIn() || $oCustomer->getId()) {
            // get customer merchant history
            $cOrderCollection = Mage::getModel('sales/order')->getCollection()
                ->addFieldToFilter('customer_id', array('eq' => array($oCustomer->getId())))
                ->addFieldToFilter('state', array(
                    array('eq' => Mage_Sales_Model_Order::STATE_COMPLETE),
                    array('eq' => Mage_Sales_Model_Order::STATE_CLOSED)
                ));
            $fLifetimeSalesAmount           = 0;        // total amount of complete orders
            $fMaximumSaleValue              = 0;        // Maximum single order amount among complete orders
            $fLifetimeSalesRefundedAmount   = 0;        // Total refunded amount (of closed orders)
            $fAverageSaleValue              = 0;        // Average order amount
            $iOrderNum                      = 0;        // Total number of orders
            $bDeclinedBefore                = false;    // the number of declined payments
            $bChargeBackBefore              = false;    // any payments that have been charged back by their bank or card provider.
                                                        //  A charge back is when a customer has said they did not make the payment, and the bank forces a refund of the amount
            foreach ($cOrderCollection AS $oOrder) {
                if ($oOrder->getState() == Mage_Sales_Model_Order::STATE_COMPLETE) {
                    $iOrderNum++;
                    $fLifetimeSalesAmount += $oOrder->getGrandTotal();
                    if ($oOrder->getGrandTotal() > $fMaximumSaleValue) {
                        $fMaximumSaleValue = $oOrder->getGrandTotal();
                    }
                } else if ($oOrder->getState() == Mage_Sales_Model_Order::STATE_CLOSED) {
                    $fLifetimeSalesRefundedAmount += $oOrder->getGrandTotal();
                }
            }
            if ($iOrderNum > 0) {
                $fAverageSaleValue = (float)round($fLifetimeSalesAmount / $iOrderNum, 2);
            }

            if ($oCustomer->getGender()) {
                $aCustomerData['gender'] = $this->_getGenderText($oCustomer->getGender());
            }
            if ($oCustomer->getDob()) {
                $aCustomerData['dob'] = $oCustomer->getDob();
            }
            foreach ($oCustomer->getAddresses() as $oAddress) {
                if ($oAddress->getTelephone()) {
                    $aCustomerData['phone'] = $oAddress->getTelephone();
                    break;
                }
            }
            if ($oCustomer->getPrefix()) {
                $aCustomerData['title'] = $oCustomer->getPrefix();
            }
            if ($oLogCustomer->getLoginAtTimestamp()) {
                $aCustomerData['last_login'] = date('Y-m-d H:i:s', $oLogCustomer->getLoginAtTimestamp());
            }
            $aCustomerData['email']                             = $oCustomer->getEmail();
            $aCustomerData['first_name']                        = $oCustomer->getFirstname();
            $aCustomerData['last_name']                         = $oCustomer->getLastname();
            $aCustomerData['account_created_on']                = $oCustomer->getCreatedAt();
            $aCustomerData['lifetime_sales_amount']             = $fLifetimeSalesAmount;
            $aCustomerData['average_sale_value']                = $fAverageSaleValue;
            $aCustomerData['maximum_sale_value']                = $fMaximumSaleValue;
            $aCustomerData['lifetime_sales_units']              = $iOrderNum;
            $aCustomerData['lifetime_sales_refunded_amount']    = $fLifetimeSalesRefundedAmount;
            $aCustomerData['declined_before']                   = $bDeclinedBefore;
            $aCustomerData['chargeback_before']                 = $bChargeBackBefore;
        }
        return $aCustomerData;
    }

    /**
     * Get customer data for consumer section in json from existing quote if the customer does not exist
     *
     * @param Mage_Sales_Model_Quote $oQuote
     * @return array|null
     */
    public function getRegisterCustomerData(Mage_Sales_Model_Quote $oQuote)
    {
        if(!$oQuote) {
            return null;
        }

        $aCustomerData = Array();
        if ($oQuote->getCustomerGender()) {
            $aCustomerData['gender'] = $this->_getGenderText($oQuote->getCustomerGender());
        }
        if ($oQuote->getCustomerDob()) {
            $aCustomerData['dob'] = $oQuote->getCustomerDob();
        }
        if ($oQuote->getCustomerPrefix()) {
            $aCustomerData['title'] = $oQuote->getCustomerPrefix();
        }
        $vPhone = $oQuote->getShippingAddress()->getTelephone();
        if ($vPhone) {
            $aCustomerData['phone'] = $vPhone;
        }
        $aCustomerData['email']                             = $oQuote->getCustomerEmail();
        $aCustomerData['first_name']                        = $oQuote->getCustomerFirstname();
        $aCustomerData['last_name']                         = $oQuote->getCustomerLastname();
        // default values for customer merchant history
        $aCustomerData['lifetime_sales_amount']             = 0;
        $aCustomerData['average_sale_value']                = 0;
        $aCustomerData['maximum_sale_value']                = 0;
        $aCustomerData['lifetime_sales_units']              = 0;
        $aCustomerData['lifetime_sales_refunded_amount']    = 0;
        $aCustomerData['declined_before']                   = false;
        $aCustomerData['chargeback_before']                 = false;

        return $aCustomerData;
    }

    public function getGuestCustomerData(Mage_Sales_Model_Order $oOrder)
    {
        if(!$oOrder) {
            return null;
        }

        $aCustomerData = Array();
        if ($oOrder->getCustomerGender()) {
            $aCustomerData['gender'] = $this->_getGenderText($oOrder->getCustomerGender());
        }
        if ($oOrder->getCustomerDob()) {
            $aCustomerData['dob'] = $oOrder->getCustomerDob();
        }
        if ($oOrder->getCustomerPrefix()) {
            $aCustomerData['title'] = $oOrder->getCustomerPrefix();
        }
        $vPhone = $oOrder->getShippingAddress()->getTelephone();
        if ($vPhone) {
            $aCustomerData['phone'] = $vPhone;
        }
        $aCustomerData['email']                             = $oOrder->getCustomerEmail();
        $aCustomerData['first_name']                        = $oOrder->getCustomerFirstname();
        $aCustomerData['last_name']                         = $oOrder->getCustomerLastname();
        // default values for customer merchant history
        $aCustomerData['lifetime_sales_amount']             = 0;
        $aCustomerData['average_sale_value']                = 0;
        $aCustomerData['maximum_sale_value']                = 0;
        $aCustomerData['lifetime_sales_units']              = 0;
        $aCustomerData['lifetime_sales_refunded_amount']    = 0;
        $aCustomerData['declined_before']                   = false;
        $aCustomerData['chargeback_before']                 = false;

        return $aCustomerData;
    }

    /**
     * Get Shipping Price
     *
     * @param $oShippingAddress
     * @param $oQuote
     * @param $fPrice
     * @param $bFlag
     * @return float
     */
    public function getShippingPrice($oShippingAddress, $oQuote, $fPrice, $bFlag)
    {
        $fRawPrice = Mage::helper('tax')->getShippingPrice(
            $fPrice,
            $bFlag,
            $oShippingAddress,
            $oQuote->getCustomerTaxClassId()
        );
        return $fRawPrice;
    }

    protected function _getShippingEstimateRates($oShippingAddress)
    {
        $cRates = $oShippingAddress->getGroupedAllShippingRates();
        return $cRates;
    }

    /**
     * Get data for shipping_address/billing_address section in json from quote_address/order_address which depends on whether the quote is converted to order.
     *
     * @param $oAddress
     * @param $bShippingRates
     * @return array|null
     */
    protected function _getAddressData($oAddress, $bShippingRates = false)
    {
        if(!$oAddress) {
            return null;
        }
        if(!$oAddress->getStreet1()
            || !$oAddress->getCity()
            || !$oAddress->getCountryId()
            || !$oAddress->getPostcode()
        ) {
            return null;
        }

        $oLogging = Mage::helper('zipmoneypayment/logging');
        $aAddressData = Array();
        if($oAddress && $oAddress->getId()) {
            $aAddressData['first_name'] = $oAddress->getFirstname();
            $aAddressData['last_name']  = $oAddress->getLastname();
            $aAddressData['email']      = $oAddress->getEmail();
            $aAddressData['line1']      = $oAddress->getStreet1();
            $aAddressData['line2']      = $oAddress->getStreet2();
            $aAddressData['country']    = $oAddress->getCountryId();
            $aAddressData['zip']        = $oAddress->getPostcode();
            $aAddressData['city']       = $oAddress->getCity();

            /**
             * If region_id is null, the state is saved in region directly, so the state can be got from region.
             * If region_id is a valid id, the state should be got by getRegionCode.
             */
            if ($oAddress->getRegionId()) {
                $aAddressData['state'] = $oAddress->getRegionCode();
            } else {
                $aAddressData['state'] = $oAddress->getRegion();
            }

            if ($oAddress->getAddressType() == Mage_Customer_Model_Address_Abstract::TYPE_SHIPPING) {
                if ($oAddress instanceof Mage_Sales_Model_Quote_Address) {
                    $oQuote = $oAddress->getQuote();

                    /**
                     * for quote shipping address
                     */
                    if ($bShippingRates && $oQuote->getIsActive()) {
                        $oLogging->writeLog($this->__('Get shipping rates.'), Zend_Log::INFO);
                        // create temporary session/customer session
                        $oCheckoutSession = Mage::getSingleton('checkout/session');
                        $oCustomerSession = Mage::getSingleton('customer/session');
                        if ($oCheckoutSession->getQuote()
                            && $oCheckoutSession->getQuoteId() != $oQuote->getId()) {
                            $oLogging->writeLog($this->__('Replace quote id: %s with %s', $oCheckoutSession->getQuoteId(), $oQuote->getId()), Zend_Log::DEBUG);
                            Mage::getSingleton('checkout/session')->replaceQuote($oQuote);
                            $oLogging->writeLog($this->__('Current temporary session quote id: %s', $oCheckoutSession->getQuoteId()), Zend_Log::DEBUG);
                        }
                        if (!$oQuote->getCustomerIsGuest()
                            && $oQuote->getCustomerId()
                            && $oCustomerSession->getCustomerId() != $oQuote->getCustomerId()) {
                            $oLogging->writeLog($this->__('Log in customer (id: %s). Old customer id: %s.', $oQuote->getCustomerId(), $oCustomerSession->getCustomerId()), Zend_Log::DEBUG);
                            Mage::getSingleton('customer/session')->loginById($oQuote->getCustomerId());
                            $oLogging->writeLog($this->__('Current temporary session customer id: %s', $oCustomerSession->getCustomerId()), Zend_Log::DEBUG);
                        }

                        $oQuote->collectTotals();
                        $cRates = $this->_getShippingEstimateRates($oAddress);
                        $aOptions = array();
                        foreach ($cRates as $aCarrier) {
                            foreach ($aCarrier as $oRate) {
                                /** @var Mage_Sales_Model_Quote_Address_Rate $oRate */
                                $vMethod = $oRate->getMethod();
                                $vErrorMessage = $oRate->getErrorMessage();
                                $vName = $oRate->getCarrierTitle() . ' - ' . $oRate->getMethodTitle();
                                $vId = $oRate->getCode();
                                $fShippingValueInclTax = $this->getShippingPrice($oAddress, $oAddress->getQuote(), $oRate->getPrice(), true);
                                $fShippingValueExclTax = $this->getShippingPrice($oAddress, $oAddress->getQuote(), $oRate->getPrice(), false);
                                if ($fShippingValueInclTax === null || $fShippingValueExclTax === null
                                    || !is_numeric($fShippingValueInclTax) || !is_numeric($fShippingValueExclTax)
                                    || !$vMethod || $vErrorMessage
                                ) {
                                    $oLogging->writeLog($this->__('Carrier/method title: %s', $vName), Zend_Log::INFO);
                                    $oLogging->writeLog($this->__('Method: %s', $vMethod), Zend_Log::DEBUG);
                                    $oLogging->writeLog($this->__('There is an error in %s. %s', $vId, $vErrorMessage), Zend_Log::WARN);
                                    $fShippingValueInclTax = null;
                                    $fShippingValueExclTax = null;
                                    $oLogging->writeLog($this->__('Skip the shipping rate.'), Zend_Log::INFO);
                                    continue;
                                }
                                try {
                                    if ($fShippingValueInclTax === null || $fShippingValueExclTax === null) {
                                        $fShippingTax = null;
                                    } else {
                                        $fShippingTax = $fShippingValueInclTax - $fShippingValueExclTax;
                                    }
                                } catch (Exception $e) {
                                    $oLogging->writeLog($this->__('An error occurred when calculating shipping tax. ') . $e->getMessage(), Zend_Log::ERR);
                                    $oLogging->writeLog($e->getTraceAsString(), Zend_Log::DEBUG);
                                    $fShippingTax = null;
                                }
                                $aOptions[] = array(
                                    'name'          => $vName,
                                    'id'            => $vId,
                                    'value'         => $fShippingValueInclTax,
                                    'tax'           => $fShippingTax,
                                );
                            }
                        }
                        $aAddressData['options'] = $aOptions;
                        $oQuote->save();
                    }
                    $aAddressData['selected_option_id'] = $oAddress->getShippingMethod();
                }
            }
        }
        return $aAddressData;
    }

    public function getShippingMethodsData($oShippingAddress, $bShippingRates = false)
    {
        return $this->_getAddressData($oShippingAddress, $bShippingRates);
    }

    public function getBillingAddressData($oBillingAddress)
    {
        return $this->_getAddressData($oBillingAddress);
    }

    protected function _getCategory($iProductId)
    {
        $oLogging = Mage::helper('zipmoneypayment/logging');
        $aCategoryName = array();
        $vCategory = '';
        try {
            $oProduct = Mage::getModel("catalog/product")->load($iProductId);
            $aCategoryIds = $oProduct->getCategoryIds();
            foreach ($aCategoryIds as $iCategoryId) {
                $oCategory = Mage::getModel('catalog/category')->load($iCategoryId);
                $aCategoryName[] = $oCategory->getName();
            }
            $vCategory = implode(',', $aCategoryName);
        } catch (Exception $e) {
            $oLogging->writeLog($this->__('An error occurred during getting category for product ' . $iProductId . '.'), Zend_Log::WARN);
            $oLogging->writeLog($e->getMessage(), Zend_Log::ERR);
            $oLogging->writeLog($e->getTraceAsString(), Zend_Log::DEBUG);
        }
        return $vCategory;
    }


    public function getChildProduct($item)
    {
        if ($option = $item->getOptionByCode('simple_product')) {
            return $option->getProduct();
        }
        return $item->getProduct();
    }

  

    protected function _getProductImage($item)
    {

        $oLogging = Mage::helper('zipmoneypayment/logging');
        $vImageUrl = '';
        try {
            
            $product = $this->getChildProduct($item);

            if (!$product || !$product->getData('thumbnail')
                || ($product->getData('thumbnail') == 'no_selection')
                || (Mage::getStoreConfig(self::CONFIGURABLE_PRODUCT_IMAGE) == 'parent')) {
                $product =  $item->getProduct();
            }           

            $vImageUrl = (string)Mage::helper('catalog/image')->init($product, 'thumbnail');

        } catch (Exception $e) {
            $oLogging->writeLog($this->__('An error occurred during getting item image for product ' . $product->getId() . '.'), Zend_Log::WARN);
            $oLogging->writeLog($e->getMessage(), Zend_Log::ERR);
            $oLogging->writeLog($e->getTraceAsString(), Zend_Log::DEBUG);
        }
        return $vImageUrl;
    }

    public function getProductShortDescription($item, $iStoreId)
    {
       
        $product = $this->getChildProduct($item);
        
        $oLogging = Mage::helper('zipmoneypayment/logging');

        if (!$product) {
            $product = $item->getProduct();
            
            $vDescription = $product->getShortDescription();

            if (!$vDescription) {
                $vDescription = $product->getResource()->getAttributeRawValue($product->getId(), 'short_description', $iStoreId);
            }  

            return $vDescription;
        }    

        $vDescription = $product->getShortDescription();

        if (!$vDescription) {
            $vDescription = $product->getResource()->getAttributeRawValue($product->getId(), 'short_description', $iStoreId);
        }  

        return $vDescription;
    }

    /**
     * Get data for order section in json from existing quote if the quote has not been converted to order.
     *
     * @param Mage_Sales_Model_Quote $oQuote
     * @param null $oShippingAddress
     * @return array|null
     */
    public function getOrderDataByQuote(Mage_Sales_Model_Quote $oQuote, $oShippingAddress = null)
    {
        if(!$oQuote || !$oQuote->getId()) {
            return null;
        }
        $aOrderData = Array();
        $aOrderData['id'] = $oQuote->getReservedOrderId() ? $oQuote->getReservedOrderId() : '0';
        $aOrderData['tax'] = $oShippingAddress ? $oShippingAddress->getTaxAmount() : 0.00;
        $aOrderData['discount_amount'] = $oShippingAddress ? $oShippingAddress->getDiscountAmount() : 0.00;
        $aOrderData['shipping_value'] = $oShippingAddress ? $oShippingAddress->getShippingInclTax() : 0.00;
        $aOrderData['shipping_tax'] = $oShippingAddress ? $oShippingAddress->getShippingTaxAmount() : 0.00;
        $aOrderData['total'] = $oQuote->getGrandTotal();

        /** @var Mage_Sales_Model_Quote_Item $oItem */
        foreach($oQuote->getAllItems() as $oItem) {
            if($oItem->getParentItemId()) {
                continue;   // Only sends parent items to zipMoney
            }

            $aItem = array();
            
            if ($oItem->getDescription()) {
                $vDescription = $oItem->getDescription();
            } else {
                $vDescription = $this->getProductShortDescription($oItem, $oQuote->getStoreId());
            }

            $aItem['id']            = $oItem->getId();
            $aItem['name']          = $oItem->getName();
            $aItem['sku']           = $oItem->getSku();
            $aItem['description']   = $vDescription;
            $aItem['price']         = $oItem->getPriceInclTax() ? $oItem->getPriceInclTax() : 0.0000;
            $aItem['quantity']      = round($oItem->getQty());          // zipMoney does not support a decimal item quantity at this point, so round the item_qty here.
            $aItem['discount_percent'] = $oItem->getDiscountPercent();
            $aItem['discount_amount'] = $oItem->getDiscountAmount();
            $aItem['category']      = $this->_getCategory($oItem->getProductId());
            $aItem['image_url']     = $this->_getProductImage($oItem);

            $aOrderData['detail'][] = $aItem;
        }
        return $aOrderData;
    }

    /**
     * Get data for order section in json from existing order if the order has already been created.
     *
     * @param Mage_Sales_Model_Order $oOrder
     * @param bool $bStatusState
     * @return array|null
     */
    public function getOrderDataByOrder(Mage_Sales_Model_Order $oOrder, $bStatusState = false)
    {
        if(!$oOrder || !$oOrder->getId()) {
            return null;
        }
        $aOrderData = Array();

        $aOrderData['id'] = $oOrder->getIncrementId();
        $aOrderData['tax'] = $oOrder->getTaxAmount() ? $oOrder->getTaxAmount() : 0.00;
        $aOrderData['discount_amount'] = $oOrder->getDiscountAmount() ? $oOrder->getDiscountAmount() : 0.00;
        $aOrderData['shipping_value'] = $oOrder->getShippingInclTax() ? $oOrder->getShippingInclTax() : 0.00;
        $aOrderData['shipping_tax'] = $oOrder->getShippingTaxAmount() ? $oOrder->getShippingTaxAmount() : 0.00;
        $aOrderData['total'] = $oOrder->getGrandTotal() ? $oOrder->getGrandTotal() : 0.00;
        if ($bStatusState) {
            $aOrderData['total_refunded'] = $oOrder->getTotalRefunded() ? $oOrder->getTotalRefunded() : 0.00;
            $aOrderData['state'] = $oOrder->getState();
            $aOrderData['status'] = $oOrder->getStatus();
        }

        /** @var Mage_Sales_Model_Order_Item $oItem */
        foreach($oOrder->getAllItems() as $oItem) {
            if($oItem->getParentItemId()) {
                continue;   // Only sends parent items to zipMoney
            }

            $aItem = array();
            if ($oItem->getDescription()) {
                $vDescription = $oItem->getDescription();
            } else {
                $vDescription = $this->getProductShortDescription($oItem, $oOrder->getStoreId());
            }
            $aItem['id']            = $oItem->getId();
            $aItem['name']          = $oItem->getName();
            $aItem['sku']           = $oItem->getSku();
            $aItem['description']   = $vDescription;
            $aItem['price']         = $oItem->getPriceInclTax() ? $oItem->getPriceInclTax() : 0.00;
            $aItem['quantity']      = round($oItem->getQtyOrdered());       // zipMoney does not support a decimal item quantity at this point, so round the item_qty here.
            $aItem['discount_percent'] = $oItem->getDiscountPercent();
            $aItem['discount_amount'] = $oItem->getDiscountAmount();
            $aItem['category']      = $this->_getCategory($oItem->getProductId());
            $aItem['image_url']     = $this->_getProductImage($oItem);
            $aOrderData['detail'][] = $aItem;
        }
        return $aOrderData;
    }

    protected function _addApiKeysToArray($aData)
    {
        $aData['merchant_id'] = $this->getMerchantId();
        $aData['merchant_key'] = $this->getMerchantKey();
        $aData['version'] = array(
                                'client'=> Zipmoney_ZipmoneyPayment_Model_Config::MODULE_VERSION,
                                'platform'=> Zipmoney_ZipmoneyPayment_Model_Config::MODULE_PLATFORM
                            );
        return $aData;
    }

    protected function _addCustomerToArray($aData, $oQuote, $oOrder, $oCustomer)
    {
        // customer data
        $aCustomerData = null;
        if ($oQuote) {
            $vCheckoutMethod = $oQuote->getCheckoutMethod();
            if ($vCheckoutMethod == Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER
                || $vCheckoutMethod == Mage_Checkout_Model_Type_Onepage::METHOD_GUEST) {
                $aCustomerData = $this->getRegisterCustomerData($oQuote);
            } else {
                if (!$oCustomer) {
                    // load customer from database rather than session
                    $oCustomer = Mage::getModel('customer/customer')->load($oQuote->getCustomerId());
                }
                if ($oCustomer && $oCustomer->getId()) {
                    $aCustomerData = $this->getCustomerData($oCustomer);
                }
            }
        } else if ($oOrder) {
            if ($oOrder->getCustomerIsGuest() && !$oCustomer) {
                // get customer (guest) data from order
                $aCustomerData = $this->getGuestCustomerData($oOrder);
            } else {
                if (!$oCustomer) {
                    $oCustomer = Mage::getModel('customer/customer')->load($oOrder->getCustomerId());
                }
                if ($oCustomer && $oCustomer->getId()) {
                    $aCustomerData = $this->getCustomerData($oCustomer);
                }
            }
        } else {
            return null;
        }

        if ($aCustomerData) {
            $aData['consumer'] = $aCustomerData;
        }
        return $aData;
    }

    protected function _addShippingAddressToArray($aData, $oShippingAddress, $bShippingRates = false)
    {
        // Shipping_address data
        $aShippingAddressData = $this->getShippingMethodsData($oShippingAddress, $bShippingRates);
        if ($aShippingAddressData) {
            $aData['shipping_address'] = $aShippingAddressData;
        }
       
        return $aData;
    }

    protected function _addBillingAddressToArray($aData, $oBillingAddress)
    {
        $aBillingAddressData = $this->getBillingAddressData($oBillingAddress);
        if ($aBillingAddressData) {
            $aData['billing_address'] = $aBillingAddressData;
        }
        return $aData;
    }

    protected function _addOrderToArrayByOrder($aData, $oOrder, $bStatusState = false)
    {
        $aOrderData = $this->getOrderDataByOrder($oOrder, $bStatusState);
        if ($aOrderData) {
            $aData['order'] = $aOrderData;
        }
        return $aData;
    }

    protected function _addOrderToArrayByQuote($aData, $oQuote, $oShippingAddress)
    {
        $aOrderData = $this->getOrderDataByQuote($oQuote, $oShippingAddress);
        if ($aOrderData) {
            $aData['order'] = $aOrderData;
        }
        return $aData;
    }

    protected function _addUrlsToArray($aData, $oQuote)
    {
        $oHelper = Mage::helper('zipmoneypayment');
        $aData['success_url']    = $oHelper->getReturnUrl('success_url', $oQuote);
        $aData['cart_url']       = $oHelper->getReturnUrl('cart_url', $oQuote);
        $aData['refer_url']      = $oHelper->getReturnUrl('refer_url', $oQuote);
        $aData['cancel_url']     = $oHelper->getReturnUrl('cancel_url', $oQuote);
        $aData['error_url']      = $oHelper->getReturnUrl('error_url', $oQuote);
        $aData['decline_url']    = $oHelper->getReturnUrl('decline_url', $oQuote);
        return $aData;
    }

    protected function _prepareDataForMerchantConfigure($aRawData)
    {
        return $aRawData;
    }

    protected function _prepareDataForMerchantSettings($aRawData)
    {
        return $aRawData;
    }

    protected function _prepareDataForQuote($aRawData)
    {
        if (!isset($aRawData['quote'])
            || !isset($aRawData['checkout_source'])
        ) {
            $vMessage = Mage::helper('zipmoneypayment')->__('Incorrect API request data.');
            Mage::throwException($vMessage);
        }

        $oQuote = $aRawData['quote'];
        if(!$oQuote || !$oQuote->getId()) {
            $vMessage = Mage::helper('zipmoneypayment')->__('Cannot find the quote. The quote is empty.');
            Mage::throwException($vMessage);
        }

        $aRequestData = array();

        $oShippingAddress = $oQuote->getShippingAddress();
        $vCheckoutSource = $aRawData['checkout_source'];
        $oBillingAddress = $oQuote->getBillingAddress();
        $oCustomer = null;
        $vCheckoutMethod = $oQuote->getCheckoutMethod();
        if ($vCheckoutMethod != Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER
            && $vCheckoutMethod != Mage_Checkout_Model_Type_Onepage::METHOD_GUEST) {
            // load customer from database rather than session
            $oCustomer = Mage::getModel('customer/customer')->load($oQuote->getCustomerId());
        }

        // customer data
        $aRequestData = $this->_addCustomerToArray($aRequestData, $oQuote, null, $oCustomer);

        // billing_address data
        $aRequestData = $this->_addBillingAddressToArray($aRequestData, $oBillingAddress);

        // Shipping_address data
        $aRequestData = $this->_addShippingAddressToArray($aRequestData, $oShippingAddress, false);

        // order data
        $aRequestData = $this->_addOrderToArrayByQuote($aRequestData, $oQuote, $oShippingAddress);

        // URLs
        $aRequestData = $this->_addUrlsToArray($aRequestData, $oQuote);

        $aRequestData['quote_id']       = $oQuote->getId();
        $aRequestData['token']          = $oQuote->getZipmoneyToken();
        $aRequestData['checkout_source']= $vCheckoutSource;
        $aRequestData['reference']    = 'DefaultRefundReferenceValue';
        return $aRequestData;
    }

    protected function _prepareDataForOrderShippingAddress($aRawData)
    {
        if (!isset($aRawData['order'])
            || !isset($aRawData['shipping_address'])
        ) {
            $vMessage = Mage::helper('zipmoneypayment')->__('Incorrect API request data.');
            Mage::throwException($vMessage);
        }

        $aRequestData = array();
        $oOrder = $aRawData['order'];
        if(!$oOrder || !$oOrder->getId()) {
            $vMessage = Mage::helper('zipmoneypayment')->__('Cannot find the order. The order is empty.');
            Mage::throwException($vMessage);
        }

        $oShippingAddress = $aRawData['shipping_address'];

        // Shipping_address data
        $aRequestData = $this->_addShippingAddressToArray($aRequestData, $oShippingAddress, false);

        // order data
        $aRequestData = $this->_addOrderToArrayByOrder($aRequestData, $oOrder);

        $aRequestData['quote_id'] = $oOrder->getQuoteId();
        $aRequestData['merchant_id'] = $this->getMerchantId();
        $aRequestData['merchant_key'] = $this->getMerchantKey();
        $aRequestData['reference']    = 'DefaultRefundReferenceValue';
        return $aRequestData;
    }

    protected function _prepareDataForOrderCancel($aRawData)
    {
        if (!isset($aRawData['order'])) {
            $vMessage = Mage::helper('zipmoneypayment')->__('Incorrect API request data.');
            Mage::throwException($vMessage);
        }
        $oLogging = Mage::helper('zipmoneypayment/logging');
        $aRequestData = array();
        $oOrder = $aRawData['order'];
        $oPayment = $oOrder->getPayment();
        $vTxnId = '';
        /**
         * find out last successful authorise transaction, then get its txn_id
         */
        if ($oPayment) {
            $oAuthTransaction = $oPayment->getAuthorizationTransaction();
            if ($oAuthTransaction && $oAuthTransaction->getId()) {
                $vTxnId = $oAuthTransaction->getTxnId();
            } else {
                $oLogging->writeLog($this->__('Can not get authorization transaction txn id.'), Zend_Log::NOTICE);
                $oTransaction = Mage::getModel('sales/order_payment_transaction')->getCollection()
                    ->addAttributeToFilter('order_id', array('eq' => $oOrder->getEntityId()))
                    ->addAttributeToFilter('txn_type', array(
                        array('eq' => 'capture'),
                        array('eq' => 'authorization'),
                    ))
                    ->setOrder('created_at', 'DESC')->getFirstItem();
                if ($oTransaction && $oTransaction->getId()) {
                    $vTxnId = $oTransaction->getTxnId();
                }
            }
        }
        if (!$vTxnId) {
            $oLogging->writeLog($this->__('Can not get txn id.'), Zend_Log::WARN);
        }

        // order data
        $aRequestData = $this->_addOrderToArrayByOrder($aRequestData, $oOrder);

        $aRequestData['order_id']       = $oOrder->getIncrementId();
        $aRequestData['quote_id']       = $oOrder->getQuoteId();
        $aRequestData['merchant_id']    = $this->getMerchantId();
        $aRequestData['merchant_key']   = $this->getMerchantKey();
        $aRequestData['txn_id']         = $vTxnId;
        $aRequestData['reference']      = $oOrder->getIncrementId();

        return $aRequestData;
    }

    protected function _prepareDataForRefund($aRawData)
    {
        if (!isset($aRawData['payment'])
            || !isset($aRawData['refund_amount'])
            || !isset($aRawData['comment'])
        ) {
            $vMessage = Mage::helper('zipmoneypayment')->__('Incorrect API request data.');
            Mage::throwException($vMessage);
        }

        $aRequestData = array();
        /** @var Mage_Sales_Model_Order_Payment $oPayment */
        /** @var Mage_Sales_Model_Order $oOrder */
        $oPayment = $aRawData['payment'];
        $fRefundAmount = $aRawData['refund_amount'];
        $vComment = $aRawData['comment'];
        if (!$oPayment || !$fRefundAmount) {
            $vMessage = Mage::helper('zipmoneypayment')->__('Incorrect API request data.');
            Mage::throwException($vMessage);
        }
        $oCreditmemo = $oPayment->getCreditmemo();
        $oOrder = $oPayment->getOrder();
        $vTxnId = $oPayment->getLastTransId();

        // order data
        $aRequestData = $this->_addOrderToArrayByOrder($aRequestData, $oOrder);

        $aRequestData['reason']         = $vComment;
        $aRequestData['order_id']       = $oOrder->getIncrementId();
        $aRequestData['quote_id']       = $oOrder->getQuoteId();
        $aRequestData['txn_id']         = $vTxnId;
        $aRequestData['reference']      = $oCreditmemo ? $oCreditmemo->getRefundReference() : 'DefaultRefundReferenceValue';
        $aRequestData['refund_amount']  = $fRefundAmount;
        $aRequestData['merchant_id']    = $this->getMerchantId();
        $aRequestData['merchant_key']   = $this->getMerchantKey();

        return $aRequestData;
    }

    /**
     * Prepare data (array) for zipMoney API request
     *
     * @param $vEndpoint
     * @param array $aRawData
     * @return array
     */
    public function prepareDataForZipRequest($vEndpoint, $aRawData = array())
    {
        $aRequestData = array();
        switch ($vEndpoint) {
            case ZipMoney_ApiSettings::API_TYPE_MERCHANT_CONFIGURE:
                $aRequestData = $this->_prepareDataForMerchantConfigure($aRawData);
                break;
            case ZipMoney_ApiSettings::API_TYPE_MERCHANT_SETTINGS:
                $aRawData = array();
                $aRequestData = $this->_prepareDataForMerchantSettings($aRawData);
                break;
            case ZipMoney_ApiSettings::API_TYPE_QUOTE_QUOTE:
                $aRequestData = $this->_prepareDataForQuote($aRawData);
                break;
            case ZipMoney_ApiSettings::API_TYPE_ORDER_SHIPPING_ADDRESS:
                $aRequestData = $this->_prepareDataForOrderShippingAddress($aRawData);
                break;
            case ZipMoney_ApiSettings::API_TYPE_ORDER_CANCEL:
                $aRequestData = $this->_prepareDataForOrderCancel($aRawData);
                break;
            case ZipMoney_ApiSettings::API_TYPE_ORDER_REFUND:
                $aRequestData = $this->_prepareDataForRefund($aRawData);
                break;
            default:
                // should never go to here
                break;
        }

        $aRequestData = $this->_addApiKeysToArray($aRequestData);
        return $aRequestData;
    }

    protected function _getQuoteDetails($oQuote, $bShippingRates = false)
    {
        $aResponseData = array();

        /** @var Mage_Sales_Model_Quote $oQuote */
        // customer data
        $oCustomer = null;
        $vCheckoutMethod = $oQuote->getCheckoutMethod();
        if ($vCheckoutMethod != Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER && $oQuote->getCustomerId()) {
            // load customer from database rather than session
            $oCustomer = Mage::getModel('customer/customer')->load($oQuote->getCustomerId());
        }
        $aResponseData = $this->_addCustomerToArray($aResponseData, $oQuote, null, $oCustomer);

        // Shipping_address data
        $oShippingAddress = $oQuote->getShippingAddress();
        $aResponseData = $this->_addShippingAddressToArray($aResponseData, $oShippingAddress, $bShippingRates);

        // billing_address data
        $oBillingAddress = $oQuote->getBillingAddress();
        $aResponseData = $this->_addBillingAddressToArray($aResponseData, $oBillingAddress);

        // cart data
        $aResponseData = $this->_addOrderToArrayByQuote($aResponseData, $oQuote, $oShippingAddress);

        // URLs
        $aResponseData = $this->_addUrlsToArray($aResponseData, $oQuote);
        $aResponseData['quote_id']      = $oQuote->getId();
        $aResponseData['is_active']     = $oQuote->getIsActive();

        return $aResponseData;
    }

    protected function _prepareDataForGetShippingMethods($aRawData, $vAction)
    {
        if (!isset($aRawData['quote'])
            || !isset($aRawData['request'])
        ) {
            $vMessage = Mage::helper('zipmoneypayment')->__('Incorrect API response data.');
            Mage::throwException($vMessage);
        }

        /** @var Mage_Sales_Model_Quote $oQuote */
        $oQuote = $aRawData['quote'];
        $oRequest = $aRawData['request'];

        $aResponseData = $this->_getQuoteDetails($oQuote, true);
        $aResponseData = $this->_addAdditionalInfo($aResponseData, $oQuote, $oRequest, $vAction);
        return $aResponseData;
    }

    protected function _prepareDataForGetQuoteDetails($aRawData, $vAction)
    {
        if (!isset($aRawData['quote'])
            || !isset($aRawData['request'])
        ) {
            $vMessage = Mage::helper('zipmoneypayment')->__('Incorrect API response data.');
            Mage::throwException($vMessage);
        }

        /** @var Mage_Sales_Model_Quote $oQuote */
        $oQuote = $aRawData['quote'];
        $oRequest = $aRawData['request'];
        $oLogging = Mage::helper('zipmoneypayment/logging');

        $aResponseData = $this->_getQuoteDetails($oQuote, false);
        $aResponseData = $this->_addAdditionalInfo($aResponseData, $oQuote, $oRequest, $vAction);

        $needs_shipping = Mage::helper('zipmoneypayment/quote')->needsShipping($oQuote);
        
        if(!$aResponseData['shipping_address']['selected_option_id'] && !$needs_shipping){
            $aResponseData['shipping_address']['selected_option_id'] = "not_required";
        }

    return $aResponseData;
    }

    protected function _prepareDataForConfirmShippingMethod($aRawData, $vAction)
    {
        if (!isset($aRawData['quote'])
            || !isset($aRawData['request'])
        ) {
            $vMessage = Mage::helper('zipmoneypayment')->__('Incorrect API response data.');
            Mage::throwException($vMessage);
        }

        /** @var Mage_Sales_Model_Quote $oQuote */
        $oQuote = $aRawData['quote'];
        $oRequest = $aRawData['request'];

        $aResponseData = $this->_getQuoteDetails($oQuote, false);
        $aResponseData = $this->_addAdditionalInfo($aResponseData, $oQuote, $oRequest, $vAction);
        return $aResponseData;
    }

    /**
     * Compare quotes in Magento db and from zip request, and return error code/message if needed
     *
     * @param $aResponseData
     * @param $oQuote
     * @param $oRequest
     * @param $vAction
     * @return mixed
     * @throws Mage_Core_Exception
     */
    protected function _addAdditionalInfo($aResponseData, $oQuote, $oRequest, $vAction)
    {
        if (!isset($oRequest->order)) {
            $aResponseData['error_code'] = self::API_ERROR_CODE_ORDER_NOT_IN_REQUEST;
            $aResponseData['message'] = $this->__('The order object is not in the request.');
            return $aResponseData;
        }
        if (!isset($oRequest->order->detail)) {
            $aResponseData['error_code'] = self::API_ERROR_CODE_NO_ORDER_ITEMS_IN_REQUEST;
            $aResponseData['message'] = $this->__('There is no order detail in the request.');
            return $aResponseData;
        }

        switch ($vAction) {
            case self::ACTION_RESPONSE_TYPE_GET_QUOTE_DETAILS:
                $aResult = $this->compareQuoteInRequestAndDb($oQuote, $oRequest, true, true, true, true, true, false);
                break;
            case self::ACTION_RESPONSE_TYPE_GET_SHIPPING_METHODS:
                $aResult = $this->compareQuoteInRequestAndDb($oQuote, $oRequest, true, false, false, false, true, true);
                break;
            case self::ACTION_RESPONSE_TYPE_CONFIRM_SHIPPING_METHOD:
                $aResult = $this->compareQuoteInRequestAndDb($oQuote, $oRequest, true, true, false, true, true, true);
                break;
            default:
                // should never go to here
                $aResult = null;
                break;
        }

        if (!$aResult) {
            return $aResponseData;
        }
        $bChanged = isset($aResult['changed']) ? $aResult['changed'] : null;
        $vMessage = isset($aResult['message']) ? $aResult['message'] : '';
        if ($bChanged === null) {
            $vMessage = Mage::helper('zipmoneypayment')->__('Incorrect return value from compareQuoteInRequestAndDb.');
            Mage::throwException($vMessage);
        }
        if ($bChanged) {
            $vMessage = $this->__('The shopping cart details may have been changed. ') . $vMessage;
            $aResponseData['error_code'] = self::API_ERROR_CODE_QUOTE_CHANGED;
            $aResponseData['message'] = $vMessage;
            Mage::helper('zipmoneypayment/logging')->writeLog($this->__('Additional info in response: %s', $vMessage), Zend_Log::INFO);
            return $aResponseData;
        }

        $aResponseData['error_code'] = self::API_ERROR_CODE_OK;
        $aResponseData['message'] = '';
        return $aResponseData;
    }

    private function __compareAddress($oQuoteAddress, $oRequestAddress)
    {
        if ((isset($oRequestAddress->line1) && $oRequestAddress->line1 != $oQuoteAddress->getStreet1())
            || (isset($oRequestAddress->line2) && $oRequestAddress->line2 != $oQuoteAddress->getStreet2())
            || (isset($oRequestAddress->country) && $oRequestAddress->country != $oQuoteAddress->getCountryId())
            || (isset($oRequestAddress->zip) && $oRequestAddress->zip != $oQuoteAddress->getPostcode())
            || (isset($oRequestAddress->city) && $oRequestAddress->city != $oQuoteAddress->getCity())
        ) {
            return true;
        }

        if ($oQuoteAddress->getRegionId()) {
            if (isset($oRequestAddress->state) && $oRequestAddress->state != $oQuoteAddress->getRegionCode()) {
                return true;
            }
        } else {
            if (isset($oRequestAddress->state) && $oRequestAddress->state != $oQuoteAddress->getRegion()) {
                return true;
            }
        }
        return false;
    }

    /**
     * check quote details
     *
     * @param $oQuote
     * @param $oRequest
     * @param bool $bItemsOnly
     * @return array
     */
    public function compareQuote($oQuote, $oRequest, $bItemsOnly = false)
    {
        $aReturn = array(
            'changed' => false,
            'message' => '',
        );

        if (!isset($oRequest->order)) {
            $aReturn['changed'] = true;
            $aReturn['message'] = $this->__('Order is missing from request.');
            return $aReturn;
        } else {
            $oRequestOrder = $oRequest->order;

            if (isset($oRequestOrder->id) && $oRequestOrder->id != $oQuote->getReservedOrderId()) {
                $aReturn['changed'] = true;
                $aReturn['message'] = $this->__('Order id has been changed from %s to %s.', $oRequestOrder->id, $oQuote->getReservedOrderId());
                return $aReturn;
            } else if (!isset($oRequestOrder->id)) {
                $aReturn['changed'] = true;
                $aReturn['message'] = $this->__('Order id is missing from request.');
                return $aReturn;
            }

            // check count of items
            if (!isset($oRequestOrder->detail)) {
                $aReturn['changed'] = true;
                $aReturn['message'] = $this->__('Order detail is missing from request.');
                return $aReturn;
            }
            $aOrderDetail = $oRequestOrder->detail;
            $iRequestItemCount = count($aOrderDetail);
            $iQuoteItemCount = 0;
            /** @var Mage_Sales_Model_Quote_Item $oItem */
            foreach($oQuote->getAllItems() as $oItem) {
                if($oItem->getParentItemId()) {
                    continue;   // Only sends parent items to zipMoney
                }
                $iQuoteItemCount++;
            }
            if (!$iRequestItemCount) {
                $aReturn['changed'] = true;
                $aReturn['message'] = $this->__('No items are in the quote from request.');
                return $aReturn;
            }
            if ($iRequestItemCount != $iQuoteItemCount) {
                $aReturn['changed'] = true;
                $aReturn['message'] = $this->__('Number of items has been changed from %s to %s.', $iRequestItemCount, $iQuoteItemCount);
                return $aReturn;
            }
            foreach ($aOrderDetail as $oRequestItem) {
                $iItemId = isset($oRequestItem->id) ? $oRequestItem->id : null;
                if ($iItemId === null) {
                    $aReturn['changed'] = true;
                    $aReturn['message'] = $this->__('Item id is missing from request.');
                    return $aReturn;
                }
                $oQuoteItem = $oQuote->getItemById($iItemId);
                $fPrice = $oQuoteItem->getPriceInclTax();
                $iQty = round($oQuoteItem->getQty());
                $fDiscountPercent = $oQuoteItem->getDiscountPercent();
                $fDiscountAmount = $oQuoteItem->getDiscountAmount();
                if (!$oQuoteItem || !$oQuoteItem->getId()) {
                    $aReturn['changed'] = true;
                    $aReturn['message'] = $this->__('The item (id:%s) does not exist in quote.', $iItemId);
                    return $aReturn;
                }
                if (isset($oRequestItem->sku) && $oRequestItem->sku != $oQuoteItem->getSku()) {
                    $aReturn['changed'] = true;
                    $aReturn['message'] = $this->__('The item (id:%s) sku has been changed from %s to %s.', $iItemId, $oRequestItem->sku, $oQuoteItem->getSku());
                    return $aReturn;
                }
                if (isset($oRequestItem->price) && abs($oRequestItem->price - $fPrice) >= 0.0001) {
                    $aReturn['changed'] = true;
                    $aReturn['message'] = $this->__('The item (id:%s) price has been changed from %s to %s.', $iItemId, $oRequestItem->price, $fPrice);
                    return $aReturn;
                }
                if (isset($oRequestItem->quantity) && abs($oRequestItem->quantity - $iQty) >= 0.0001) {
                    $aReturn['changed'] = true;
                    $aReturn['message'] = $this->__('The item (id:%s) qty has been changed from %s to %s.', $iItemId, $oRequestItem->quantity, $iQty);
                    return $aReturn;
                } else if (!isset($oRequestItem->quantity)) {
                    $aReturn['changed'] = true;
                    $aReturn['message'] = $this->__('The item (id:%s) qty is missing from request.', $iItemId);
                    return $aReturn;
                }
                if (isset($oRequestItem->discount_percent) && abs($oRequestItem->discount_percent - $fDiscountPercent) >= 0.0001) {
                    $aReturn['changed'] = true;
                    $aReturn['message'] = $this->__('The item (id:%s) discount percent has been changed from %s to %s.', $iItemId, $oRequestItem->discount_percent, $fDiscountPercent);
                    return $aReturn;
                }
                if (isset($oRequestItem->discount_amount) && abs($oRequestItem->discount_amount - $fDiscountAmount) >= 0.0001) {
                    $aReturn['changed'] = true;
                    $aReturn['message'] = $this->__('The item (id:%s) discount amount has been changed from %s to %s.', $iItemId, $oRequestItem->discount_amount, $fDiscountAmount);
                    return $aReturn;
                }
            }

            if (!$bItemsOnly) {
                $fShippingValue = $oQuote->getShippingAddress()->getShippingInclTax();
                $fGrandTotal = $oQuote->getGrandTotal();
                if (isset($oRequestOrder->total) && abs($oRequestOrder->total - $fGrandTotal) >= 0.0001) {
                    $aReturn['changed'] = true;
                    $aReturn['message'] = $this->__('Grand total has been changed from %s to %s.', $oRequestOrder->total, $fGrandTotal);
                    return $aReturn;
                } else if (!isset($oRequestOrder->total)) {
                    $aReturn['changed'] = true;
                    $aReturn['message'] = $this->__('Order total amount is missing from request.');
                    return $aReturn;
                }

                if (isset($oRequestOrder->shipping_value) && abs($oRequestOrder->shipping_value - $fShippingValue) >= 0.0001) {
                    $aReturn['changed'] = true;
                    $aReturn['message'] = $this->__('Shipping amount has been changed from %s to %s.', $oRequestOrder->shipping_value, $fShippingValue);
                    return $aReturn;
                }
            }
        }
        return $aReturn;
    }

    /**
     * compare quotes in DB and from zip request. Returns true if there's anything different
     *
     * @param $oQuote
     * @param $oRequest
     * @param bool $bCustomer
     * @param bool $bShipping
     * @param bool $bShippingMethod
     * @param bool $bBilling
     * @param bool $bQuote
     * @param bool $bQuoteItemOnly
     * @return array
     */
    public function compareQuoteInRequestAndDb($oQuote, $oRequest, $bCustomer = true, $bShipping = true, $bShippingMethod = false, $bBilling = true, $bQuote = true, $bQuoteItemOnly = false)
    {
        $aReturn = array(
            'changed' => false,
            'message' => '',
        );

        /** @var Mage_Sales_Model_Quote $oQuote */
        if ($bCustomer) {
            if ($oQuote->getCustomerId() && isset($oRequest->consumer)) {
                $oConsumer = $oRequest->consumer;
                if ((isset($oConsumer->email) && $oConsumer->email != $oQuote->getCustomerEmail())
                    || (isset($oConsumer->first_name) && $oConsumer->first_name != $oQuote->getCustomerFirstname())
                    || (isset($oConsumer->last_name) && $oConsumer->last_name != $oQuote->getCustomerLastname())
                ) {
                    $aReturn['changed'] = true;
                    $aReturn['message'] = $this->__('Customer details have been changed.');
                    return $aReturn;
                }
            }
        }

        if ($bShipping) {
            if (isset($oRequest->shipping_address)) {
                if ($this->__compareAddress($oQuote->getShippingAddress(), $oRequest->shipping_address)) {
                    $aReturn['changed'] = true;
                    $aReturn['message'] = $this->__('Shipping address has been changed.');
                    return $aReturn;
                }
            }
            if ($bShippingMethod && isset($oRequest->shipping_address->selected_option_id)) {
                if ($oQuote->getShippingAddress()->getShippingMethod() != $oRequest->shipping_address->selected_option_id) {
                    $aReturn['changed'] = true;
                    $aReturn['message'] = $this->__('Shipping method has been changed from %s to %s.', $oRequest->shipping_address->selected_option_id, $oQuote->getShippingAddress()->getShippingMethod());
                    return $aReturn;
                }
            }
        }

        if ($bBilling) {
            if (isset($oRequest->billing_address)) {
                if ($this->__compareAddress($oQuote->getBillingAddress(), $oRequest->billing_address)) {
                    $aReturn['changed'] = true;
                    $aReturn['message'] = $this->__('Billing address has been changed.');
                    return $aReturn;
                }
            }
        }

        if ($bQuote) {
            $aReturn = $this->compareQuote($oQuote, $oRequest, $bQuoteItemOnly);
        }

        return $aReturn;
    }

    protected function _prepareDataForConfirmOrder($aRawData)
    {
        if (!isset($aRawData['quote'])
            || !isset($aRawData['order'])
        ) {
            $vMessage = Mage::helper('zipmoneypayment')->__('Incorrect API response data.');
            Mage::throwException($vMessage);
        }

        /** @var Mage_Sales_Model_Quote $oQuote */
        $aResponseData = array();
        $oQuote = $aRawData['quote'];
        $oOrder = $aRawData['order'];

        // customer data
        $oCustomer = Mage::getModel('customer/customer')->load($oOrder->getCustomerId());
        $aResponseData = $this->_addCustomerToArray($aResponseData, null, $oOrder, $oCustomer);

        // Shipping_address data
        $oShippingAddress = $oOrder->getShippingAddress();
        $aResponseData = $this->_addShippingAddressToArray($aResponseData, $oShippingAddress, false);

        // billing_address data
        $oBillingAddress = $oOrder->getBillingAddress();
        $aResponseData = $this->_addBillingAddressToArray($aResponseData, $oBillingAddress);

        // order data
        $aResponseData = $this->_addOrderToArrayByOrder($aResponseData, $oOrder);

        // URLs
        $aResponseData = $this->_addUrlsToArray($aResponseData, $oQuote);
        $aResponseData['quote_id']      = $oOrder->getQuoteId();

        return $aResponseData;
    }

    protected function _prepareDataForOrderStatus($aRawData)
    {
        if (!isset($aRawData['quote'])
            || !isset($aRawData['order'])
        ) {
            $vMessage = Mage::helper('zipmoneypayment')->__('Incorrect API response data.');
            Mage::throwException($vMessage);
        }

        /** @var Mage_Sales_Model_Quote $oQuote */
        $aResponseData = array();
        $oQuote = $aRawData['quote'];
        $oOrder = $aRawData['order'];

        // customer data
        $oCustomer = Mage::getModel('customer/customer')->load($oOrder->getCustomerId());
        $aResponseData = $this->_addCustomerToArray($aResponseData, null, $oOrder, $oCustomer);

        // Shipping_address data
        $oShippingAddress = $oOrder->getShippingAddress();
        $aResponseData = $this->_addShippingAddressToArray($aResponseData, $oShippingAddress, false);

        // billing_address data
        $oBillingAddress = $oOrder->getBillingAddress();
        $aResponseData = $this->_addBillingAddressToArray($aResponseData, $oBillingAddress);

        // order data
        $aResponseData = $this->_addOrderToArrayByOrder($aResponseData, $oOrder, true);

        // URLs
        $aResponseData = $this->_addUrlsToArray($aResponseData, $oQuote);
        $aResponseData['quote_id']      = $oOrder->getQuoteId();

        return $aResponseData;
    }

    /**
     * Prepare data (array) for response to zipMoney
     *
     * @param $vAction
     * @param array $aRawData
     * @return array
     */
    public function prepareDataForResponse($vAction, $aRawData = array())
    {
        $aResponseData = array();
        switch ($vAction) {
            case self::ACTION_RESPONSE_TYPE_GET_SHIPPING_METHODS:
                $aResponseData = $this->_prepareDataForGetShippingMethods($aRawData, $vAction);
                break;
            case self::ACTION_RESPONSE_TYPE_GET_QUOTE_DETAILS:
                $aResponseData = $this->_prepareDataForGetQuoteDetails($aRawData, $vAction);
                break;
            case self::ACTION_RESPONSE_TYPE_CONFIRM_SHIPPING_METHOD:
                $aResponseData = $this->_prepareDataForConfirmShippingMethod($aRawData, $vAction);
                break;
            case self::ACTION_RESPONSE_TYPE_CONFIRM_ORDER:
            case self::ACTION_RESPONSE_TYPE_FINALISE_ORDER:
                $aResponseData = $this->_prepareDataForConfirmOrder($aRawData);
                break;
            case self::ACTION_RESPONSE_TYPE_ORDER_STATUS:
                $aResponseData = $this->_prepareDataForOrderStatus($aRawData);
                break;
            default:
                // should never go to here
                break;
        }

        $aResponseData = $this->_addApiKeysToArray($aResponseData);
        return $aResponseData;
    }


}