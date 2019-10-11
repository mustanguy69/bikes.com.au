<?php
/**
 * Magmodules.eu - http://www.magmodules.eu.
 *
 * NOTICE OF LICENSE
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.magmodules.eu/MM-LICENSE.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@magmodules.eu so we can send you a copy immediately.
 *
 * @category      Magmodules
 * @package       Magmodules_Googleshopping
 * @author        Magmodules <info@magmodules.eu>
 * @copyright     Copyright (c) 2017 (http://www.magmodules.eu)
 * @license       https://www.magmodules.eu/terms.html  Single Service License
 */

class Magmodules_Googleshopping_Model_Googleshopping extends Magmodules_Googleshopping_Model_Common
{

    /**
     * @param $storeId
     * @param $timeStart
     *
     * @return array
     */
    public function generateFeed($storeId, $timeStart)
    {
        $this->setMemoryLimit($storeId);
        $config = $this->getFeedConfig($storeId);
        $products = $this->getProducts($config, $config['limit']);
        $prices = Mage::helper('googleshopping')->getTypePrices($config, $products);
        if ($feed = $this->getFeedData($products, $config, $timeStart, $prices)) {
            return $this->saveFeed($feed, $config, 'googleshopping', $feed['config']['products']);
        }
    }

    /**
     * @param $storeId
     */
    protected function setMemoryLimit($storeId)
    {
        if (Mage::getStoreConfig('googleshopping/generate/overwrite', $storeId)) {
            if ($memoryLimit = Mage::getStoreConfig('googleshopping/generate/memory_limit', $storeId)) {
                ini_set('memory_limit', $memoryLimit);
            }

            if ($maxExecutionTime = Mage::getStoreConfig('googleshopping/generate/max_execution_time', $storeId)) {
                ini_set('max_execution_time', $maxExecutionTime);
            }
        }
    }

    /**
     * @param        $storeId
     * @param string $type
     *
     * @return array
     */
    public function getFeedConfig($storeId, $type = 'xml')
    {
        $config = array();
        $feed = Mage::helper('googleshopping');
        $filename = $this->getFileName('googleshopping', $storeId);
        $websiteId = Mage::app()->getStore($storeId)->getWebsiteId();

        // DEFAULTS
        $config['store_id'] = $storeId;
        $config['website_name'] = $feed->cleanData(
            Mage::getModel('core/website')->load($websiteId)->getName(),
            'striptags'
        );
        $config['website_url'] = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
        $config['media_url'] = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
        $config['media_image_url'] = $config['media_url'] . 'catalog' . DS . 'product';
        $config['media_attributes'] = $feed->getMediaAttributes();
        $config['media_gallery_id'] = Mage::getResourceModel('eav/entity_attribute')->getIdByCode(
            'catalog_product',
            'media_gallery'
        );
        $config['file_name'] = $filename;
        $config['limit'] = Mage::getStoreConfig('googleshopping/generate/limit', $storeId);
        $config['filters'] = @unserialize(Mage::getStoreConfig('googleshopping/filter/advanced', $storeId));
        $config['version'] = (string)Mage::getConfig()->getNode()->modules->Magmodules_Googleshopping->version;
        $config['product_url_suffix'] = $feed->getProductUrlSuffix($storeId);
        $config['filter_enabled'] = Mage::getStoreConfig('googleshopping/filter/category_enabled', $storeId);
        $config['filter_cat'] = Mage::getStoreConfig('googleshopping/filter/categories', $storeId);
        $config['filter_type'] = Mage::getStoreConfig('googleshopping/filter/category_type', $storeId);
        $config['filter_status'] = Mage::getStoreConfig('googleshopping/filter/visibility_inc', $storeId);
        $config['category_default'] = Mage::getStoreConfig('googleshopping/data/category_fixed', $storeId);
        $config['producttype'] = Mage::getStoreConfig('googleshopping/advanced/producttype', $storeId);
        $config['identifier'] = Mage::getStoreConfig('googleshopping/advanced/identifier', $storeId);
        $config['stock'] = Mage::getStoreConfig('googleshopping/filter/stock', $storeId);
        $config['conf_enabled'] = Mage::getStoreConfig('googleshopping/advanced/conf_enabled', $storeId);
        $config['conf_fields'] = Mage::getStoreConfig('googleshopping/advanced/conf_fields', $storeId);
        $config['conf_switch_urls'] = Mage::getStoreConfig('googleshopping/advanced/conf_switch_urls', $storeId);
        $config['simple_price'] = Mage::getStoreConfig('googleshopping/advanced/simple_price', $storeId);
        $config['conf_exclude_parent'] = Mage::getStoreConfig('googleshopping/advanced/conf_enabled', $storeId);
        $config['url_suffix'] = Mage::getStoreConfig('googleshopping/advanced/url_utm', $storeId);
        $config['images'] = Mage::getStoreConfig('googleshopping/data/images', $storeId);
        $config['image1'] = Mage::getStoreConfig('googleshopping/data/image1', $storeId);
        $config['condition_default'] = Mage::getStoreConfig('googleshopping/data/condition_default', $storeId);
        $config['stock_manage'] = Mage::getStoreConfig('cataloginventory/item_options/manage_stock');
        $config['stock_instock'] = 'in stock';
        $config['stock_outofstock'] = 'out of stock';
        $config['condition_default'] = Mage::getStoreConfig('googleshopping/data/condition_default', $storeId);
        $config['hide_no_stock'] = Mage::getStoreConfig('googleshopping/filter/stock', $storeId);
        $config['weight'] = Mage::getStoreConfig('googleshopping/advanced/weight', $storeId);
        $config['weight_units'] = Mage::getStoreConfig('googleshopping/advanced/weight_units', $storeId);
        $config['price_scope'] = Mage::getStoreConfig('catalog/price/scope');
        $config['price_add_tax'] = Mage::getStoreConfig('googleshopping/advanced/add_tax', $storeId);
        $config['price_add_tax_perc'] = Mage::getStoreConfig('googleshopping/advanced/tax_percentage', $storeId);
        $config['price_grouped'] = Mage::getStoreConfig('googleshopping/advanced/grouped_price', $storeId);
        $config['force_tax'] = Mage::getStoreConfig('googleshopping/advanced/force_tax', $storeId);
        $config['currency'] = Mage::app()->getStore($storeId)->getCurrentCurrencyCode();
        $config['base_currency_code'] = Mage::app()->getStore($storeId)->getBaseCurrencyCode();
        $config['markup'] = Mage::helper('googleshopping')->getPriceMarkup($config);
        $config['use_tax'] = Mage::helper('googleshopping')->getTaxUsage($config);
        $config['shipping'] = @unserialize(Mage::getStoreConfig('googleshopping/advanced/shipping', $storeId));

        // CHECK CUSTOM ATTRIBUTES
        $eavAttribute = new Mage_Eav_Model_Mysql4_Entity_Attribute();
        if ($eavAttribute->getIdByCode('catalog_category', 'googleshopping_category')) {
            $config['category_custom'] = 'googleshopping_category';
        }

        if ($eavAttribute->getIdByCode('catalog_product', 'googleshopping_exclude')) {
            $config['filter_exclude'] = 'googleshopping_exclude';
        }

        if (Mage::getStoreConfig('googleshopping/data/condition_type', $storeId) == 'attribute') {
            $config['condition_attribute'] = Mage::getStoreConfig('googleshopping/data/condition', $storeId);
        }

        if (Mage::getStoreConfig('googleshopping/data/name', $storeId) == 'use_custom') {
            $config['custom_name'] = Mage::getStoreConfig('googleshopping/data/name_custom', $storeId);
        }

        // FIELD & CATEGORY DATA
        $config['field'] = $this->getFeedAttributes($storeId, $type, $config);
        $config['category_data'] = $feed->getCategoryData($config, $storeId);

        return $config;
    }

    /**
     * @param     $type
     * @param     $storeId
     * @param int $refresh
     *
     * @return string
     */
    public function getFileName($type, $storeId, $refresh = 1)
    {
        if (!$fileName = Mage::getStoreConfig($type . '/generate/filename', $storeId)) {
            $fileName = $type . '.xml';
        }

        if (substr($fileName, -3) != 'xml') {
            $fileName = $fileName . '-' . $storeId . '.xml';
        } else {
            $fileName = substr($fileName, 0, -4) . '-' . $storeId . '.xml';
        }

        if (!file_exists(Mage::getBaseDir('media') . DS . $type)) {
            mkdir(Mage::getBaseDir('media') . DS . $type);
        }

        return Mage::getBaseDir() . DS . 'media' . DS . $type . DS . $fileName;
    }

    public function getFeedAttributes($storeId = 0, $type = 'xml', $config = '')
    {
        $attributes = array();
        $attributes['id'] = array(
            'label'  => 'g:id',
            'source' => Mage::getStoreConfig('googleshopping/data/id', $storeId)
        );
        $attributes['title'] = array(
            'label'  => 'g:title',
            'source' => Mage::getStoreConfig('googleshopping/data/name', $storeId),
            'action' => 'striptags_truncate150_uppercheck'
        );
        $attributes['description'] = array(
            'label'  => 'g:description',
            'source' => Mage::getStoreConfig('googleshopping/data/description', $storeId),
            'action' => 'striptags_truncate'
        );
        $attributes['gtin'] = array(
            'label'  => 'g:gtin',
            'source' => Mage::getStoreConfig('googleshopping/data/gtin_attribute', $storeId),
            'action' => 'striptags'
        );
        $attributes['brand'] = array(
            'label'  => 'g:brand',
            'source' => Mage::getStoreConfig('googleshopping/data/brand_attribute', $storeId),
            'action' => 'striptags'
        );
        $attributes['mpn'] = array(
            'label'  => 'g:mpn',
            'source' => Mage::getStoreConfig('googleshopping/data/mpn_attribute', $storeId),
            'action' => 'striptags'
        );
        $attributes['color'] = array(
            'label'  => 'g:color',
            'source' => Mage::getStoreConfig('googleshopping/data/color', $storeId),
            'action' => 'striptags'
        );
        $attributes['material'] = array(
            'label'  => 'g:material',
            'source' => Mage::getStoreConfig('googleshopping/data/material', $storeId),
            'action' => 'striptags'
        );
        $attributes['pattern'] = array(
            'label'  => 'g:pattern',
            'source' => Mage::getStoreConfig('googleshopping/data/pattern', $storeId),
            'action' => 'striptags'
        );
        $attributes['size'] = array(
            'label'  => 'g:size',
            'source' => Mage::getStoreConfig('googleshopping/data/size', $storeId),
            'action' => 'striptags'
        );
        $attributes['size_type'] = array(
            'label'  => 'g:size_type',
            'source' => Mage::getStoreConfig('googleshopping/data/size_type', $storeId),
            'action' => 'striptags'
        );
        $attributes['size_system'] = array(
            'label'  => 'g:size_system',
            'source' => Mage::getStoreConfig('googleshopping/data/size_system', $storeId),
            'action' => 'striptags'
        );
        $attributes['gender'] = array(
            'label'  => 'g:gender',
            'source' => Mage::getStoreConfig('googleshopping/data/gender', $storeId),
            'action' => 'striptags'
        );
        $attributes['product_url'] = array(
            'label'  => 'g:link',
            'source' => ''
        );
        $attributes['image_link'] = array(
            'label'  => 'g:image_link',
            'source' => Mage::getStoreConfig('googleshopping/data/image1', $storeId)
        );
        $attributes['availability'] = array(
            'label'  => 'g:availability',
            'source' => ''
        );
        $attributes['condition'] = array(
            'label'  => 'g:condition',
            'source' => Mage::getStoreConfig('googleshopping/data/condition', $storeId)
        );
        $attributes['price'] = array(
            'label'  => 'g:price',
            'source' => ''
        );
        $attributes['weight'] = array(
            'label'  => 'g:weight',
            'source' => ''
        );
        $attributes['categories'] = array(
            'label'  => 'categories',
            'source' => ''
        );
        $attributes['bundle'] = array(
            'label'  => 'g:is_bundle',
            'source' => ''
        );
        $attributes['parent_id'] = array(
            'label'  => 'g:item_group_id',
            'source' => Mage::getStoreConfig('googleshopping/data/id', $storeId),
            'parent' => 1
        );
        $attributes['googleshopping_exclude'] = array(
            'label'  => 'g:exclude',
            'source' => 'googleshopping_exclude'
        );

        if ($extraFields = @unserialize(Mage::getStoreConfig('googleshopping/advanced/extra', $storeId))) {
            foreach ($extraFields as $extraField) {
                $attributes[$extraField['attribute']] = array(
                    'label'  => $extraField['name'],
                    'source' => $extraField['attribute'],
                    'action' => $extraField['action']
                );
            }
        }

        if ($type == 'flatcheck') {
            if ($filters = @unserialize(Mage::getStoreConfig('googleshopping/filter/advanced', $storeId))) {
                foreach ($filters as $filter) {
                    $attributes[$filter['attribute']] = array(
                        'label'  => $filter['attribute'],
                        'source' => $filter['attribute']
                    );
                }
            }

            if (Mage::getStoreConfig('googleshopping/data/name', $storeId) == 'use_custom') {
                $customValues = Mage::getStoreConfig('googleshopping/data/name_custom', $storeId);
                $att = preg_match_all("/{{([^}]*)}}/", $customValues, $foundAtts);
                if (!empty($foundAtts)) {
                    foreach ($foundAtts[1] as $att) {
                        $attributes[$att] = array('label' => $att, 'source' => $att);
                    }
                }
            }
        }

        return Mage::helper('googleshopping')->addAttributeData($attributes, $config);
    }

    /**
     * @param $products
     * @param $config
     * @param $timeStart
     * @param $prices
     *
     * @return array
     */
    public function getFeedData($products, $config, $timeStart, $prices)
    {
        foreach ($products as $product) {
            $parentId = Mage::helper('googleshopping')->getParentData($product, $config);
            if ($parentId > 0) {
                $parent = $products->getItemById($parentId);
            } else {
                $parent = '';
            }

            if ($productData = Mage::helper('googleshopping')->getProductDataRow($product, $config, $parent)) {
                foreach ($productData as $key => $value) {
                    if ((!is_array($value)) && (!empty($value))) {
                        $productRow[$key] = $value;
                    }
                }

                if ($extraData = $this->getExtraDataFields($productData, $config, $product, $prices)) {
                    $productRow = array_merge($productRow, $extraData);
                }

                $productRow = $this->processUnset($productRow);
                $feed['channel'][] = $productRow;
                unset($productRow);
            }
        }

        if (!empty($feed)) {
            $returnFeed = array();
            $returnFeed['config'] = $this->getFeedHeader($config, $timeStart, count($feed['channel']));
            $returnFeed['channel'] = $feed['channel'];
            return $returnFeed;
        } else {
            $returnFeed = array();
            $returnFeed['config'] = $this->getFeedHeader($config, $timeStart);
            return $returnFeed;
        }
    }

    /**
     * @param $productData
     * @param $config
     * @param $product
     * @param $prices
     *
     * @return array
     */
    protected function getExtraDataFields($productData, $config, $product, $prices)
    {
        $_extra = array();
        if ($_custom = $this->getCustomData($productData, $config, $product)) {
            $_extra = array_merge($_extra, $_custom);
        }

        if ($_identifierExists = $this->getIdentifierExists($productData, $config)) {
            $_extra = array_merge($_extra, $_identifierExists);
        }

        if ($_categoryData = $this->getCategoryData($productData, $config)) {
            $_extra = array_merge($_extra, $_categoryData);
        }

        if ($_prices = $this->getPrices($productData['g:price'], $prices, $product, $config['currency'])) {
            $_extra = array_merge($_extra, $_prices);
        }

        if ($_images = $this->getImages($productData, $config)) {
            $_extra = array_merge($_extra, $_images);
        }

        if ($_shipping = $this->getShipping($productData, $config)) {
            $_extra = array_merge($_extra, $_shipping);
        }

        if ($_promotion = $this->getPromotion($productData, $config)) {
            $_extra = array_merge($_extra, $_promotion);
        }

        return $_extra;
    }

    /**
     * @param $productData
     * @param $config
     * @param $product
     *
     * @return array
     */
    protected function getCustomData($productData, $config, $product)
    {
        $custom = array();
        if (isset($config['custom_name'])) {
            $custom['g:title'] = $this->reformatString($config['custom_name'], $product, '');
        }

        return $custom;
    }

    /**
     * @param        $data
     * @param        $product
     * @param string $symbol
     *
     * @return string
     */
    protected function reformatString($data, $product, $symbol = '')
    {
        $att = preg_match_all("/{{([^}]*)}}/", $data, $attributes);
        if (!empty($attributes)) {
            $i = 0;
            foreach ($attributes[0] as $key => $value) {
                if (!empty($product[$attributes[1][$key]])) {
                    if ($product->getAttributeText($attributes[1][$key])) {
                        $data = str_replace($value, $product->getAttributeText($attributes[1][$key]), $data);
                    } else {
                        $data = str_replace($value, $product[$attributes[1][$key]], $data);
                    }
                } else {
                    $data = str_replace($value, '', $data);
                }

                if ($symbol) {
                    $data = preg_replace('/' . $symbol . '+/', ' ' . $symbol . ' ',
                        rtrim(str_replace(' ' . $symbol . ' ', $symbol, $data), $symbol));
                }
            }
        }

        return trim($data);
    }

    /**
     * @param $productData
     * @param $config
     *
     * @return mixed
     */
    protected function getIdentifierExists($productData, $config)
    {
        if ($config['identifier'] == 1) {
            $identifiers = '';
            if (!empty($productData['g:gtin'])) {
                $identifiers++;
            }

            if (!empty($productData['g:brand'])) {
                $identifiers++;
            }

            if (!empty($productData['g:mpn'])) {
                $identifiers++;
            }

            if ($identifiers < 2) {
                $identifier['g:identifier_exists'] = 'FALSE';
                return $identifier;
            }
        }

        if ($config['identifier'] == 2) {
            $identifier['g:identifier_exists'] = 'FALSE';
            return $identifier;
        }
    }

    /**
     * @param $productData
     * @param $config
     *
     * @return array
     */
    protected function getCategoryData($productData, $config)
    {
        $level1 = $level2 = '';
        $category = array();
        $category['g:google_product_category'] = Mage::helper('googleshopping')->cleanData($config['category_default'],
            'stiptags');
        if (!empty($productData['categories'])) {
            foreach ($productData['categories'] as $cat) {
                if ($cat['level'] > $level1) {
                    if (!empty($cat['custom'])) {
                        $category['g:google_product_category'] = $cat['custom'];
                        $level1 = $cat['level'];
                    }
                }
            }

            if (!empty($config['producttype'])) {
                foreach ($productData['categories'] as $cat) {
                    if ($cat['level'] > $level2) {
                        $category['g:product_type'] = implode(' > ', $cat['path']);
                        $level2 = $cat['level'];
                    }
                }
            }
        }

        return $category;
    }

    /**
     * @param $data
     * @param $confPrices
     * @param $product
     * @param $currency
     *
     * @return array
     */
    protected function getPrices($data, $confPrices, $product, $currency)
    {
        $prices = array();
        $id = $product->getEntityId();
        if (!empty($confPrices[$id])) {
            $confPrice = Mage::helper('tax')->getPrice($product, $confPrices[$id], true);
            $confPriceReg = Mage::helper('tax')->getPrice($product, $confPrices[$id . '_reg'], true);
            if ($confPriceReg > $confPrice) {
                $prices['g:sale_price'] = $confPrice . ' ' . $currency;
                $prices['g:price'] = $confPriceReg . ' ' . $currency;
            } else {
                $prices['g:price'] = $confPrice . ' ' . $currency;
            }
        } else {
            if (isset($data['sales_price'])) {
                $prices['g:price'] = $data['regular_price'];
                $prices['g:sale_price'] = $data['sales_price'];
                if (isset($data['sales_date_start']) && isset($data['sales_date_end'])) {
                    $prices['g:sale_price_effective_date'] = str_replace(' ', 'T',
                            $data['sales_date_start']) . '/' . str_replace(' ', 'T', $data['sales_date_end']);
                }
            } else {
                $prices['g:price'] = $data['price'];
            }
        }

        return $prices;
    }

    /**
     * @param $productData
     * @param $config
     *
     * @return array
     */
    public function getImages($productData, $config)
    {
        $_images = array();
        $i = 1;
        if ($config['images'] == 'all') {
            if (!empty($config['image1'])) {
                if (!empty($productData['image'][$config['image1']])) {
                    $_images['g:image_link'] = $productData['image'][$config['image1']];
                }
            } else {
                if (!empty($productData['image']['base'])) {
                    $_images['g:image_link'] = $productData['image']['base'];
                }
            }

            if (empty($_images['g:image_link'])) {
                $_images['g:image_link'] = $productData['image_link'];
            }

            if (!empty($productData['image']['all'])) {
                foreach ($productData['image']['all'] as $image) {
                    if (!in_array($image, $_images)) {
                        $_images['g:additional_image_link' . $i] = $image;
                        if ($i == 10) {
                            break;
                        }

                        $i++;
                    }
                }
            }
        }

        return $_images;
    }

    /**
     * @param $data
     * @param $config
     *
     * @return array
     */
    protected function getShipping($data, $config)
    {
        $shippingArray = array();
        $i = 1;
        if (!empty($config['shipping'])) {
            foreach ($config['shipping'] as $shipping) {
                $price = $data['g:price']['final_price_clean'];
                if (($price >= $shipping['price_from']) && ($price <= $shipping['price_to'])) {
                    $shippingPrice = $shipping['price'];
                    $shippingPrice = number_format($shippingPrice, 2, '.', '') . ' ' . $config['currency'];
                    $shippingArrayR['g:country'] = $shipping['country'];
                    $shippingArrayR['g:service'] = $shipping['service'];
                    $shippingArrayR['g:price'] = $shippingPrice;
                    $shippingArray['g:shipping-' . $i] = $shippingArrayR;
                    $i++;
                }
            }
        }

        return $shippingArray;
    }

    /**
     * @param $productData
     * @param $config
     *
     * @return array
     */
    public function getPromotion($productData, $config)
    {
        $_promos = array();
        $i = 1;
        if (!empty($productData['promotion_id'])) {
            $promos = explode(',', $productData['promotion_id']);
            foreach ($promos as $promo) {
                $_promos['g:promotion_id' . $i] = $promo;
                $i++;
            }
        }

        if (!empty($productData['g:promotion_id'])) {
            $promos = explode(',', $productData['g:promotion_id']);
            foreach ($promos as $promo) {
                $_promos['g:promotion_id' . $i] = $promo;
                $i++;
            }
        }

        return $_promos;
    }

    /**
     * @param $productRow
     *
     * @return mixed
     */
    public function processUnset($productRow)
    {
        if (isset($productRow['g:promotion_id'])) {
            unset($productRow['g:promotion_id']);
        }

        if (isset($productRow['promotion_id'])) {
            unset($productRow['promotion_id']);
        }

        if (isset($productRow['g:image_link'])) {
            if (isset($productRow['image_link'])) {
                unset($productRow['image_link']);
            }
        }

        if (isset($productRow['g:exclude'])) {
            unset($productRow['g:exclude']);
        }

        return $productRow;
    }

    /**
     * @param     $config
     * @param     $timeStart
     * @param int $count
     *
     * @return array
     */
    protected function getFeedHeader($config, $timeStart, $count = 0)
    {
        $header = array();
        $header['system'] = 'Magento';
        $header['extension'] = 'Magmodules_Googleshopping';
        $header['extension_version'] = $config['version'];
        $header['store'] = $config['website_name'];
        $header['url'] = $config['website_url'];
        if ($config['limit'] > 0) {
            $header['set_limit'] = $config['limit'];
        }

        $header['products'] = $count;
        $header['generated'] = Mage::getModel('core/date')->date('Y-m-d H:i:s');
        $header['processing_time'] = number_format((microtime(true) - $timeStart), 4);
        return $header;
    }

    /**
     * @param $feed
     * @param $config
     * @param $type
     * @param $count
     *
     * @return array
     */
    public function saveFeed($feed, $config, $type, $count)
    {
        $encoding = Mage::getStoreConfig('design/head/default_charset');
        $xmlData = new SimpleXMLElement("<rss xmlns:g=\"http://base.google.com/ns/1.0\" xmlns:c=\"http://base.google.com/ns/1.0\"  version=\"2.0\" encoding=\"" . $encoding . "\"></rss>");
        $this->getArray2Xml($feed, $xmlData);
        $dom = dom_import_simplexml($xmlData)->ownerDocument;
        $dom->encoding = $encoding;
        $dom->formatOutput = true;
        $xmlFeed = $dom->saveXML();
        if (!file_put_contents($config['file_name'], $xmlFeed)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper($type)->__('File writing not succeeded'));
        } else {
            $filename = $config['file_name'];
            $storeId = $config['store_id'];
            $localPath = Mage::getBaseDir() . DS . 'media' . DS . $type . DS;
            $filename = str_replace($localPath, '', $filename);
            $websiteUrl = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
            $feedUrl = $websiteUrl . $type . '/' . $filename;
            $result = array();
            $result['url'] = $feedUrl;
            $result['shop'] = Mage::app()->getStore($storeId)->getCode();
            $result['date'] = date("Y-m-d H:i:s", Mage::getModel('core/date')->timestamp(time()));
            $result['qty'] = $count;
            return $result;
        }
    }

    /**
     * @param $array
     * @param $xmlUserInfo
     */
    function getArray2Xml($array, &$xmlUserInfo)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (!is_numeric($key)) {
                    if (substr($key, 0, 10) == 'g:shipping') {
                        $key = 'g:shipping';
                        $subnode = $xmlUserInfo->addChild("$key", "", "http://base.google.com/ns/1.0");
                        $this->getArray2Xml($value, $subnode);
                    } else {
                        $subnode = $xmlUserInfo->addChild("$key");
                        $this->getArray2Xml($value, $subnode);
                    }
                } else {
                    $subnode = $xmlUserInfo->addChild("item");
                    $this->getArray2Xml($value, $subnode);
                }
            } else {
                if (substr($key, 0, 23) == 'g:additional_image_link') {
                    $key = 'g:additional_image_link';
                }

                if (substr($key, 0, 14) == 'g:promotion_id') {
                    $key = 'g:promotion_id';
                }

                $xmlUserInfo->addChild($key, htmlspecialchars("$value"), "http://base.google.com/ns/1.0");
            }
        }
    }

}