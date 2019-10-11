<?php

class Magecomp_Recaptcha_Helper_Data extends Mage_Core_Helper_Abstract
{
    const RECAPTCHA_ENABLE = 'grecaptcha/general/magecomp_select';
    const SITEKEY = 'grecaptcha/general/sitekey';
    const SECRETKEY = 'grecaptcha/general/secretkey';
    const RECAPTCHA_THEME = 'grecaptcha/general/recaptcha_theme';
    const RECAPTCHA_CATEGORY = 'grecaptcha/general/recaptcha_category';
    const XML_PATH_EMAIL_RECIPIENT  = 'contacts/email/recipient_email';
    const XML_PATH_EMAIL_SENDER     = 'contacts/email/sender_email_identity';
    const XML_PATH_EMAIL_TEMPLATE   = 'contacts/email/email_template';
    const XML_PATH_ENABLED          = 'contacts/contacts/enabled';

    public function contactmailsent($response)
    {
        try {
            $receiveName = Mage::getStoreConfig('trans_email/ident_general/name');
            $receiveEmail = Mage::getStoreConfig('contacts/email/recipient_email');

                $senderEmailIdentity = Mage::getStoreConfig('contacts/email/sender_email_identity');
                $senderEmail = Mage::getStoreConfig('trans_email/ident_'.$senderEmailIdentity.'/email');


                $storeId = Mage::app()->getStore()->getStoreId();
                $mailTemplate = Mage::getModel('core/email_template')
                    ->loadDefault('contacts_email_email_template');
				 $mailTemplate->setReplyTo($response['email']);
                 $mailTemplate->sendTransactional(
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE),
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_SENDER),
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_RECIPIENT),
                        null,
                        array('data' => $response)
                    );

        } catch (Exception $e) {
            Mage::log("Catptcha Error :" . $e->getMessage());
        }
    }

    public function isEnabled()
    {
        return (bool)Mage::getStoreConfig(self::RECAPTCHA_ENABLE);
    }

    public function getKey()
    {
        return Mage::getStoreConfig(self::SITEKEY);
    }

    public function getSecretkey()
    {
        return Mage::getStoreConfig(self::SECRETKEY);
    }

    public function showOnContact()
    {
        if ($this->isEnabled()) {
            return self::getEnabledPage(0);
        }
    }

    public function showOnReview()
    {
        if ($this->isEnabled()) {
            return self::getEnabledPage(1);
        }
    }

    public function showOnRegister()
    {
        if ($this->isEnabled()) {
            return self::getEnabledPage(2);
        }
    }

    public function showOnOnepage()
    {
        if ($this->isEnabled()) {
            return self::getEnabledPage(3);
        }
    }
    public function showOnLogin()
    {
        if ($this->isEnabled())
        {
            return self::getEnabledPage(4);
        }
    }
    public function showOnNewsletter()
    {
        if ($this->isEnabled())
        {
            return self::getEnabledPage(5);
        }
    }

    public function showadminlogin()
    {
        if ($this->isEnabled())
        {
            return self::getEnabledPage(6);
        }
    }

    public function getTheme()
    {
        if ($this->isEnabled()) {
            if ((Mage::getStoreConfig(self::RECAPTCHA_THEME)) == 0) {
                return "light";
            } else {
                return "dark";
            }
        }
    }

    public function getEnabledPage($value)
    {
        $enablepage = Mage::getStoreConfig(self::RECAPTCHA_CATEGORY);
        $enablepage = explode(",", $enablepage);
        if (in_array($value, $enablepage))
            return true;
        else
            return false;
    }

    public function Validate_captcha($response)
    {
        $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $this->getSecretkey() . '&response=' . $response);
        $responseData = json_decode($verifyResponse);
        if ($responseData->success):
            return true;
        else:
            return false;
        endif;
    }

}