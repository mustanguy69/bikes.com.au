<?php
$installer = Mage::getResourceModel('catalog/setup', 'catalog_setup');
$installer->startSetup();

$attributeCode = 'delivery_method';
$installer->removeAttribute(Mage_Catalog_Model_Product::ENTITY, $attributeCode);
$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, $attributeCode, array(
                        'label'             => 'Delivery Method',
                        'type'              => 'int',
                        'input'             => 'select',
                        'backend'           => '',
                        'frontend'          => '',
                        'source'		    => 'eav/entity_attribute_source_table',
                        'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
                        'visible'           => true,
                        'required'          => false,
                        'user_defined'      => true,
                        'searchable'        => false,
                        'filterable'        => false,
                        'comparable'        => false,
                        'option'            => array ('value'  => array('deliveryonly' => array('Delivery Only'),
                                                                  'deliveryclickcollect' => array('Delivery and Click and Collect'),
                                                                  'clickcollectonly' => array('Click and Collect Only')
                                                                )
                                                    ),
                        'visible_on_front'  => true,
                        'visible_in_advanced_search' => false,
                        'unique'            => false
                        ));


$entityTypeId = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();
$attributeSets = Mage::getResourceModel('eav/entity_attribute_set_collection')
                    ->addFieldToFilter('entity_type_id', $entityTypeId)
                    ->load();

foreach($attributeSets as $attributeSet) {
    $installer->addAttributeToSet(Mage_Catalog_Model_Product::ENTITY, $attributeSet->getAttributeSetId(), 'General', $attributeCode);
}

$installer->endSetup();
