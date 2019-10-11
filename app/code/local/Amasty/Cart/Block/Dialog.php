<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Cart
 */
class Amasty_Cart_Block_Dialog extends Mage_Catalog_Block_Product_Abstract
{
    const IMAGE_WIDTH = 165;
    const IMAGE_HEIGHT = 165;

    protected $_product;

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('amasty/amcart/catalog/product/view/dialog.phtml');
    }

    /**
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        return $this->_product;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @return $this
     */
    public function setProduct(Mage_Catalog_Model_Product $product)
    {
        $this->_product = $product;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        $simpleProduct = Mage::registry('simpleProduct');
        $helper = $this->helper('catalog/output');

        if ($this->getAmcartHelper()->getConfigurableName() && $simpleProduct) {
            $name = $helper->productAttribute($simpleProduct, $simpleProduct->getName(), 'name');
        } else {
            $name = $helper->productAttribute($this->getProduct(), $this->getProduct()->getName(), 'name');
        }

        return $name;
    }

    /**
     * @return string
     */
    public function getImageSrc()
    {
        $simpleProduct = Mage::registry('simpleProduct');
        $helper = $this->helper('catalog/image');

        if ($this->getAmcartHelper()->getConfigurableImage() && $simpleProduct) {
            $image = $simpleProduct->getResource()->getAttributeRawValue($simpleProduct->getId(),'image');
            $src = $helper->init($simpleProduct, 'image', $image)
                    ->resize(self::IMAGE_WIDTH, self::IMAGE_HEIGHT);
        } else {
            $src = $helper->init($this->getProduct(), 'image')
                ->resize(self::IMAGE_WIDTH, self::IMAGE_HEIGHT);

        }

        return $src;
    }

    /**
     * @return string
     */
    public function getSubtotalText()
    {
        return $this->getAmcartHelper()->getSubtotalText();
    }

    /**
     * @return string
     */
    public function getCartCount()
    {
        return $this->getAmcartHelper()->getCartCount();
    }

    /**
     * @return Amasty_Cart_Helper_Data
     */
    public function getAmcartHelper()
    {
        return Mage::helper('amcart');
    }

    public function displayChangeQty()
    {
        return $this->getAmcartHelper()->displayChangeQty()
            && $this->getAmcartHelper()->displayProductImage()
            && !($this->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE
                || $this->getProduct()->isGrouped());
    }

    /**
     * @return mixed|string
     */
    public function getImageLabel()
    {
        $label = $this->getProduct()->getData('image_label');
        if (empty($label)) {
            $label = $this->getProduct()->getName();
        }

        return $label;
    }

    public function getQty()
    {
        $simpleProduct = Mage::registry('simpleProduct');
        $qty = 1;
        $product = $simpleProduct ? $simpleProduct : $this->getProduct();
        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE) {
            $cartItem = Mage::getSingleton('checkout/cart')->getQuote()->getItemByProduct($product);
            if ($cartItem) {
                $qty = $cartItem->getQty();
            }
        }

        return $qty;
    }

}
