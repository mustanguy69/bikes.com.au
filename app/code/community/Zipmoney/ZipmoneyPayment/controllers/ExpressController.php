<?php
/**
 * @category  Aligent
 * @package   zipmoney
 * @author    Andi Han <andi@aligent.com.au>
 * @copyright 2014 Aligent Consulting.
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.aligent.com.au/
 */

class Zipmoney_ZipmoneyPayment_ExpressController extends Zipmoney_ZipmoneyPayment_Controller_Express_Abstract
{
    /**
     * Checkout mode type
     * @var string
     */
    protected $_checkoutType = 'zipmoneypayment/express_checkout';


    /**
     * Ajax Call zipMoney Quote endpoint for redirect url, and return the url
     */
    public function redirectAction()
    {
        $oHelper = Mage::helper('zipmoneypayment');
        $this->_logging->writeLog($oHelper->__('Calling redirectAction'), Zend_Log::DEBUG);

        if (!$this->getRequest()->isAjax()) {
            $this->_errorHandling(null, $oHelper->__('Can not accept the request.'), null, false, true, Mage_Api2_Model_Server::HTTP_NOT_ACCEPTABLE);
            return;
        }

        try {
            $vRedirectUrl = $this->getRedirectUrl(Zipmoney_ZipmoneyPayment_Helper_Quote::QUOTE_CHECKOUT_SOURCE_CHECKOUT);

            if($vRedirectUrl) {
                $aData = array(
                    'redirect_url'      => $vRedirectUrl,
                    'error_messages'    => $oHelper->__('Redirecting to zipMoney.')
                );
                $this->_setResponseData($aData, Mage_Api2_Model_Server::HTTP_OK);
                $this->_logging->writeLog($oHelper->__('Successful to get redirect url, which is ') . $vRedirectUrl, Zend_Log::INFO);
            } else {
                $aData = array(
                    'redirect_url'      => '',
                    'error_messages'    => $oHelper->__('Can not redirect to zipMoney.')
                );
                $this->_setResponseData($aData, Mage_Api2_Model_Server::HTTP_INTERNAL_ERROR);
                $this->_logging->writeLog($oHelper->__('Failed to get redirect url.'), Zend_Log::WARN);
            }
        } catch (Mage_Core_Exception $e) {
            $this->_errorHandling($e, $oHelper->__('An error occurred during getting redirect url.'), null, false, true);
        } catch (Exception $e) {
            $this->_errorHandling($e, $oHelper->__('An error occurred during getting redirect url.'), null, false, true);
        }
    }

    protected function _setGuestInfoToQuote($oQuote, $oRequest)
    {
        $bUpdate = false;
        if (!$oQuote->getCustomerEmail() && isset($oRequest->consumer->email)) {
            $bUpdate = true;
            $oQuote->setCustomerEmail($oRequest->consumer->email);
        }
        if (!$oQuote->getCustomerFirstname() && isset($oRequest->consumer->first_name)) {
            $bUpdate = true;
            $oQuote->setCustomerFirstname($oRequest->consumer->first_name);
        }
        if (!$oQuote->getCustomerLastname() && isset($oRequest->consumer->last_name)) {
            $bUpdate = true;
            $oQuote->setCustomerLastname($oRequest->consumer->last_name);
        }
        if ($bUpdate) {
            $oQuote->save();
        }
    }

    /**
     * Submit the order (create Pending order). Perform quote check, and place order.
     */
    public function confirmOrderAction()
    {
        $vAction = Zipmoney_ZipmoneyPayment_Helper_Api::ACTION_RESPONSE_TYPE_CONFIRM_ORDER;
        $oHelper = Mage::helper('zipmoneypayment');
        $oOrderHelper = Mage::helper('zipmoneypayment/order');
        $oQuoteHelper = Mage::helper('zipmoneypayment/quote');
        $oApiHelper = Mage::helper('zipmoneypayment/api');
        $oRequest = $this->_getRequestBody();
        $this->_logging->writeLog($oHelper->__('Calling confirmOrderAction'), Zend_Log::DEBUG);

        try {
            if (!$this->_checkRequestAvailability($vAction, $oRequest)) {
                $this->_logging->writeLog(json_encode($oRequest), Zend_Log::DEBUG);
                return;
            }

            $iQuoteId = isset($oRequest->quote_id) ?  $oRequest->quote_id : null;
            $oQuote = $oQuoteHelper->getQuote($iQuoteId);       // Get the quote from database instead of session

            /**
             * check quote details
             */
            $check_shipping = true;

            if(isset($oRequest->shipping_address) && $oRequest->shipping_address->selected_option_id == 'not_required')
                $check_shipping = false;

            $aResult = Mage::helper('zipmoneypayment/api')->compareQuoteInRequestAndDb($oQuote, $oRequest, true, $check_shipping, true, false, true, false);
            $bChanged = isset($aResult['changed']) ? $aResult['changed'] : null;
            $vMessage = isset($aResult['message']) ? $aResult['message'] : '';
            if ($bChanged === null) {
                $this->_logging->writeLog(json_encode($oRequest), Zend_Log::DEBUG);
                $vMessage = $oHelper->__('Incorrect return value from compareQuoteInRequestAndDb.');
                throw Mage::exception('Zipmoney_ZipmoneyPayment', $vMessage);
            }
            if ($bChanged) {
                $this->_logging->writeLog(json_encode($oRequest), Zend_Log::DEBUG);
                $vMessage = $oHelper->__('The shopping cart details may have been changed. ') . $vMessage;
                throw Mage::exception('Zipmoney_ZipmoneyPayment', $vMessage);
            }

            $this->_logging->writeLog(json_encode($oRequest), Zend_Log::DEBUG);

            /**
             * Activate quote if needed
             */
            $oQuoteHelper->activateQuote($oQuote);
            $this->_setGuestInfoToQuote($oQuote, $oRequest);

            /**
             * zipMoney needs to manage the billing address
             */
            $this->_saveQuoteAddress($oQuote->getBillingAddress(), $oRequest);  

            /**
             * place order
             */
            $oOrder = $oOrderHelper->confirmOrder($iQuoteId);

            $aRawData = array(
                'quote' => $oQuote,
                'order' => $oOrder
                );
            $aResponseData = $oApiHelper->prepareDataForResponse($vAction, $aRawData);
            if(!$aResponseData) {
                $this->_logging->writeLog(json_encode($oRequest), Zend_Log::DEBUG);
                $this->_errorHandling(null, $oHelper->__('Failed to get response data.'), null, false, true);
                return;
            }
            $this->_setResponseData($aResponseData, Mage_Api2_Model_Server::HTTP_OK);
            $this->_logging->writeLog($oHelper->__('Successful to place the order.'), Zend_Log::INFO);
            if ($oOrder) {
                $this->_logging->writeLog($oHelper->__('Order id: ' . $oOrder->getIncrementId()), Zend_Log::DEBUG);
            }
        } catch (Zipmoney_ZipmoneyPayment_Exception $e) {
            $this->_logging->writeLog(json_encode($oRequest), Zend_Log::DEBUG);
            $this->_errorHandling($e, $oHelper->__('An error occurred during placing the order.'), null, false, true);
            return;
        } catch (Mage_Core_Exception $e) {
            $this->_logging->writeLog(json_encode($oRequest), Zend_Log::DEBUG);
            $this->_errorHandling($e, $oHelper->__('An error occurred during placing the order.'), null, false, true);
            return;
        } catch (Exception $e) {
            $this->_logging->writeLog(json_encode($oRequest), Zend_Log::DEBUG);
            $this->_errorHandling($e, $oHelper->__('An error occurred during placing the order.'), null, false, true);
            return;
        }
    }

    /**
     * @param $oQuote
     * @param $oOrder
     * @return Mage_Checkout_Model_Session
     */
    protected function _updateSession($oQuote, $oOrder)
    {
        /** @var Mage_Sales_Model_Quote $oQuote */
        /** @var Mage_Sales_Model_Order $oOrder */
        // Load order/quote into session
        $oQuoteHelper = Mage::helper('zipmoneypayment/quote');
        $oSession = $oQuoteHelper->getCheckoutSession();
        if ($oQuote->getIsActive()) {
            $oSession->replaceQuote($oQuote);
        }
        $oSession->setLastSuccessQuoteId($oQuote->getId());
        $oSession->setLastQuoteId($oQuote->getId());
        $oSession->setLastOrderId($oOrder->getId());
        $oSession->setLastRealOrderId($oOrder->getIncrementId());

        if (!$oQuote->getCustomerIsGuest() && $oQuote->getCustomerId()) {
            // login the customer automatically.
            Mage::getSingleton('customer/session')->loginById($oQuote->getCustomerId());
        }
        return $oSession;
    }


    /**
     * Redirect to success page
     */
    public function successAction()
    {
        $vAction = Zipmoney_ZipmoneyPayment_Helper_Api::ACTION_RESPONSE_TYPE_SUCCESS_PAGE;
        $oHelper = Mage::helper('zipmoneypayment');
        $oQuoteHelper = Mage::helper('zipmoneypayment/quote');
        $oOrderHelper = Mage::helper('zipmoneypayment/order');
        $oRequest = $this->getRequest();
        $this->_logging->writeLog($oHelper->__('Calling successAction'), Zend_Log::DEBUG);
        try {
            $iQuoteId       = $oRequest->getParam('quote_id');
            $vToken         = $oRequest->getParam('token');
            $this->_logging->writeLog(Mage::helper('core/url')->getCurrentUrl(), Zend_Log::DEBUG);
            $this->_logging->writeLog($oHelper->__('QuoteId: ' . $iQuoteId . ' vToken: ' . $vToken), Zend_Log::DEBUG);

            $oQuote = $oQuoteHelper->getQuote($iQuoteId);
            if (!$this->_checkRequestAvailability($vAction, $oRequest, $oQuote, $vToken)) {
                $this->_logging->writeLog(json_encode($oRequest->getParams()), Zend_Log::DEBUG);
                $vMessage = $oHelper->__('The request is unavailable for success page.');
                throw Mage::exception('Zipmoney_ZipmoneyPayment', $vMessage);
            }
            $vReservedId = $oQuote->getReservedOrderId();
            $oOrder = Mage::getModel('sales/order')->loadByIncrementId($vReservedId);

            if (!$oOrder || !$oOrder->getId()) {
                $this->_logging->writeLog(json_encode($oRequest->getParams()), Zend_Log::DEBUG);
                $this->_logging->writeLog($oHelper->__('Order increment id: ' . $vReservedId . '.'), Zend_Log::DEBUG);
                $vFrontMessage = $oHelper->__('Can not find the order (inc_id: %s).', $vReservedId);
                $this->_errorHandling(null, $vFrontMessage, null, false, true);
                throw Mage::exception('Zipmoney_ZipmoneyPayment', $vFrontMessage);
            }

            $vOrderState = $oOrder->getState();

            if ($vOrderState != Mage_Sales_Model_Order::STATE_NEW
                && $vOrderState != Mage_Sales_Model_Order::STATE_PROCESSING
                && $vOrderState != Mage_Sales_Model_Order::STATE_PENDING_PAYMENT
                ) {
                $this->_logging->writeLog(json_encode($oRequest->getParams()), Zend_Log::DEBUG);
                $this->_logging->writeLog($oHelper->__('Order increment id: ' . $vReservedId . '.'), Zend_Log::DEBUG);
                $vFrontMessage = $oHelper->__('Invalid order state (%s).', $vOrderState);
                $this->_errorHandling(null, $vFrontMessage, null, false, true);
                throw Mage::exception('Zipmoney_ZipmoneyPayment', $vFrontMessage);
            }

            $this->_updateSession($oQuote, $oOrder);
            $this->getResponse()->setRedirect(Mage::getUrl('checkout/onepage/success'));
            $this->_logging->writeLog($oHelper->__('Successful to redirect to success page.'), Zend_Log::INFO);
        } catch (Mage_Core_Exception $e) {
            $this->_logging->writeLog(json_encode($oRequest->getParams()), Zend_Log::ERR);
            $this->_errorHandling($e, $oHelper->__('An error occurred during redirecting to success page.'), null, false, true, Mage_Api2_Model_Server::HTTP_INTERNAL_ERROR, true);
            return;
        } catch (Exception $e) {
            $this->_logging->writeLog(json_encode($oRequest->getParams()), Zend_Log::ERR);
            $this->_errorHandling($e, $oHelper->__('An error occurred during redirecting to success page.'), null, false, true, Mage_Api2_Model_Server::HTTP_INTERNAL_ERROR, true);
        }
    }

    /**
     * Request for order details
     */
    public function orderStatusAction()
    {
        $vAction = Zipmoney_ZipmoneyPayment_Helper_Api::ACTION_RESPONSE_TYPE_ORDER_STATUS;
        $oHelper = Mage::helper('zipmoneypayment');
        $oQuoteHelper = Mage::helper('zipmoneypayment/quote');
        $oApiHelper = Mage::helper('zipmoneypayment/api');
        $oRequest = $this->_getRequestBody();
        try {
            if (!$this->_checkRequestAvailability($vAction, $oRequest)) {
                $this->_logging->writeLog(json_encode($oRequest), Zend_Log::DEBUG);
                return;
            }
            $iQuoteId = isset($oRequest->quote_id) ?  $oRequest->quote_id : null;
            $oQuote = $oQuoteHelper->getQuote($iQuoteId);

            $vReservedId = $oQuote->getReservedOrderId();
            $vOrderIncId = isset($oRequest->order->id) ?  $oRequest->order->id : null;
            if (!$vOrderIncId || ($vReservedId != $vOrderIncId)) {
                $this->_logging->writeLog(json_encode($oRequest), Zend_Log::DEBUG);
                $vFrontMessage = $oHelper->__('Incorrect order id.');
                $this->_errorHandling(null, $vFrontMessage, null, false, true);
                return;
            }

            $oOrder = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('increment_id', $vOrderIncId)->getFirstItem();
            $aRawData = array(
                'quote' => $oQuote,
                'order' => $oOrder
            );
            $aResponseData = $oApiHelper->prepareDataForResponse($vAction, $aRawData);
            if(!$aResponseData) {
                $this->_logging->writeLog(json_encode($oRequest), Zend_Log::DEBUG);
                $vFrontMessage = $oHelper->__('Failed to get response data.');
                $this->_errorHandling(null, $vFrontMessage, null, false, true);
                return;
            }
            $this->_setResponseData($aResponseData, Mage_Api2_Model_Server::HTTP_OK);
        } catch (Zipmoney_ZipmoneyPayment_Exception $e) {
            $this->_logging->writeLog(json_encode($oRequest), Zend_Log::ERR);
            $vFrontMessage = $oHelper->__('An error occurred during requesting order status.');
            $this->_errorHandling($e, $vFrontMessage, null, false, true);
            return;
        } catch (Mage_Core_Exception $e) {
            $this->_logging->writeLog(json_encode($oRequest), Zend_Log::ERR);
            $vFrontMessage = $oHelper->__('An error occurred during requesting order status.');
            $this->_errorHandling($e, $vFrontMessage, null, false, true);
            return;
        } catch (Exception $e) {
            $this->_logging->writeLog(json_encode($oRequest), Zend_Log::ERR);
            $vFrontMessage = $oHelper->__('An error occurred during requesting order status.');
            $this->_errorHandling($e, $vFrontMessage, null, false, true);
            return;
        }
    }


    /**
     * Redirect to under review page
     */
    public function underReviewAction()
    {
        $vAction = Zipmoney_ZipmoneyPayment_Helper_Api::ACTION_RESPONSE_TYPE_UNDER_REVIEW_PAGE;
        $oHelper = Mage::helper('zipmoneypayment');
        $oQuoteHelper = Mage::helper('zipmoneypayment/quote');
        $oRequest = $this->getRequest();
        $this->_logging->writeLog($oHelper->__('Calling underReviewAction'), Zend_Log::DEBUG);
        try {
            $iQuoteId       = $oRequest->getParam('quote_id');
            $vToken         = $oRequest->getParam('token');

            $oQuote = $oQuoteHelper->getQuote($iQuoteId);
            if (!$this->_checkRequestAvailability($vAction, $oRequest, $oQuote, $vToken)) {
                $this->_logging->writeLog(json_encode($oRequest->getParams()), Zend_Log::DEBUG);
                $vMessage = $oHelper->__('The request is unavailable for under review page.');
                throw Mage::exception('Zipmoney_ZipmoneyPayment', $vMessage);
            }
            $oSession = $oQuoteHelper->getCheckoutSession();
            $oSession->clear();
            $oQuoteHelper->deactivateQuote($oQuote);
            $this->loadLayout();
            $this->renderLayout();
            $this->_logging->writeLog($oHelper->__('Successful to redirect to under review page.'), Zend_Log::INFO);
        } catch (Mage_Core_Exception $e) {
            $this->_logging->writeLog(json_encode($oRequest->getParams()), Zend_Log::ERR);
            $this->_errorHandling($e, $oHelper->__('An error occurred during redirecting to under review page.'), null, false, true, Mage_Api2_Model_Server::HTTP_INTERNAL_ERROR, true);
            return;
        } catch (Exception $e) {
            $this->_logging->writeLog(json_encode($oRequest->getParams()), Zend_Log::ERR);
            $this->_errorHandling($e, $oHelper->__('An error occurred during redirecting to under review page.'), null, false, true, Mage_Api2_Model_Server::HTTP_INTERNAL_ERROR, true);
        }
    }

    public function errorAction()
    {
        $oHelper = Mage::helper('zipmoneypayment');
        $oRequest = $this->getRequest();
        $this->_logging->writeLog($oHelper->__('Calling errorAction'), Zend_Log::DEBUG);
        try {
            $this->loadLayout()
                ->_initLayoutMessages('checkout/session')
                ->_initLayoutMessages('catalog/session')
                ->_initLayoutMessages('customer/session')
            ;
            $this->renderLayout();
            $this->_logging->writeLog($oHelper->__('Successful to redirect to error page.'), Zend_Log::INFO);
        } catch (Exception $e) {
            $this->_logging->writeLog(json_encode($oRequest->getParams()), Zend_Log::ERR);
            $this->_errorHandling($e, $oHelper->__('An error occurred during redirecting to error page.'), null, false, true);
        }
    }
}