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

$installer = new Mage_Catalog_Model_Resource_Eav_Mysql4_Setup('core_setup');
$installer->startSetup();

// Add New Product Attribute Group - Google Shoppinh
$attributeSetId = Mage::getModel('catalog/product')->getDefaultAttributeSetId();
$attributeSet = Mage::getModel('eav/entity_attribute_set')->load($attributeSetId);
$installer->addAttributeGroup('catalog_product', $attributeSet->getAttributeSetName(), 'Google Shopping', 1000);

// Add New Product Attribute - Yes/No googleshopping_enabled
$installer->addAttribute(
    'catalog_product', 'googleshopping_exclude', array(
    'group'                      => 'Google Shopping',
    'input'                      => 'select',
    'type'                       => 'int',
    'source'                     => 'eav/entity_attribute_source_boolean',
    'label'                      => 'Exclude for Google Shopping',
    'visible'                    => 1,
    'required'                   => 0,
    'user_defined'               => 1,
    'searchable'                 => 0,
    'filterable'                 => 0,
    'comparable'                 => 0,
    'visible_on_front'           => 0,
    'visible_in_advanced_search' => 0,
    'is_html_allowed_on_front'   => 0,
    'used_in_product_listing'    => 1,
    'global'                     => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
    )
);

// Add New Product Attribute - Condition
$installer->addAttribute(
    'catalog_product', 'googleshopping_condition', array(
    'group'                      => 'Google Shopping',
    'input'                      => 'select',
    'type'                       => 'int',
    'backend'                    => 'eav/entity_attribute_backend_array',
    'option'                     => array(
        'value' => array(
            'new'         => array('New'),
            'refurbished' => array('Refurbished'),
            'used'        => array('Used')
        )
    ),
    'default'                    => 'new',
    'label'                      => 'Product Condition',
    'visible'                    => 1,
    'required'                   => 0,
    'user_defined'               => 1,
    'searchable'                 => 0,
    'filterable'                 => 0,
    'comparable'                 => 0,
    'visible_on_front'           => 0,
    'visible_in_advanced_search' => 0,
    'is_html_allowed_on_front'   => 0,
    'used_in_product_listing'    => 1,
    'global'                     => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    )
);

// Add New Category Attribute - Google Product Category
$installer->addAttribute(
    'catalog_category', 'googleshopping_category', array(
    'group'        => 'Feeds',
    'input'        => 'text',
    'type'         => 'varchar',
    'label'        => 'Google Product Category',
    'required'     => false,
    'user_defined' => true,
    'visible'      => true,
    'global'       => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    )
);

$installer->endSetup();