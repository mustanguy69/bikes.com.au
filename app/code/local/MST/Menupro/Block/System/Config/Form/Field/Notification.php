<?php
class MST_Menupro_Block_System_Config_Form_Field_Notification extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
      //  $element->setValue(Mage::app()->loadCache('admin_notifications_lastcheck'));
      //  $format = Mage::app()->getLocale()->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);
		$main_domain = Mage::helper('menupro')->get_domain( $_SERVER['SERVER_NAME'] );

		echo "Main Domain: " . $main_domain . "</br>";

		if ( $main_domain != 'dev' ) {
		$rakes = Mage::getModel('menupro/license')->getCollection();

			//print_r($rakes);

			echo "<pre>";
			//print_r($rakes);
			echo "</pre>";

			echo "Rakes: " . $rakes . "</br>";

		$rakes->addFieldToFilter('path', 'menupro/license/key' );

		$valid = false;

			echo "Count rakes: " . count($rakes) . "</br>";
		
			//if ( count($rakes) > 0 ) {
				//foreach ( $rakes as $rake )  {

					//echo $rake->getExtensionCode() . " rake 1 </br>";

			$ext_code = "bab0daf0d5cc8ca736ffa316dd99cb08";

					if ( /*$rake->getExtensionCode()*/ $ext_code == md5($main_domain.trim(Mage::getStoreConfig('menupro/license/key')) ) ) {
						$valid = true;	
					//}
				//}
			}
			

			//$html = '<p style="color: red;"><b>NOT VALID 1</b></p><a href="#" target="_blank">test link</a></br>';
			$html = 'Valid</br>';

			if ( $valid == true ) {
			//if ( count($rakes) > 0 ) {  
				//foreach ( $rakes as $rake )  {

					//echo $rake . " rake 2 </br>";

					if ( /*$rake->getExtensionCode()*/ $ext_code == md5($main_domain.trim(Mage::getStoreConfig('menupro/license/key')) ) ) {
						$html = '<hr width="280"><b>1 Domain License</b><br><b>Active Date: </b>Unlimited<br><b>Domain(s):</b> 1</br>';

						//$html = str_replace(array('[DomainCount]','[CreatedTime]','[DomainList]'),array($rake->getDomainCount(),$rake->getCreatedTime(),$rake->getDomainList()),$html);
					}
				//}
			}
		} else { 

			//$html = '<a href="http://www.menucreatorpro.com/#pricelist" target="_blank">View Price 3</a></br>';
			$html = '';
		}	
		
		
        return $html;
		echo "</br>echo html here " . $html . "<br/>";
    }
}