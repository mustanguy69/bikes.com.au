<?php
	class Tatvic_Uaee_Model_Observer
	{
		public function MageInfoConfigDisable(){
		
			$newTimeStamp = date('Y-m-d H:i:s');
			$email = Mage::getStoreConfig('tatvic_uaee/general/email_id');
			$email = $email."-".$newTimeStamp;
			$domain = Mage::getBaseUrl (Mage_Core_Model_Store::URL_TYPE_WEB);
			$this->send_email_to_tatvic($email, $domain);
	}
		public function MageInfoConfig(){
				$email = Mage::getStoreConfig('tatvic_uaee/general/email_id');
				$domain = Mage::getBaseUrl (Mage_Core_Model_Store::URL_TYPE_WEB);
				$token = Mage::getStoreConfig('tatvic_uaee/general/ref_token');
				$this->send_email_to_tatvic($email, $domain, $token);
		}
		public function send_email_to_tatvic($email, $domain_name, $token) {
		   //set POST variables
		   //$url = "http://dev.tatvic.com/leadgen/woocommerce-plugin/store_email/";
		   $url = "http://dev.tatvic.com/leadgen/woocommerce-plugin/store_email/actionable_ga/";

		   $fields = array(
			   "email" => urlencode($email),
			   "domain_name" => urlencode($domain_name),
			   "tvc_tkn" => $token,
			   "store_type" => "Magento"
			   
		   );
		   
		   foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
			rtrim($fields_string, '&');

					$ch = curl_init();

				//set the url, number of POST vars, POST data
				curl_setopt($ch,CURLOPT_URL, $url);
				curl_setopt($ch,CURLOPT_POST, count($fields));
				curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
				
				//execute post
				$result = curl_exec($ch);

				//close connection
				curl_close($ch);
		}
		public function adminSystemConfigChangedSection()
		{
		
			if(Mage::getStoreConfigFlag('tatvic_uaee/general/enable')){
				
				$in_model = Mage::getStoreConfig('tatvic_uaee/general/installMail',Mage::app()->getStore());
				if($in_model == 0){
					$tvcDataUpdate = new Mage_Core_Model_Config();
					$tvcDataUpdate->saveConfig('tatvic_uaee/general/installMail', 1, 'default', 0);
					$tvcDataUpdate->saveConfig('tatvic_uaee/general/uninstallMail', 0, 'default', 0);
					$this->MageInfoConfig();
					
				}
				
			}
			else{
				$un_model = Mage::getStoreConfig('tatvic_uaee/general/uninstallMail',Mage::app()->getStore());
				if($un_model==0){
					$tvcDataUpdate = new Mage_Core_Model_Config();
					$tvcDataUpdate->saveConfig('tatvic_uaee/general/installMail', 0, 'default', 0);
					$tvcDataUpdate->saveConfig('tatvic_uaee/general/uninstallMail', 1, 'default', 0);
					$this->MageInfoConfigDisable();
				}
				
			}
				
			//	exit;
			
			
		}
		
		
	}