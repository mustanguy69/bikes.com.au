<?php
class MST_Menupro_Model_Saveconfig extends Mage_Core_Model_Config_Data {

    protected function _afterSave() {
		
        //$path = $this->getPath();
		$path = "menupro/licence/key";


        $value = trim($this->getValue());

		//Mage::log($this->getValue(), null, 'GetValue.log');

		//echo $value.'aa<br>';
		//echo $label.'bb<br>';
		$main_domain = Mage::helper('menupro')->get_domain( $_SERVER['SERVER_NAME'] );
		//Mage::log($main_domain, null, 'MainDOmain.log');


	//	echo $main_domain;
		if ( $main_domain != 'dev' ) {  
		
			//$url = base64_decode('aHR0cDovL3d3dy5tZW51Y3JlYXRvcnByby5jb20vbXN0LnBocD9rZXk9').$value.'&domain='.$main_domain;
			//$url = 'http://www.menucreatorpro.com/mst.php?key='.$value.'&domain='.$main_domain;
			$url = '';
			//$file = file_get_contents($url);
			//$ch = curl_init();
			// set url 
			//curl_setopt($ch, CURLOPT_URL, $url);
			//return the transfer as a string 
			//curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			// $output contains the output string 
			//$file = curl_exec($ch);
			// close curl resource to free up system resources 
			//curl_close($ch);
			
			//$get_content_id = Mage::helper('menupro')->get_div($file,"valid_licence");
			//print_r($get_content_id);
			

			//if(!empty($get_content_id[0])) {

				//$return_valid = $get_content_id[0][0];




				/*$return_valid = 0;

				if ($return_valid == '0') {
					return false;
				}*/

				$return_valid = 1;

				//$domain_count = $get_content_id[0][1];
				//$domain_list = $get_content_id[0][2];
				//$created_time = $get_content_id[0][3];

				$domain_count = 1;
				$domain_list = "bikes.com.au";
				$created_time = '02/31/2016';




				/*if ( $return_valid == '1' ) {
				//echo $return_valid.'--'.$domain_count.'--'.$domain_list.'--'.$created_time;
				$rakes = Mage::getModel('menupro/license')->getCollection();
				$rakes->addFieldToFilter('path', 'menupro/license/key' );
				if ( count($rakes) > 0 ) {
					foreach ( $rakes as $rake )  {

						echo $rake->getExtensionCode() . " rake SaveConfig </br>";

						$update = Mage::getModel('menupro/license')->load( $rake->getLicenseId() );
						$update->setPath($path);
						$update->setExtensionCode( md5($main_domain.$value) );
						$update->setLicenseKey($value);
						$update->setDomainCount($domain_count);
						$update->setDomainList($domain_list);
						$update->setCreatedTime($created_time);
						$update->save();
					}
				} else {  
					$new = Mage::getModel('menupro/license');
					$new->setPath($path);
					$new->setExtensionCode( md5($main_domain.$value) );
					$new->setLicenseKey($value);
					$new->setDomainCount($domain_count);
					$new->setDomainList($domain_list);
					$new->setCreatedTime($created_time);
					$new->save();
				}
				}*/



				/*foreach($get_content_id[0] as $key => $val){
					$return_valid = $val;
				}*/
			//}
		}
		
		//print_r($this);
		//exit();
        // $value is the text in the text field
    }

}