<?php
/**
 * @category  Aligent
 * @package   zipmoney
 * @author    Andi Han <andi@aligent.com.au>
 * @copyright 2014 Aligent Consulting.
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.aligent.com.au/
 */

/**
 * Class Zipmoney_ZipmoneyPayment_Test_Model_Observer
 * @loadSharedFixture scope.yaml
 */
class Zipmoney_ZipmoneyPayment_Test_Model_Observer extends EcomDev_PHPUnit_Test_Case
{


    protected function _updateAddress(Mage_Sales_Model_Order_Address $oAddress, $aUpdatedAddress)
    {
        if (isset($aUpdatedAddress['prefix'])) {
            $oAddress->setPrefix($aUpdatedAddress['prefix']);
        }
        if (isset($aUpdatedAddress['firstname'])) {
            $oAddress->setFirstname($aUpdatedAddress['firstname']);
        }
        if (isset($aUpdatedAddress['middlename'])) {
            $oAddress->setMiddlename($aUpdatedAddress['middlename']);
        }
        if (isset($aUpdatedAddress['lastname'])) {
            $oAddress->setLastname($aUpdatedAddress['lastname']);
        }
        if (isset($aUpdatedAddress['suffix'])) {
            $oAddress->setSuffix($aUpdatedAddress['suffix']);
        }
        if (isset($aUpdatedAddress['company'])) {
            $oAddress->setCompany($aUpdatedAddress['company']);
        }
        if (isset($aUpdatedAddress['street'])) {
            $oAddress->setStreet($aUpdatedAddress['street']);
        }
        if (isset($aUpdatedAddress['city'])) {
            $oAddress->setCity($aUpdatedAddress['city']);
        }
        if (isset($aUpdatedAddress['country_id'])) {
            $oAddress->setCountryId($aUpdatedAddress['country_id']);
        }
        if (isset($aUpdatedAddress['region_id'])) {
            $oAddress->setRegionId($aUpdatedAddress['region_id']);
        }
        if (isset($aUpdatedAddress['region'])) {                // for the case of free text field of state
            $oAddress->setRegion($aUpdatedAddress['region']);
        }
        if (isset($aUpdatedAddress['postcode'])) {
            $oAddress->setPostcode($aUpdatedAddress['postcode']);
        }
        if (isset($aUpdatedAddress['telephone'])) {
            $oAddress->setTelephone($aUpdatedAddress['telephone']);
        }
        if (isset($aUpdatedAddress['fax'])) {
            $oAddress->setFax($aUpdatedAddress['fax']);
        }
        if (isset($aUpdatedAddress['vat_id'])) {
            $oAddress->setVatId($aUpdatedAddress['vat_id']);
        }
        return $oAddress;
    }

    /**
     * @test
     * @cover Zipmoney_ZipmoneyPayment_Model_Observer_notifyShippingAddressUpdated
     * @group Zipmoney_ZipmoneyPayment
     * @loadFixture products.yaml
     * @loadFixture orders.yaml
     * @loadFixture order_addresses.yaml
     * @loadFixture order_items.yaml
     * @dataProvider dataProvider
     */
    public function testNotifyShippingAddressUpdated($iOrderId, $aAddress, $bExpIsNotify)
    {
        /** @var Mage_Sales_Model_Order $oOrder */
        /** @var Mage_Sales_Model_Order_Address $oShippingAddress */
        $oOrder = Mage::getModel('sales/order')->getCollection()->addFieldToFilter("entity_id", $iOrderId)->getFirstItem();
        $oShippingAddress = $oOrder->getShippingAddress();
        if ($aAddress) {
            $oShippingAddress = $this->_updateAddress($oShippingAddress, $aAddress);
        }
        $bIsNotify = Mage::getModel('zipmoneypayment/observer')->isNotifyShippingAddressUpdated($oShippingAddress);
        $this->assertEquals($bExpIsNotify, $bIsNotify);

        if ($bIsNotify) {
            $oApiHelper = Mage::helper('zipmoneypayment/api');
            $vEndpoint = ZipMoney_ApiSettings::API_TYPE_ORDER_SHIPPING_ADDRESS;
            $aRawData = array(
                'order' => $oOrder,
                'shipping_address' => $oShippingAddress
            );
            $aRequestData = $oApiHelper->prepareDataForZipRequest($vEndpoint, $aRawData);
            $this->assertNotNull($aRequestData, 'API request data is null.');
            $this->_checkApiRequestData($aRequestData, $oOrder, $oShippingAddress);
        }
    }

    protected function _checkApiRequestData($aRequestData, Mage_Sales_Model_Order $oOrder, Mage_Sales_Model_Order_Address $oShippingAddress = null)
    {
        $oApiHelper = Mage::helper('zipmoneypayment/api');
        if ($oShippingAddress) {
            $this->assertTrue(isset($aRequestData['shipping_address']));
            $this->assertSame($oShippingAddress->getFirstname(), $aRequestData['shipping_address']['first_name'],
                'Expecting first_name: ' . $oShippingAddress->getFirstname() . ', actually: ' . $aRequestData['shipping_address']['first_name']);
            $this->assertSame($oShippingAddress->getLastname(), $aRequestData['shipping_address']['last_name'],
                'Expecting last_name: ' . $oShippingAddress->getLastname() . ', actually: ' . $aRequestData['shipping_address']['last_name']);
            $this->assertSame($oShippingAddress->getEmail(), $aRequestData['shipping_address']['email'],
                'Expecting email: ' . $oShippingAddress->getEmail() . ', actually: ' . $aRequestData['shipping_address']['email']);
            $this->assertSame($oShippingAddress->getStreet1(), $aRequestData['shipping_address']['line1'],
                'Expecting line1: ' . $oShippingAddress->getStreet1() . ', actually: ' . $aRequestData['shipping_address']['line1']);
            $this->assertSame($oShippingAddress->getStreet2(), $aRequestData['shipping_address']['line2'],
                'Expecting line2: ' . $oShippingAddress->getStreet2() . ', actually: ' . $aRequestData['shipping_address']['line2']);
            $this->assertSame($oShippingAddress->getCountryId(), $aRequestData['shipping_address']['country'],
                'Expecting country: ' . $oShippingAddress->getCountryId() . ', actually: ' . $aRequestData['shipping_address']['country']);
            $this->assertSame($oShippingAddress->getPostcode(), $aRequestData['shipping_address']['zip'],
                'Expecting zip: ' . $oShippingAddress->getPostcode() . ', actually: ' . $aRequestData['shipping_address']['zip']);
            $this->assertSame($oShippingAddress->getCity(), $aRequestData['shipping_address']['city'],
                'Expecting city: ' . $oShippingAddress->getCity() . ', actually: ' . $aRequestData['shipping_address']['city']);
            $this->assertSame($oShippingAddress->getRegionCode(), $aRequestData['shipping_address']['state'],
                'Expecting state: ' . $oShippingAddress->getRegionCode() . ', actually: ' . $aRequestData['shipping_address']['state']);
        }

        $this->assertTrue(isset($aRequestData['order']));
        $this->assertTrue(isset($aRequestData['quote_id']));
        $this->assertTrue(isset($aRequestData['merchant_id']));
        $this->assertTrue(isset($aRequestData['merchant_key']));

        $this->assertEquals($oOrder->getQuoteId(), $aRequestData['quote_id'], 'Quote id does not match.');

        $this->assertSame($oOrder->getIncrementId(), $aRequestData['order']['id'],
            'Expecting order id: ' . $oOrder->getIncrementId() . ', actually: ' . $aRequestData['order']['id']);
        $this->assertEquals($oOrder->getTaxAmount(), $aRequestData['order']['tax'],
            'Expecting order tax: ' . $oOrder->getTaxAmount() . ', actually: ' . $aRequestData['order']['tax']);
        $this->assertEquals($oOrder->getShippingAmount(), $aRequestData['order']['shipping_value'],
            'Expecting order shipping_value: ' . $oOrder->getShippingAmount() . ', actually: ' . $aRequestData['order']['shipping_value']);
        $this->assertEquals($oOrder->getGrandTotal(), $aRequestData['order']['total'],
            'Expecting order total: ' . $oOrder->getGrandTotal() . ', actually: ' . $aRequestData['order']['total']);
        $_i = 0;
        /** @var Mage_Sales_Model_Order_Item $oItem */
        foreach ($oOrder->getAllItems() as $oItem) {
            $iId        = $oItem->getId();
            $vName      = $oItem->getName();
            $vSku       = $oItem->getSku();
            $vDes       = $oApiHelper->getProductShortDescription($oItem, $oOrder->getStoreId());
            $fPrice     = $oItem->getPrice() ? $oItem->getPrice() : 0.0000;
            $iQty       = round($oItem->getQtyOrdered());          // ZipMoney does not support a decimal item quantity at this point, so round the item_qty here.
            $vCat       = $this->_getCategory($oItem->getProductId());
            $vImg       = (string)Mage::helper('catalog/image')->init($oItem->getProduct(), 'thumbnail');

            $this->assertEquals($iId, $aRequestData['order']['detail'][$_i]['id'],
                'Expecting order detail id: ' . $iId . ', actually: ' . $aRequestData['order']['detail'][$_i]['id']);
            $this->assertSame($vName, $aRequestData['order']['detail'][$_i]['name'],
                'Expecting order detail name: ' . $vName . ', actually: ' . $aRequestData['order']['detail'][$_i]['name']);
            $this->assertSame($vSku, $aRequestData['order']['detail'][$_i]['sku'],
                'Expecting order detail sku: ' . $vSku . ', actually: ' . $aRequestData['order']['detail'][$_i]['sku']);
            $this->assertSame($vDes, $aRequestData['order']['detail'][$_i]['description'],
                'Expecting order detail description: ' . $vDes . ', actually: ' . $aRequestData['order']['detail'][$_i]['description']);
            $this->assertEquals($fPrice, $aRequestData['order']['detail'][$_i]['price'],
                'Expecting order detail price: ' . $fPrice . ', actually: ' . $aRequestData['order']['detail'][$_i]['price']);
            $this->assertEquals($iQty, $aRequestData['order']['detail'][$_i]['quantity'],
                'Expecting order detail quantity: ' . $iQty . ', actually: ' . $aRequestData['order']['detail'][$_i]['quantity']);
            $this->assertSame($vCat, $aRequestData['order']['detail'][$_i]['category'],
                'Expecting order detail category: ' . $vCat . ', actually: ' . $aRequestData['order']['detail'][$_i]['category']);
            $this->assertSame($vImg, $aRequestData['order']['detail'][$_i]['image_url'],
                'Expecting order detail image_url: ' . $vImg . ', actually: ' . $aRequestData['order']['detail'][$_i]['image_url']);
            $_i++;
        }
    }

    protected function _getCategory($iProductId)
    {
        $aCategoryName = array();
        $oProduct = Mage::getModel("catalog/product")->load($iProductId);
        $aCategoryIds = $oProduct->getCategoryIds();
        foreach($aCategoryIds as $iCategoryId) {
            $oCategory = Mage::getModel('catalog/category')->load($iCategoryId);
            $aCategoryName[] = $oCategory->getName();
        }
        return implode(',', $aCategoryName);
    }

    /**
     * @test
     * @cover Zipmoney_ZipmoneyPayment_Model_Observer_notifyOrderCancelled
     * @group Zipmoney_ZipmoneyPayment
     * @loadFixture products.yaml
     * @loadFixture orders.yaml
     * @loadFixture order_addresses.yaml
     * @loadFixture order_items.yaml
     * @dataProvider dataProvider
     */
    public function testNotifyOrderCancelled($iOrderId, $vNewState, $bExpIsNotify)
    {
        /** @var Mage_Sales_Model_Order $oOrder */
        $oOrder = Mage::getModel('sales/order')->getCollection()->addFieldToFilter("entity_id", $iOrderId)->getFirstItem();

        $oOrder->setState($vNewState);
        $bIsNotify = Mage::getModel('zipmoneypayment/observer')->isNotifyOrderCancelled($oOrder);
        $this->assertEquals($bExpIsNotify, $bIsNotify);

        if ($bIsNotify) {
            $oApiHelper = Mage::helper('zipmoneypayment/api');
            $vEndpoint = ZipMoney_ApiSettings::API_TYPE_ORDER_CANCEL;
            $aRawData = array(
                'order' => $oOrder
            );
            $aRequestData = $oApiHelper->prepareDataForZipRequest($vEndpoint, $aRawData);
            $this->assertNotNull($aRequestData, 'API request data is null.');
            $this->_checkApiRequestData($aRequestData, $oOrder);
        }
    }
}