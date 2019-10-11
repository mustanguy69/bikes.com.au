<?php
$installer = $this;
$installer->startSetup();
$updateBlock = true;

$content ='<div class="purchase-guarantees row">
                <div class="col-sm-12 banner-item">
                     <img src="/skin/frontend/tv_bikeworld_package/tv_bikeworld2/images/purchase_guarantees.png" alt="purchase-guarantees"/>
                </div>
            </div>';
$staticBlock = array(
    'title' => 'Purchase Guarantees',
    'identifier' => 'purchase_guarantees',
    'content' => $content,
    'is_active' => 1,
    'stores' => array(0, 1)
);
$installer->addCmsBlock($staticBlock, $updateBlock);

$installer->endSetup();
