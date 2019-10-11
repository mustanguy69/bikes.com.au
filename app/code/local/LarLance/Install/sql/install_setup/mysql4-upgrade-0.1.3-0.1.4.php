<?php
/** @var LarLance_Install_Model_Resource_Setup $installer */
$installer = $this;
$installer->startSetup();

$content = '<div  class="widget-product zip-money-holder" zm-asset="productwidget" zm-widget="popup" zm-popup-asset="termsdialog">
    <div class="zip-money-button">
        <div class="text-block">
            <strong>own</strong> it now, up to 6 months interest free <span class="learn-more">learn more</span>
        </div>
    </div>
</div>';
$staticBlock = array(
    'title' => 'Zipmoney Button Block',
    'identifier' => 'zipmoney-button-block',
    'content' => $content,
    'is_active' => 1,
    'stores' => array(0, 1)
);

$installer->addCmsBlock($staticBlock, true);

?>