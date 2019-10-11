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

class Magmodules_Googleshopping_Model_Common extends Mage_Core_Helper_Abstract
{

    /**
     * @param        $config
     * @param string $limit
     *
     * @return mixed
     */
    public function getProducts($config, $limit = '')
    {
        $storeId = $config['store_id'];
        $collection = Mage::getResourceModel('catalog/product_collection');
        $collection->setStore($storeId);
        $collection->addStoreFilter($storeId);
        $collection->addFinalPrice();
        $collection->addUrlRewrite();

        if (!empty($config['filter_enabled'])) {
            $type = $config['filter_type'];
            $categories = $config['filter_cat'];
            if ($type && $categories) {
                $table = Mage::getSingleton('core/resource')->getTableName('catalog_category_product');
                if ($type == 'include') {
                    $collection->getSelect()->join(array('cats' => $table), 'cats.product_id = e.entity_id');
                    $collection->getSelect()->where('cats.category_id in (' . $categories . ')');
                } else {
                    $collection->getSelect()->join(array('cats' => $table), 'cats.product_id = e.entity_id');
                    $collection->getSelect()->where('cats.category_id not in (' . $categories . ')');
                }
            }
        }

        $collection->addAttributeToFilter('status', 1);

        if ($limit) {
            $collection->setPage(1, $limit)->getCurPage();
        }

        if (!empty($config['filter_status'])) {
            $visibility = $config['filter_status'];
            if (strlen($visibility) > 1) {
                $visibility = explode(',', $visibility);
                if ($config['conf_enabled']) {
                    $visibility[] = '1';
                }

                $collection->addAttributeToFilter('visibility', array('in' => array($visibility)));
            } else {
                if (!empty($config['conf_enabled'])) {
                    $visibility = '1,' . $visibility;
                    $visibility = explode(',', $visibility);
                    $collection->addAttributeToFilter('visibility', array('in' => array($visibility)));
                } else {
                    $collection->addAttributeToFilter('visibility', array('eq' => array($visibility)));
                }
            }
        }

        // All attributes
        $attributes = array();
        $attributes[] = 'url_key';
        $attributes[] = 'status';
        $attributes[] = 'price';
        $attributes[] = 'final_price';
        $attributes[] = 'price_model';
        $attributes[] = 'price_type';
        $attributes[] = 'special_price';
        $attributes[] = 'special_from_date';
        $attributes[] = 'special_to_date';
        $attributes[] = 'type_id';
        $attributes[] = 'tax_class_id';
        $attributes[] = 'tax_percent';
        $attributes[] = 'weight';
        $attributes[] = 'visibility';
        $attributes[] = 'type_id';
        $attributes[] = 'image';
        $attributes[] = 'small_image';
        $attributes[] = 'thumbnail';

        if (!empty($config['filter_exclude'])) {
            $attributes[] = $config['filter_exclude'];
        }

        foreach ($config['field'] as $field) {
            if (isset($field['source'])) {
                $attributes[] = $field['source'];
            }
        }

        if (isset($config['custom_name'])) {
            $att = preg_match_all("/{{([^}]*)}}/", $config['custom_name'], $foundAtts);
            if (!empty($foundAtts)) {
                foreach ($foundAtts[1] as $att) {
                    $attributes[] = $att;
                }
            }
        }

        $collection->addAttributeToSelect(array_unique($attributes));

        if (!empty($config['filters'])) {
            foreach ($config['filters'] as $filter) {
                $attribute = $filter['attribute'];
                if ($filter['type'] == 'select') {
                    $attribute = $filter['attribute'] . '_value';
                }

                $condition = $filter['condition'];
                $value = $filter['value'];
                switch ($condition) {
                    case 'nin':
                        if (strpos($value, ',') !== false) {
                            $value = explode(',', $value);
                        }

                        $collection->addAttributeToFilter(
                            array(
                            array('attribute' => $attribute, $condition => $value),
                            array('attribute' => $attribute, 'null' => true)
                            )
                        );
                        break;
                    case 'in';
                        if (strpos($value, ',') !== false) {
                            $value = explode(',', $value);
                        }

                        $collection->addAttributeToFilter($attribute, array($condition => $value));
                        break;
                    case 'neq':
                        $collection->addAttributeToFilter(
                            array(
                            array('attribute' => $attribute, $condition => $value),
                            array('attribute' => $attribute, 'null' => true)
                            )
                        );
                        break;
                    case 'empty':
                        $collection->addAttributeToFilter($attribute, array('null' => true));
                        break;
                    case 'not-empty':
                        $collection->addAttributeToFilter($attribute, array('notnull' => true));
                        break;
                    default:
                        $collection->addAttributeToFilter($attribute, array($condition => $value));
                        break;
                }
            }
        }

        $collection->joinTable(
            'cataloginventory/stock_item', 'product_id=entity_id', array(
            "qty"                     => "qty",
            "stock_status"            => "is_in_stock",
            "manage_stock"            => "manage_stock",
            "use_config_manage_stock" => "use_config_manage_stock"
            )
        )->addAttributeToSelect(array('qty', 'stock_status', 'manage_stock', 'use_config_manage_stock'));

        $collection->getSelect()->group('e.entity_id');

        if (!empty($config['hide_no_stock'])) {
            Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
        }

        return $collection->load();
    }

}