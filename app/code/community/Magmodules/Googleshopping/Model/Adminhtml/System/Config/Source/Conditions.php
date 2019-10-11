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

class Magmodules_Googleshopping_Model_Adminhtml_System_Config_Source_Conditions
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $type = array();
        $type[] = array('value' => '', 'label' => Mage::helper('googleshopping')->__(''));
        $type[] = array('value' => 'eq', 'label' => Mage::helper('googleshopping')->__('Equal'));
        $type[] = array('value' => 'neq', 'label' => Mage::helper('googleshopping')->__('Not equal'));
        $type[] = array('value' => 'gt', 'label' => Mage::helper('googleshopping')->__('Greater than'));
        $type[] = array('value' => 'gteq', 'label' => Mage::helper('googleshopping')->__('Greater than or equal to'));
        $type[] = array('value' => 'lt', 'label' => Mage::helper('googleshopping')->__('Less than'));
        $type[] = array('value' => 'lteg', 'label' => Mage::helper('googleshopping')->__('Less than or equal to'));
        $type[] = array('value' => 'in', 'label' => Mage::helper('googleshopping')->__('In'));
        $type[] = array('value' => 'nin', 'label' => Mage::helper('googleshopping')->__('Not in'));
        $type[] = array('value' => 'like', 'label' => Mage::helper('googleshopping')->__('Like'));
        $type[] = array('value' => 'empty', 'label' => Mage::helper('googleshopping')->__('Empty'));
        $type[] = array('value' => 'not-empty', 'label' => Mage::helper('googleshopping')->__('Not Empty'));
        return $type;
    }

}