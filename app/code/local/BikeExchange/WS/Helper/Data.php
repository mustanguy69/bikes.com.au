<?php

class BikeExchange_WS_Helper_Data extends Mage_Core_Helper_Abstract
{

    public function bikeExchangeApiCall($action, $method, $data = null) {

        $apiKey = Mage::getStoreConfig('bikeexchange/bikeexchange-ws/bikeexchange_apikey', Mage::app()->getStore());
        $endpoint = Mage::getStoreConfig('bikeexchange/bikeexchange-ws/bikeexchange_endpoint', Mage::app()->getStore());

        $curl = curl_init();
        if ($method == "POST") {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        } elseif ($method == "PUT") {
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        } elseif ($method == "GET") {
            curl_setopt($curl, CURLOPT_HTTPGET, 1);
        } elseif ($method == "DELETE") {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
        }

        curl_setopt_array($curl, array(
            CURLOPT_URL => $endpoint. '/' .$action,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "Content-Type: application/vnd.api+json",
                "Authorization: Bearer " . $apiKey
            ),
        ));

        $response = curl_exec($curl);
        if ($response == '') {
            $response = curl_error($curl);
        }

        curl_close($curl);

        return $response;

    }

    public function createUpdateAdvert($product) {
        if ($product->getStatus() == 2) {
            $status = false;
        } elseif ($product->getStatus() == 1) {
            $status = true;
        }

        $categoryIds = $product->getCategoryIds();
        $to_remove = [2, 356, 350, 351, 354];
        $categoryIds = array_diff($categoryIds, $to_remove);
        $categoryId = end($categoryIds);
        $category = Mage::getModel('catalog/category')->load($categoryId);
        $categoryName = $category->getName();
        $categoryTransformed = $this->transformCategory($categoryName);

        $description = preg_replace('#<[^>]+>#', ' ', $product->getDescription());

        $advertData = [
            'data' => [
                'type' => 'adverts',
                'attributes' => [
                    'title' => $product->getName(),
                    'description' => $product->getShortDescription(),
                    'specifications' => $description,
                    'condition' => 'new',
                    'price' => $product->getPrice(),
                    'published' => false,
                    'taxon_slug' => $categoryTransformed,
                    'brand_slug' => $product->getAttributeText('brands'),
                    'custom_code' => $product->getEntityId(),
                ],
            ],
        ];

        if ($product->getPrice() != $product->getFinalPrice()) {
            $advertData['data']['attributes']['sale_price'] =  $product->getFinalPrice();
        }

        return $advertData;
    }

    public function createVariants($product) {
        $variantData = [
            'data' => [
                'type' => 'variants',
                'attributes' => [
                    'count_on_hand' => 1,
                ],
                "relationships" => [
                    "option_values" => [
                        [
                            'type' => 'option_values',
                            'id' => 2439,
                        ],
                        [
                            'type' => 'option_values',
                            'id' => 4118,
                        ],
                    ],
                ],
            ],
        ];

        return $variantData;
    }

    public function createImage($product) {
        $imageUrl = $product->getImageUrl();
        $imageData = [
            'image_url' => $imageUrl,
        ];

        return $imageData;
    }

}