<?php
/**
 * Plumrocket Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End-user License Agreement
 * that is available through the world-wide-web at this URL:
 * http://wiki.plumrocket.net/wiki/EULA
 * If you are unable to obtain it through the world-wide-web, please
 * send an email to support@plumrocket.com so we can send you a copy immediately.
 *
 * @package     Plumrocket_Newsletterpopup
 * @copyright   Copyright (c) 2018 Plumrocket Inc. (http://www.plumrocket.com)
 * @license     http://wiki.plumrocket.net/wiki/EULA  End-user License Agreement
 */


class Plumrocket_Newsletterpopup_Model_SubscriberEncoded extends Mage_Core_Model_Abstract
{
    private $_popup = NULL;
    protected $_fieldsForEmail;
    protected $fieldsTag = null;

    protected function _construct()
    {
        parent::_construct();
        $this->_fieldsForEmail = array('firstname', 'middlename', 'lastname');
    }

    public function subscribe($email, $data)
    {
        if (Mage::helper('newsletterpopup')->moduleEnabled()
            && ($this->_getPopup()->getId() > 0)
        ) {
            // create coupon
            $couponCode = $this->_createCoupon();
            $data['coupon'] = $couponCode;

            // subscribe to mailchimp list
            $this->_subscribeToMailchimp($email, $data);

            // send email
            if ($this->_getPopup()->getSendEmail()) {
                $this->_sendEmail($email, $couponCode, $data);
            }

            // log subscription
            $this->_addHistory(Plumrocket_Newsletterpopup_Model_Values_Action::SUBSCRIBE, $email, $couponCode);
            return true;
        }
        return false;
    }

    public function getPopup()
    {
        return $this->_getPopup();
    }

    protected function _subscribeToMailchimp($email, $data)
    {
        $model = Mage::helper('newsletterpopup/adminhtml')->getMcapi();
        if ($model) {
            $mailchimpLists = Mage::getSingleton('newsletterpopup/values_mailchimplist')->toOptionHash();
            $list = $this->_getActiveMailchimpList($data);

            if ($this->fieldsTag === null) {
                $this->fieldsTag = @json_decode(Mage::getStoreConfig('newsletterpopup/mailchimp/fields_tag'));
            }
            $mergeVars = array();
            foreach ($this->fieldsTag as $key => $tag) {
                if(isset($data[$key])) {
                    $mergeVars[$tag] = $data[$key];
                }
            }

            foreach ($list as $id) {
                if (array_key_exists($id, $mailchimpLists)) {
                    $model->listSubscribe($id, $email, $mergeVars, 'html', (int)Mage::getStoreConfig('newsletterpopup/mailchimp/send_email') === 1);
                }
            }
        }
    }

    protected function _getActiveMailchimpList($data)
    {
        $list = array();
        if ($this->_getPopup()->getSubscriptionMode() == Plumrocket_Newsletterpopup_Model_Values_SubscriptionMode::ALL_LIST) {
            $list = Mage::getSingleton('newsletterpopup/values_mailchimplist')->toOptionHash();
            $list = array_keys($list);
        } elseif ($this->_getPopup()->getSubscriptionMode() == Plumrocket_Newsletterpopup_Model_Values_SubscriptionMode::ALL_SELECTED_LIST) {
            $list = Mage::helper('newsletterpopup')->getPopupMailchimpListKeys($this->_getPopup()->getId(), true);
        } elseif (isset($data['mailchimp_list'])) {
            if (!is_array($data['mailchimp_list'])) {
                $data['mailchimp_list'] = array($data['mailchimp_list']);
            }
            $list = $data['mailchimp_list'];
        }
        return $list;
    }

    protected function _createCoupon()
    {
        // create coupon
        $popup = $this->_getPopup();
        $rule = $popup->getCoupon();
        $couponCode = '';
        if ($rule && $rule->getId() && $rule->getIsActive()) {
            $popupData = $popup->getData();
            $ruleData = $rule->getData();

            if ($ruleData['coupon_type'] == Mage_SalesRule_Model_Rule::COUPON_TYPE_SPECIFIC) {
                if ($ruleData['use_auto_generation']) {
                    $generator = Mage::getModel('newsletterpopup/massgenerator');

                    $data = array(
                        'rule_id'           => $rule->getId(),

                        'qty'               => 1,
                        'length'            => $popupData['code_length'],
                        'format'            => $popupData['code_format'],
                        'dash'              => $popupData['code_dash'],
                        'prefix'            => $popupData['code_prefix'],
                        'suffix'            => $popupData['code_suffix'],

                        'uses_per_customer' => $ruleData['uses_per_customer'],
                        'uses_per_coupon'   => $ruleData['uses_per_coupon'],
                        'to_date'           => $ruleData['to_date']
                    );

                    if ($couponExpDate = $popup->getCouponExpDateTime()) {
                        $data['np_expiration_date'] = $couponExpDate;
                        $popup->setData(
                            'rule_coupon_expiration_date',
                            $popup->getCouponExpLocaleDateTime($couponExpDate)
                        );
                    }

                    if ($generator->validateData($data)) {
                        $generator->setData($data);
                        $couponCode = $generator->generateOne()->getCode();
                    }
                } else {
                    $couponCode = $ruleData['coupon_code'];
                }
            } elseif ($ruleData['coupon_type'] == Mage_SalesRule_Model_Rule::COUPON_TYPE_AUTO) {
                // not send type AUTO.
            }
        }

        $popup->setData('rule_coupon_code', $couponCode);

        return $couponCode;
    }

    protected function _sendEmail($email, $couponCode, $data = false)
    {
        $customerName = '';
        if ($data) {
            foreach ($this->_fieldsForEmail as $key) {
                if (!empty($data[$key])) {
                    $customerName .= $data[$key] . ' ';
                }
            }
            $customerName = trim($customerName);
        }
        if (!$customerName) {
            $customerName = (string)Mage::helper('newsletterpopup')->__('Visitor');
        }
        // Send email
        Mage::getModel('core/email_template')
            ->sendTransactional(
                $this->_getPopup()->getEmailTemplate(),
                'support',
                $email,
                $customerName,
                array(
                    'code'  => $couponCode,
                    'coupon_expiration_date' => $this->_getPopup()->getData('rule_coupon_expiration_date')
                )
            );
    }

    public function getAdditionalData($data)
    {
        $resData = array();
        if (Mage::helper('newsletterpopup')->moduleEnabled() && $data) {
            $fieldKeys = Mage::helper('newsletterpopup')->getPopupFormFieldsKeys($this->_getPopup()->getId(), true);
            foreach ($fieldKeys as $key) {
                if (array_key_exists($key, $data) && $key != 'email') {
                    $resData['subscriber_' . $key] = $data[$key];
                }
            }
        }
        return $resData;
    }

    protected function _getHoldItem($email)
    {
        return Mage::getSingleton('core/resource')->getConnection('newsletterpopup_read')
            ->fetchRow(sprintf("SELECT * FROM %s WHERE `email` = '%s'",
                Mage::getSingleton('core/resource')->getTableName('newsletterpopup_hold'),
                $email
            ));
    }

    protected function _insertHoldItem($email, $data)
    {
        Mage::getSingleton('core/resource')->getConnection('newsletterpopup_write')
            ->query(sprintf("INSERT INTO `%s` (`email`, `popup_id`, `lists`) VALUES ('%s', '%u', '%s')",
                Mage::getSingleton('core/resource')->getTableName('newsletterpopup_hold'),
                $email,
                $this->_getPopup()->getId(),
                implode(',', $this->_getActiveMailchimpList($data))
            ));
    }

    protected function _deleteHoldItem($email)
    {
        return Mage::getSingleton('core/resource')->getConnection('newsletterpopup_write')
            ->query(sprintf("DELETE FROM %s WHERE `email` = '%s'",
                Mage::getSingleton('core/resource')->getTableName('newsletterpopup_hold'),
                $email
            ));
    }

    public function holdSubscribe($email, $data)
    {
        if ($this->_getHoldItem($email)) {
            return false;
        }
        $this->_insertHoldItem($email, $data);
        return $this;
    }

    public function releaseSubscribe($subscriber)
    {
        $email = $subscriber->getEmail();

        if ($this->fieldsTag === null) {
            $this->fieldsTag = @json_decode(Mage::getStoreConfig('newsletterpopup/mailchimp/fields_tag'));
        }

        $data = $this->_getHoldItem($email);

        $subscriberData = array();
        foreach ($subscriber->getData() as $key => $value) {
            if (array_key_exists($key = str_replace('subscriber_', '', $key), $this->fieldsTag)) {
                $subscriberData[$key] = $value;
            }
        }
        $subscriberData['mailchimp_list'] = explode(',', $data['lists']);

        if (Mage::helper('newsletterpopup')->moduleEnabled() && $data) {
            $this->_popup = Mage::helper('newsletterpopup')->getPopupById($data['popup_id']);
            $this->subscribe(
                $email,
                $subscriberData
            );
            $this->_deleteHoldItem($email);
        }
        return $this;
    }

    public function tryRegisterCustomer($customer, $controller)
    {
        if (! Mage::helper('newsletterpopup')->moduleEnabled()) {
            return false;
        }

        if ($this->_getPopup()->getSignupMethod() != Plumrocket_Newsletterpopup_Model_Values_SignupMethod::SIGNUP_AND_REGISTER) {
            return false;
        }
        $session = Mage::getSingleton('customer/session');
        $customer->save();

        Mage::dispatchEvent('customer_register_success',
            array('account_controller' => $controller, 'customer' => $customer)
        );

        if ($customer->isConfirmationRequired() && Mage::getStoreConfig(Mage_Newsletter_Model_Subscriber::XML_PATH_CONFIRMATION_FLAG) != 1) {
            $customer->sendNewAccountEmail(
                'confirmation',
                Mage::helper('customer/data')->getAccountUrl(),
                Mage::app()->getStore()->getId()
            );
            $session->addSuccess(Mage::helper('newsletterpopup')->__('Account confirmation is required. Please, check your email for the confirmation link. To resend the confirmation email please <a href="%s">click here</a>.', Mage::helper('customer')->getEmailConfirmationUrl($customer->getEmail())));
        } else {
            $customer->sendNewAccountEmail(
                'registered',
                '',
                Mage::app()->getStore()->getId()
            );
            $session->setCustomerAsLoggedIn($customer);
            $session->renewSession();
        }

        return $customer->getId();
    }

    public function validateCustomer($data)
    {
        if (! Mage::helper('newsletterpopup')->moduleEnabled()) {
            return false;
        }
        $customer = Mage::getModel('customer/customer')->setId(null);
        $customer->getGroupId();
        $customer->setData($data);

        $fieldKeys = Mage::helper('newsletterpopup')->getPopupFormFieldsKeys($this->_getPopup()->getId(), true);

        if (!in_array('password', $fieldKeys)) {
            $customer->setPassword( $customer->generatePassword() );
        }
        return ($this->_validateCustomer($customer, $fieldKeys))? $customer: false;
    }

    protected function _validateCustomer($customer, $fieldKeys)
    {
        $session = Mage::getSingleton('customer/session');
        $success = true;

        if (in_array('firstname', $fieldKeys)) {
            if (!Zend_Validate::is( trim($customer->getFirstname()) , 'NotEmpty')) {
                $session->addError(Mage::helper('customer')->__('The first name cannot be empty.'));
                return $success = false;
            }
        }

        if (in_array('lastname', $fieldKeys)) {
            if (!Zend_Validate::is( trim($customer->getLastname()) , 'NotEmpty')) {
                $session->addError(Mage::helper('customer')->__('The last name cannot be empty.'));
                return $success = false;
            }
        }

        if (!Zend_Validate::is($customer->getEmail(), 'EmailAddress')) {
            $session->addError(Mage::helper('customer')->__('Invalid email address "%s".', $customer->getEmail()));
            return $success = false;
        }

        if (in_array('confirm_email', $fieldKeys)) {
            if ($customer->getEmail() != $customer->getConfirmEmail()) {
                $session->addError(Mage::helper('customer')->__('Please make sure your emails match.'));
                return $success = false;
            }
        }

        $password = $customer->getPassword();
        if (!$customer->getId() && !Zend_Validate::is($password , 'NotEmpty')) {
            $session->addError(Mage::helper('customer')->__('The password cannot be empty.'));
            return $success = false;
        }
        if (strlen($password) && !Zend_Validate::is($password, 'StringLength', array(6))) {
            $session->addError(Mage::helper('customer')->__('The minimum password length is %s', 6));
            return $success = false;
        }
        if (in_array('confirm_password', $fieldKeys)) {
            $confirmation = $customer->getConfirmation();
            if ($password != $confirmation) {
                $session->addError(Mage::helper('customer')->__('Please make sure your passwords match.'));
                return $success = false;
            }
        }

        $entityType = Mage::getSingleton('eav/config')->getEntityType('customer');

        if (in_array('dob', $fieldKeys)) {
            $attribute = Mage::getModel('customer/attribute')->loadByCode($entityType, 'dob');
            if ($attribute->getIsRequired() && '' == trim($customer->getDob())) {
                $session->addError(Mage::helper('customer')->__('The Date of Birth is required.'));
                return $success = false;
            }
        }

        if (in_array('taxvat', $fieldKeys)) {
            $attribute = Mage::getModel('customer/attribute')->loadByCode($entityType, 'taxvat');
            if ($attribute->getIsRequired() && '' == trim($customer->getTaxvat())) {
                $session->addError(Mage::helper('customer')->__('The TAX/VAT number is required.'));
                return $success = false;
            }
        }

        if (in_array('gender', $fieldKeys)) {
            $attribute = Mage::getModel('customer/attribute')->loadByCode($entityType, 'gender');
            if ($attribute->getIsRequired() && '' == trim($customer->getGender())) {
                $session->addError(Mage::helper('customer')->__('Gender is required.'));
                return $success = false;
            }
        }

        return $success;
    }

    public function validateAddress($data)
    {
        if (! Mage::helper('newsletterpopup')->moduleEnabled()) {
            return false;
        }
        $address = Mage::getModel('customer/address')->setId(null);
        $address->setData($data);

        $fieldKeys = Mage::helper('newsletterpopup')->getPopupFormFieldsKeys($this->_getPopup()->getId(), true);
        return ($this->_validateAddress($address, $fieldKeys))? $address: false;
    }

    protected function _validateAddress($address, $fieldKeys)
    {
        $session = Mage::getSingleton('customer/session');
        $success = true;

        if (in_array('street', $fieldKeys)) {
            if (!Zend_Validate::is($address->getStreet(1), 'NotEmpty')) {
                $session->addError(Mage::helper('customer')->__('Please enter the street.'));
                return $success = false;
            }
        }

        if (in_array('city', $fieldKeys)) {
            if (!Zend_Validate::is($address->getCity(), 'NotEmpty')) {
                $session->addError(Mage::helper('customer')->__('Please enter the city.'));
                return $success = false;
            }
        }

        if (in_array('telephone', $fieldKeys)) {
            if (!Zend_Validate::is($address->getTelephone(), 'NotEmpty')) {
                $session->addError(Mage::helper('customer')->__('Please enter the telephone number.'));
                return $success = false;
            }
        }

        if (in_array('postcode', $fieldKeys)) {
            $_havingOptionalZip = Mage::helper('directory')->getCountriesWithOptionalZip();
            if (!in_array($address->getCountryId(), $_havingOptionalZip) && !Zend_Validate::is($address->getPostcode(), 'NotEmpty')) {
                $session->addError(Mage::helper('customer')->__('Please enter the zip/postal code.'));
                return $success = false;
            }
        }

        if (in_array('country_id', $fieldKeys)) {
            if (!Zend_Validate::is($address->getCountryId(), 'NotEmpty')) {
                $session->addError(Mage::helper('customer')->__('Please enter the country.'));
                return $success = false;
            }
        }

        if (in_array('region', $fieldKeys)) {
            if ($address->getCountryModel()->getRegionCollection()->getSize()
                   && !Zend_Validate::is($address->getRegionId(), 'NotEmpty')) {
                $session->addError(Mage::helper('customer')->__('Please enter the state/province.'));
                return $success = false;
            }
        }

        if (in_array('agreement', $fieldKeys)) {
            if (!Zend_Validate::is($address->getAgreement(), 'NotEmpty')) {
                $session->addError(Mage::helper('customer')->__('Agreement is required.'));
                return $success = false;
            }
        }

        return $success;
    }

    public function cancel()
    {
        if (! Mage::helper('newsletterpopup')->moduleEnabled()) {
            return false;
        }

        if ($this->_getPopup()->getId() > 0) {
            $this->_addHistory(Plumrocket_Newsletterpopup_Model_Values_Action::CANCEL, '');
        }
    }

    public function history($actionText)
    {
        if (! Mage::helper('newsletterpopup')->moduleEnabled()) {
            return false;
        }

        if ($this->_getPopup()->getId() > 0) {
            $this->_addHistory(Plumrocket_Newsletterpopup_Model_Values_Action::OTHER, '', '', array(
                'action_text' => $actionText
            ));
        }
    }

    private function _getPopup()
    {
        if (is_null($this->_popup)) {
            $this->_popup = Mage::helper('newsletterpopup')->getCurrentPopup();
        }

        return $this->_popup;
    }

    private function _addHistory($action, $email = '', $couponCode = '', $additional = array())
    {
        $data = array_merge(array(
            'popup_id'          => (int)$this->_getPopup()->getId(),
            'action'            => $action,
            'customer_email'    => $email,
            'coupon_code'       => $couponCode,
        ), $additional);

        Mage::getModel('newsletterpopup/history')
            ->setData($data)
            ->save();

        // increment statistic
        $this->_getPopup()->setData('views_count', $this->_getPopup()->getData('views_count') + 1);
        if ($action == Plumrocket_Newsletterpopup_Model_Values_Action::SUBSCRIBE) {
            $this->_getPopup()->setData('subscribers_count', $this->_getPopup()->getData('subscribers_count') + 1);
        }

        $this->_getPopup()->save();

        // increment previous shows
        $session = Mage::getSingleton('core/session');
        if (!$prevPopups = $session->getData('newsletterpopup_prev_popups')) {
            $prevPopups = array();
        }
        $prevPopups[$this->_getPopup()->getId()] = true;
        $session->setData('newsletterpopup_prev_popups', $prevPopups);
    }
}
