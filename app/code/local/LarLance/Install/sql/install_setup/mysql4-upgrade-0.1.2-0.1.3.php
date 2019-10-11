<?php
$installer = $this;
/** @var LarLance_Install_Model_Resource_Setup $installer */
$installer->startSetup();

$content = '<div class="widget-product zip-money-holder" zm-asset="productwidget" zm-widget="popup" zm-popup-asset="termsdialog">
<div class="zipmoney-strip-block">
<div class="image-block"><img alt="image description" src="{{skin url=\'images/logo-zipmoney-new.png\'}}" /></div>
<div class="text-block">
<h2>Get 6 months Interest Free. <div class="mobile-hidden"><span class="marked">Buy now and pay later</span> with zip money.</div></h2>
</div>
</div>
</div>';
$staticBlock = array(
    'title' => 'Zipmoney Block',
    'identifier' => 'zipmoney-strip-block',
    'content' => $content,
    'is_active' => 1,
    'stores' => array(0, 1)
);

$installer->addCmsBlock($staticBlock, true);

?>