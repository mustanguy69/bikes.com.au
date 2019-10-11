<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Shell
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

require_once 'abstract.php';

/**
 * Magento Compiler Shell Script
 *
 * @category    Mage
 * @package     Mage_Shell
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Shell_ExportProductsSql extends Mage_Shell_Abstract
{
    /**
     * Run script
     *
     */
    public function run()
    {
        $maxJoinAttributes = 61 - 2;
        $ignoredAttributes = array(
            'custom_design',
            'custom_design_from',
            'custom_design_to',
            'layout_update',
            'news_from_date',
            'news_to_date',
            'page_layout',
            'tax_class_id',
            'custom_layout_update',
            'country_of_manufacture',
            'group_price',
        );

        /** @var Mage_Catalog_Model_Resource_Product_Collection $collection */
        $collection = Mage::getModel('catalog/product')->getCollection();

        $productAttrs = Mage::getResourceModel('catalog/product_attribute_collection');
        $allAttributes = array();
        $ignoredAttributes = array();

        $collection
            ->addAttributeToSelect('*')
            ->addPriceData()
            ->addFinalPrice()
        ;

        foreach ($productAttrs as $productAttr) { /** @var Mage_Catalog_Model_Resource_Eav_Attribute $productAttr */
            $attributeCode = $productAttr->getAttributeCode();

            echo $attributeCode. PHP_EOL;
            $allAttributes[] = $attributeCode;

            if (!in_array($attributeCode, $ignoredAttributes) && ($maxJoinAttributes-- > 0)) {
                $collection->addAttributeToFilter($attributeCode, array('like' => '%'));
            } else {
                $ignoredAttributes[] = $attributeCode;
            }
        }

        echo PHP_EOL . PHP_EOL;

        echo "Ignore attribute:" . PHP_EOL;
        foreach ($ignoredAttributes as $ignoredAttribute) {
            echo $ignoredAttribute . PHP_EOL;
        }

        echo PHP_EOL . PHP_EOL;


        echo "" . $collection->getSelect();


    }

    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f getAllProductsSql.php -- [options]

USAGE;
    }
}

$shell = new Mage_Shell_ExportProductsSql();
$shell->run();
