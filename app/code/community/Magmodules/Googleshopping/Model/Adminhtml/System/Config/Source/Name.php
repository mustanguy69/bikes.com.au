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

class Magmodules_Googleshopping_Model_Adminhtml_System_Config_Source_Name
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $optionArray = array();
        $backendTypes = array('text', 'select', 'textarea', 'date', 'int', 'boolean', 'static', 'varchar', 'decimal');
        $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
            ->setOrder('frontend_label', 'ASC')
            ->addFieldToFilter('backend_type', $backendTypes);
        
        foreach ($attributes as $attribute) {
            if ($attribute->getData('frontend_label')) {
                $label = str_replace("'", "", $attribute->getData('frontend_label'));
            } else {
                $label = str_replace("'", "", $attribute->getData('attribute_code'));
            }

            $optionArray[] = array(
                'value' => $attribute->getData('attribute_code'),
                'label' => $label,
            );
        }

        $optionArray[] = array('value' => 'use_custom', 'label' => Mage::helper('googleshopping')->__('-- Custom:'));
        return $optionArray;
    }

}
