<?php

class Neklo_Instagram_ApiController extends Mage_Core_Controller_Front_Action
{
    protected $_api = null;

    public function connectAction()
    {
        $code = $this->getRequest()->getParam('code', null);
        if ($code === null) {
            $this->loadLayout();
            $this->renderLayout();
            $this->_getAdminSession()->addError(
                Mage::helper('neklo_instagram')->__('Incorrect Instagram authorization code.')
            );
            return $this;
        }

        try {
            $accessToken = $this->_getApi()->getOAuthToken($code);
            if ($accessToken->code === 400) {
                throw new Exception($accessToken->error_message);
            }
            $accessToken = $accessToken->access_token;
        } catch (Exception $e) {
            $this->loadLayout();
            $this->renderLayout();
            $this->_getAdminSession()->addError(
                Mage::helper('neklo_instagram')->__($e->getMessage())
            );
            return $this;
        }

        if (!$accessToken) {
            $this->loadLayout();
            $this->renderLayout();
            $this->_getAdminSession()->addError(
                Mage::helper('neklo_instagram')->__('Incorrect Instagram authorization code.')
            );
            return $this;
        }

        $this->_getConfig()->connect($accessToken);
        $this->loadLayout();
        $this->renderLayout();
        $this->_getAdminSession()->addSuccess(
            Mage::helper('neklo_instagram')->__('Instagram connect is successful.')
        );
        return $this;
    }

    /**
     * @return Neklo_Instagram_Model_Instagram_Api
     */
    protected function _getApi()
    {
        if ($this->_api === null) {
            $this->_api = $this->_api = Mage::getModel(
                'neklo_instagram/instagram_api',
                array(
                    'apiKey'      => $this->_getConfig()->getClientId(),
                    'apiSecret'   => $this->_getConfig()->getClientSecret(),
                    'apiCallback' => $this->_getConfig()->getRedirectUrl(),
                )
            );
        }
        return $this->_api;
    }

    /**
     * @return Neklo_Instagram_Helper_Config
     */
    protected function _getConfig()
    {
        return Mage::helper('neklo_instagram/config');
    }

    /**
     * Retrieve adminhtml session model object
     *
     * @return Mage_Adminhtml_Model_Session
     */
    protected function _getAdminSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }
}