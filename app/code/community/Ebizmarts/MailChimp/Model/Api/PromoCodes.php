<?php

/**
 * mailchimp-lib Magento Component
 *
 * @category  Ebizmarts
 * @package   mailchimp-lib
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Ebizmarts_MailChimp_Model_Api_PromoCodes
{
    const BATCH_LIMIT = 50;
    const TYPE_FIXED = 'fixed';
    const TYPE_PERCENTAGE = 'percentage';
    const TARGET_PER_ITEM = 'per_item';
    const TARGET_TOTAL = 'total';
    const TARGET_SHIPPING = 'shipping';

    protected $_batchId;
    protected $mailchimpHelper;
    /**
     * @var Ebizmarts_MailChimp_Model_Api_PromoRules
     */
    protected $apiPromoRules;

    public function __construct()
    {
        $this->mailchimpHelper = Mage::helper('mailchimp');
    }

    public function createBatchJson($mailchimpStoreId, $magentoStoreId)
    {
        $batchArray = array();
        $this->_batchId = 'storeid-' . $magentoStoreId . '_' . Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE . '_' . Mage::helper('mailchimp')->getDateMicrotime();
        $batchArray = array_merge($batchArray, $this->_getDeletedPromoCodes($mailchimpStoreId));
        $batchArray = array_merge($batchArray, $this->_getNewPromoCodes($mailchimpStoreId, $magentoStoreId));

        return $batchArray;
    }

    protected function _getDeletedPromoCodes($mailchimpStoreId)
    {
        $batchArray = array();
        $deletedPromoCodes = $this->makeDeletedPromoCodesCollection($mailchimpStoreId);

        $counter = 0;
        foreach ($deletedPromoCodes as $promoCode) {
            $promoCodeId = $promoCode->getRelatedId();
            $promoRuleId = $promoCode->getDeletedRelatedId();
            $batchArray[$counter]['method'] = "DELETE";
            $batchArray[$counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/promo-rules/' . $promoRuleId . '/promo-codes/' . $promoCodeId;
            $batchArray[$counter]['operation_id'] = $this->_batchId . '_' . $promoCodeId;
            $batchArray[$counter]['body'] = '';
            $this->deletePromoCodeSyncData($promoCodeId, $mailchimpStoreId);
            $counter++;
        }

        return $batchArray;
    }

    protected function _getNewPromoCodes($mailchimpStoreId, $magentoStoreId)
    {
        $batchArray = array();
        $helper = $this->getMailChimpHelper();
        $newPromoCodes = $this->makePromoCodesCollection($magentoStoreId);

        $this->joinMailchimpSyncDataWithoutWhere($newPromoCodes, $mailchimpStoreId);
        // be sure that the orders are not in mailchimp
        $websiteId = Mage::getModel('core/store')->load($magentoStoreId)->getWebsiteId();
        $autoGeneratedCondition = "salesrule.use_auto_generation = 1 AND main_table.is_primary IS NULL";
        $notAutoGeneratedCondition = "salesrule.use_auto_generation = 0 AND main_table.is_primary = 1";
        $newPromoCodes->getSelect()->where("m4m.mailchimp_sync_delta IS NULL AND website.website_id = " . $websiteId . " AND ( " . $autoGeneratedCondition . " OR " . $notAutoGeneratedCondition . ")");
        // limit the collection
        $newPromoCodes->getSelect()->limit($this->getBatchLimitFromConfig());
        $counter = 0;
        foreach ($newPromoCodes as $promoCode) {
            $codeId = $promoCode->getCouponId();
            $ruleId = $promoCode->getRuleId();
            try {
                $promoRuleSyncData = $this->getMailChimpHelper()->getEcommerceSyncDataItem($ruleId, Ebizmarts_MailChimp_Model_Config::IS_PROMO_RULE, $mailchimpStoreId);
                if (!$promoRuleSyncData->getMailchimpSyncDelta() || $promoRuleSyncData->getMailchimpSyncDelta() < $helper->getEcommMinSyncDateFlag($magentoStoreId)) {
                    $promoRuleMailchimpData = $this->getApiPromoRules()->getNewPromoRule($ruleId, $this->_batchId, $mailchimpStoreId, $magentoStoreId);
                    if (!empty($promoRuleMailchimpData)) {
                        $batchArray[$counter] = $promoRuleMailchimpData;
                        $counter++;
                    } else {
                        $this->setCodeWithParentError($mailchimpStoreId, $ruleId, $codeId);
                        continue;
                    }
                }

                if ($promoRuleSyncData->getMailchimpSyncError()) {
                    $this->setCodeWithParentError($mailchimpStoreId, $ruleId, $codeId);
                    continue;
                }

                $promoCodeData = $this->generateCodeData($promoCode, $magentoStoreId);
                $promoCodeJson = json_encode($promoCodeData);
                if (!empty($promoCodeData)) {
                    $batchArray[$counter]['method'] = "POST";
                    $batchArray[$counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/promo-rules/' . $ruleId . '/promo-codes';
                    $batchArray[$counter]['operation_id'] = $this->_batchId . '_' . $codeId;
                    $batchArray[$counter]['body'] = $promoCodeJson;

                    $this->_updateSyncData($codeId, $mailchimpStoreId, Varien_Date::now(), null, null, null, $promoCode->getToken());
                    $counter++;
                } else {
                    $error = $helper->__('Something went wrong when retrieving the information.');
                    $this->_updateSyncData($codeId, $mailchimpStoreId, Varien_Date::now(), $error);
                    continue;
                }
            } catch (Exception $e) {
                $helper->logError($e->getMessage());
            }
        }

        return $batchArray;
    }

    /**
     * @return mixed
     */
    protected function getBatchLimitFromConfig()
    {
        $batchLimit = self::BATCH_LIMIT;
        return $batchLimit;
    }

    /**
     * @return Mage_SalesRule_Model_Resource_Coupon_Collection
     */
    protected function getPromoCodeResourceCollection()
    {
        return Mage::getResourceModel('salesrule/coupon_collection');
    }

    /**
     * @param $magentoStoreId
     * @return Mage_SalesRule_Model_Resource_Coupon_Collection
     */
    public function makePromoCodesCollection($magentoStoreId)
    {
        $helper = $this->getMailChimpHelper();
        /**
         * @var Mage_SalesRule_Model_Resource_Coupon_Collection $collection
         */
        $collection = $this->getPromoCodeResourceCollection();
        $helper->addResendFilter($collection, $magentoStoreId, Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE);
        $this->addWebsiteColumn($collection);
        $this->joinPromoRuleData($collection);
        return $collection;
    }

    protected function makeDeletedPromoCodesCollection($mailchimpStoreId)
    {
        $deletedPromoCodes = Mage::getModel('mailchimp/ecommercesyncdata')->getCollection();
        $deletedPromoCodes->getSelect()->where("mailchimp_store_id = '" . $mailchimpStoreId . "' AND type = '" . Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE . "' AND mailchimp_sync_deleted = 1");
        $deletedPromoCodes->getSelect()->limit($this->getBatchLimitFromConfig());
        return $deletedPromoCodes;
    }

    /**
     * @return string
     */
    public function getSyncDataTableName()
    {
        $mailchimpTableName = $this->getCoreResource()->getTableName('mailchimp/ecommercesyncdata');

        return $mailchimpTableName;
    }

    /**
     * @param $collection
     * @param $mailchimpStoreId
     */
    public function joinMailchimpSyncDataWithoutWhere($collection, $mailchimpStoreId)
    {
        $joinCondition = "m4m.related_id = main_table.coupon_id and m4m.type = '%s' AND m4m.mailchimp_store_id = '%s'";
        $mailchimpTableName = $this->getSyncDataTableName();
        $collection->getSelect()->joinLeft(
            array("m4m" => $mailchimpTableName),
            sprintf($joinCondition, Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE, $mailchimpStoreId), array(
                "m4m.related_id",
                "m4m.type",
                "m4m.mailchimp_store_id",
                "m4m.mailchimp_sync_delta",
                "m4m.mailchimp_sync_modified"
            )
        );
    }

    /**
     * update product sync data
     *
     * @param $codeId
     * @param $mailchimpStoreId
     * @param int|null $syncDelta
     * @param int|null $syncError
     * @param int|null $syncModified
     * @param int|null $syncDeleted
     * @param int|null $token
     * @param bool $saveOnlyIfexists
     * @param null $deletedRelatedId
     */
    protected function _updateSyncData($codeId, $mailchimpStoreId, $syncDelta = null, $syncError = null, $syncModified = 0, $syncDeleted = null, $token = null, $saveOnlyIfexists = false, $deletedRelatedId = null)
    {
        $this->getMailChimpHelper()->saveEcommerceSyncData(
            $codeId,
            Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE,
            $mailchimpStoreId,
            $syncDelta,
            $syncError,
            $syncModified,
            $syncDeleted,
            $token,
            null,
            $saveOnlyIfexists,
            $deletedRelatedId
        );
    }

    protected function generateCodeData($promoCode, $magentoStoreId)
    {
        $data = array();
        $code = $promoCode->getCode();
        $data['id'] = $promoCode->getCouponId();
        $data['code'] = $code;

        //Set title as description if description null
        $data['redemption_url'] = $this->getRedemptionUrl($promoCode, $magentoStoreId);

        return $data;
    }

    /**
     * @return Ebizmarts_MailChimp_Helper_Data
     */
    protected function getMailChimpHelper()
    {
        return $this->mailchimpHelper;
    }

    protected function addWebsiteColumn($collection)
    {
        $websiteTableName = $this->getCoreResource()->getTableName('salesrule/website');
        $collection->getSelect()->joinLeft(
            array('website' => $websiteTableName),
            'main_table.rule_id=website.rule_id',
            array('*')
        );
    }

    /**
     * @param $collection
     */
    protected function joinPromoRuleData($collection)
    {
        $salesRuleName = $this->getCoreResource()->getTableName('salesrule/rule');
        $conditions = 'main_table.rule_id=salesrule.rule_id';
        $collection->getSelect()->joinLeft(
            array('salesrule' => $salesRuleName),
            $conditions,
            array('use_auto_generation' => 'use_auto_generation')
        );
    }

    protected function getRedemptionUrl($promoCode, $magentoStoreId)
    {
        $token = $this->getToken();
        $promoCode->setToken($token);
        $url = Mage::getModel('core/url')->setStore($magentoStoreId)->getUrl('', array('_nosid' => true, '_secure' => true)) . 'mailchimp/cart/loadcoupon?coupon_id=' . $promoCode->getCouponId() . '&coupon_token=' . $token;
        return $url;
    }

    protected function getToken()
    {
        $token = md5(rand(0, 9999999));
        return $token;
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Api_PromoRules|false|Mage_Core_Model_Abstract
     */
    public function getApiPromoRules()
    {
        if (!$this->apiPromoRules) {
            $this->apiPromoRules = Mage::getModel('mailchimp/api_promoRules');
        }
        return $this->apiPromoRules;
    }

    public function markAsDeleted($codeId, $promoRuleId)
    {
        $this->_setDeleted($codeId, $promoRuleId);
    }

    protected function _setDeleted($codeId, $promoRuleId)
    {
        $helper = $this->getMailChimpHelper();
        $promoCodes = $helper->getAllEcommerceSyncDataItemsPerId($codeId, Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE);
        foreach ($promoCodes as $promoCode) {
            $mailchimpStoreId = $promoCode->getMailchimpStoreId();
            $helper->saveEcommerceSyncData($codeId, Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE, $mailchimpStoreId, null, null, null, 1, null, null, $promoRuleId);
        }
    }

    /**
     * @param $promoRule
     */
    public function deletePromoCodesSyncDataByRule($promoRule)
    {
        $promoCodeIds = $this->getPromoCodesForRule($promoRule->getRelatedId());
        foreach ($promoCodeIds as $promoCodeId) {
            $promoCodeSyncDataItems = $this->getMailChimpHelper()->getAllEcommerceSyncDataItemsPerId($promoCodeId, Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE);
            foreach ($promoCodeSyncDataItems as $promoCodeSyncDataItem) {
                $promoCodeSyncDataItem->delete();
            }
        }
    }

    public function deletePromoCodeSyncData($promoCodeId, $mailchimpStoreId)
    {
        $promoCodeSyncDataItem = $this->getMailChimpHelper()->getEcommerceSyncDataItem($promoCodeId, Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE, $mailchimpStoreId);
        $promoCodeSyncDataItem->delete();
    }

    /**
     * @param $promoRuleId
     * @return array
     */
    protected function getPromoCodesForRule($promoRuleId)
    {
        $promoCodes = array();
        $helper = $this->getMailChimpHelper();
        $promoRules = $helper->getAllEcommerceSyncDataItemsPerId($promoRuleId, Ebizmarts_MailChimp_Model_Config::IS_PROMO_RULE);
        foreach ($promoRules as $promoRule) {
            $mailchimpStoreId = $promoRule->getMailchimpStoreId();
            $api = $helper->getApiByMailChimpStoreId($mailchimpStoreId);
            try {
                $mailChimpPromoCodes = $api->ecommerce->promoRules->promoCodes->getAll($mailchimpStoreId, $promoRuleId);
                foreach ($mailChimpPromoCodes['promo_codes'] as $promoCode) {
                    $this->deletePromoCodeSyncData($promoCode['id'], $mailchimpStoreId);
                }
            } catch (MailChimp_Error $e) {
                $helper->logError($e->getFriendlyMessage());
            }
        }
        return $promoCodes;
    }

    protected function getPromoRuleIdByCouponId($promoCodeId)
    {
        $coupon = Mage::getModel('salesrule/coupon')->load($promoCodeId);
        return $coupon->getRuleId();
    }

    /**
     * @param $mailchimpStoreId
     * @param $ruleId
     * @param $codeId
     */
    protected function setCodeWithParentError($mailchimpStoreId, $ruleId, $codeId)
    {
        $error = Mage::helper('mailchimp')->__('Parent rule with id ' . $ruleId . ' has not been correctly sent.');
        $this->_updateSyncData($codeId, $mailchimpStoreId, Varien_Date::now(), $error);
    }

    /**
     * @return Mage_Core_Model_Abstract
     */
    protected function getCoreResource()
    {
        return Mage::getSingleton('core/resource');
    }
}
