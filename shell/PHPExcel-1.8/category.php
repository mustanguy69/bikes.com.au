<?php
error_reporting(E_ALL);
require_once('app/Mage.php');
ini_set('max_execution_time', 600);
ini_set('display_errors', 1);
Mage::app('admin');

class CAT
{

    public function index(){   
        
        require("PHPExcel-1.8/Classes/PHPExcel/IOFactory.php"); 
        $file="bikes.xls";
        $data = array();
        $exReader = PHPExcel_IOFactory::createREaderForFile($file);
        $object = $exReader->load($file);
        $sheets = $object->getActiveSheet();
        $lastRow = $sheets->getHighestRow();
        for($row=1;$row<=$lastRow;$row++){
            $data[$row][0] = $sheets->getCell('C'.$row)->getValue();
            $data[$row][1] = (string)$sheets->getCell('D'.$row)->getValue();
            $data[$row][2] = (string)$sheets->getCell('E'.$row)->getValue();
            $data[$row][3] = (string)$sheets->getCell('F'.$row)->getValue();

            if($data[$row][0] != null){
                $categoryId = $data[$row][0];
                $count = 3; //count($sku);
                $i=1;
                $category = Mage::getModel('catalog/category')->load($categoryId);
                $productCollection = Mage::getResourceModel('catalog/product_collection')
                                    ->addCategoryFilter($category)
                                    ->getAllIds();
                foreach ($productCollection as $productId) {
                    $product = Mage::getModel('catalog/product')->load($productId);
                    for ($i=1; $i <=$count; $i++) {
                        $newproductId = Mage::getModel("catalog/product")->getIdBySku( $data[$row][$i] );
                        $newRelatedProducts = Mage::getModel('catalog/product')->load($newproductId);
                        $relatedProductsArranged[$newRelatedProducts->getId()] = array('position' => '');
                        $product->setRelatedLinkData($relatedProductsArranged);
                        var_dump($product);
                        // $product->save();
                    }
                }                
            }               
        }
        echo "data loaded";        
    }    
}

$obj = new CAT();
$obj->index();
?>