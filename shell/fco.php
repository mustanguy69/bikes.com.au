<?php
/**
 * Created by PhpStorm.
 * User: killm
 * Date: 26.06.2018
 * Time: 14:35
 */

require_once 'abstract.php';


class Mage_Shell_Custom extends Mage_Shell_Abstract
{
    public function run()
    {
        if ($this->getArg('run')) {
            $this->findCustomColorOptions();
        } else {
            echo $this->usageHelp();
        }
    }

    public function usageHelp()
    {
        return <<<USAGE
Usage: php -f fco.php -- [options]
       php -f custom     -- run
       
  run       finished!
  help          This help
 
USAGE;
    }

    public function findCustomColorOptions()
    {
        $products = Mage::getModel('catalog/product')->getCollection();
        $products->joinTable('catalog/product_option', 'product_id=entity_id', array('main_option_id' => 'option_id'));
        $products->joinTable('catalog/product_option_title', 'option_id=main_option_id', array('option_title' => 'title'));
        $products->addFieldToFilter('option_title', 'Colour');
        $logfile = $this->getUploadDir() . DIRECTORY_SEPARATOR . 'products_' . date('_Y_m_d_H_i_s') . ".csv";
        $logfileHandle = fopen($logfile, 'w');
        foreach ($products as $product) {
            $csvLine = array();
            if ($product->getSku()) {
                $csvLine[] = $product->getSku();
                fputcsv($logfileHandle, $csvLine, ";");
            }
        }
        fclose($logfileHandle);
        echo "Finished - " . $logfile;
    }

    public function getUploadDir()
    {
        return Mage::getBaseDir() . DIRECTORY_SEPARATOR . 'var/custom-options-product' . DIRECTORY_SEPARATOR;
    }
}

(new Mage_Shell_Custom())->run();