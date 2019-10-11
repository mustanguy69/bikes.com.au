<?php
// die('tuta');
error_reporting(E_ALL);
ini_set('max_execution_time', 600);
ini_set('display_errors', 1);
require_once('../app/Mage.php');
Mage::app('admin');
require_once 'abstract.php';

class Mage_Shell_Category extends Mage_Shell_Abstract

{

    public function run(){   

        require("PHPExcel-1.8/Classes/PHPExcel/IOFactory.php"); 
        $file="../media/files/bikes.xls";
        $data = array();
        $exReader = PHPExcel_IOFactory::createREaderForFile($file);
        $object = $exReader->load($file);
        $sheets = $object->getActiveSheet();
        $import = array();
        $count = 4; //count to last ($sku) == $data[$row][4];
        $lastRow = $sheets->getHighestRow();        
        for($row=2; $row<=$lastRow; $row++){
            $data[$row][0] = (string)$sheets->getCell('B'.$row)->getValue();
            $data[$row][1] = (string)$sheets->getCell('C'.$row)->getValue();
            $data[$row][2] = (string)$sheets->getCell('D'.$row)->getValue();
            $data[$row][3] = (string)$sheets->getCell('E'.$row)->getValue();
            $data[$row][4] = (string)$sheets->getCell('F'.$row)->getValue();
            
            if($data[$row][2] != null){
                if ($data[$row][0] != null){
                    $catName = $data[$row][0];
                } else {
                    $catName = $data[$row][1];
                }
                $_category = Mage::getResourceModel('catalog/category_collection')
                        ->addFieldToFilter('name', $catName)
                        ->getFirstItem();
                $categoryId = $_category->getId();
                if ($categoryId ==null){
                    die("wrong name category - ". $catName);
                } else{
                    $category = Mage::getModel('catalog/category')->load($categoryId);
                    $productCollection = Mage::getResourceModel('catalog/product_collection')
                                    ->addCategoryFilter($category)
                                    ->getAllIds();
                    foreach ($productCollection as $productId) {
                    $product = Mage::getModel('catalog/product')->load($productId);
                        for ($i=2; $i <=$count; $i++) {
                            $newproductId = Mage::getModel("catalog/product")->getIdBySku($data[$row][$i]);
                            if ($newproductId == false){
                                die("wrong SKU product -(".$data[$row][$i].") for category ". $catName. "</br>");                            
                            } else {
                                $newRelatedProducts = Mage::getModel('catalog/product')->load($newproductId);
                                $relatedProductsArranged[$newRelatedProducts->getId()] = array('position' => '');
                                $import[] = $product->setRelatedLinkData($relatedProductsArranged);                                
                            }
                        }
                    }
                }
            }               
        }
        foreach ($import as $value) {
            $value->save();
        }
        echo "data loaded";              
    }    
}

$obj = new Mage_Shell_Category();
$obj->run();
?>