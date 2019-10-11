<?php
/**
 * @category  Aligent
 * @package   zipmoney
 * @author    Andi Han <andi@aligent.com.au>
 * @copyright 2014 Aligent Consulting.
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.aligent.com.au/
 */
/** @var $installer Mage_Sales_Model_Resource_Setup */
$installer = new Mage_Sales_Model_Resource_Setup('core_setup');;
$installer->startSetup();

$installer->addAttribute('creditmemo', 'zipmoney_txn_id', array(
    'label'     => 'zipMoney txn id',
    'type'      => 'varchar',
    'required'  => false,
    'visible'   => false
));

$installer->addAttribute('creditmemo', 'refund_reference', array(
    'label'     => 'Refund reference value',
    'type'      => 'varchar',
    'required'  => false,
    'visible'   => false
));

$installer->endSetup();
