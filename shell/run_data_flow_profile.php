<?php
 
require_once 'abstract.php';
 
/**
 * @author Matthias Kerstner <matthias@kerstner.at>
 * @version 1.0.0
 */
class Mage_Shell_Run_Data_Flow_Profile extends Mage_Shell_Abstract {
 
  /** @var string this module's namespace */
  private static $_MODULE_NAMESPACE = 'kerstnerat_rundataflowprofileshell';
 
  /**
   * Logs $msg to logfile specified in configuration.
   * @param string $msg
   */
  private function logToFile($msg) {
    Mage::log($msg, null, self::$_MODULE_NAMESPACE . '.log');
  }
 
  /**
   * Run script based on shell arguments specified.
   */
  public function run() {
    try {
      if (!$this->getArg('profile')) {
        throw new Exception('Missing profile');
      }
 
      $profileId = (int) $this->getArg('profile');
 
      $this->logToFile('Profile started: ' . $profileId . ' at ' . date('Y-m-d H:i:s')
        . '...');
 
      $profile = Mage::getModel('dataflow/profile');
      $userModel = Mage::getModel('admin/user');
      $userModel->setUserId(0);
 
      Mage::getSingleton('admin/session')->setUser($userModel);
      $profile->load($profileId);
 
      if (!$profile->getId()) {
        $this->logToFile('error: ' . $profileId . ' - incorrect profile id');
        return;
      }
 
      Mage::register('current_convert_profile', $profile);
      $profile->run();
 
      $this->logToFile('Profile ended: ' . $profileId . ' at ' . date('Y-m-d H:i:s'));
    } catch (Exception $ex) {
      $this->logToFile($ex->getMessage());
      echo $this->usageHelp();
    }
  }
 
 /**
  * Retrieve Usage Help Message.
  */
 public function usageHelp() {
   return " 
Usage: php -f run_data_flow_profile.php --[options]
  
 --profile  Data Flow Profile ID
 help Show this help
  
  ID of Data Flow Profiles to run";
 }
}
 
$shell = new Mage_Shell_Run_Data_Flow_Profile();
$shell->run();