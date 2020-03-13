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

    public function transformCategory($category)
    {
        switch ($category) {
            case "Racing Bikes":
            case "Road Bikes":
            case "Endurance Bikes":
            case "Sport Bikes":
                $taxon = "road-bikes";
                break;
            case "Triathlon Bikes":
                $taxon = "triathlon-time-trial-bikes";
                break;
            case "Cyclocross Bikes":
                $taxon = "cyclocross-bikes";
                break;
            case "Touring Bikes":
                $taxon = "touring-bikes";
                break;
            case "Mountain Bikes":
            case "Dirt Jump Bikes":
            case "Downhill Bikes":
            case "Dual Suspension Bikes":
            case "Recreational Bikes":
            case "Trail Hardtrail Bikes":
            case "XC Hardtrail Bikes":
                $taxon = "mountain-bikes";
                break;
            case "Fat Bikes":
                $taxon = "fat-bikes";
                break;
            case "Commuter Bikes":
            case "Urban Bikes":
                $taxon = "urban-bikes";
                break;
            case "Flat Bar Road Bikes":
                $taxon = "flat-bar-road-bikes";
                break;
            case "Fixie Bikes":
                $taxon = "fixie-bikes";
                break;
            case "Hybrid Bikes":
                $taxon = "hybrid-bikes";
                break;
            case "Kids Bikes":
            case "Single Speed Bikes":
            case "Geared Bikes":
            case "Balance Bikes":
            case "Tricycles":
            case "Baby & Toddler Tricycles":
            case "Boys Tricycles":
            case "Girl Tricycles":
                $taxon = "kids-bikes";
                break;
            case "Scooters":
            case "2 Wheel Scooters":
            case "Toddler Scooters":
            case "Electric Scooters":
                $taxon = "kids-scooters";
                break;
            case "Scooter Wheel":
                $taxon = "scooter-wheels";
                break;
            case "Scooter Bars":
                $taxon = "scooter-handlebars";
                break;
            case "Scooter Clamps":
                $taxon = "scooter-clamps";
                break;
            case "Scooter Accessories":
                $taxon = "scooter-parts";
                break;
            case "Tag Along Bikes":
                $taxon = "family-bikes";
                break;
            case "Retro / Vintage Bikes":
                $taxon = "vintage-bikes";
                break;
            case "Cruiser Bikes":
                $taxon = "cruiser-bikes";
                break;
            case "BMX Bikes":
            case "Street BMX Bikes":
            case "Cruiser BMX Bikes":
            case "Retro BMX Bikes":
                $taxon = "freestyle-bmxs";
                break;
            case "Folding Bikes":
                $taxon = "folding-bikes";
                break;
            case "Electric Bikes":
                $taxon = "electric-bikes";
                break;
            case "Trikes":
                $taxon = "kids-trikes";
                break;
            case "Cycling Backpacks":
                $taxon = "bike-backpacks";
                break;
            case "Bikes Saddle Bags":
                $taxon = "bike-saddle-bags";
                break;
            case "Front Pannier Bags":
            case "Rear Pannier Bags":
            case "Top Pannier Bags":
                $taxon = "pannier-bags";
                break;
            case "Bike Frame Bags":
                $taxon = "bike-frame-bags";
                break;
            case "Front Handelbar Bags":
                $taxon = "handlebar-bags";
                break;
            case "Bike Travel Bags":
                $taxon = "travel-bags";
                break;
            case "Cycling Water Bottles":
                $taxon = "bike-bottles-bidons";
                break;
            case "Bike Bottle Cage":
            case "Carbon Bottle Cage":
            case "Alloy Bottle Cage":
            case "Plastic Bottle Cage":
                $taxon = "bike-bottle-cages";
                break;
            case "Bike Bells $ Horn":
                $taxon = "bike-bells-horns";
                break;
            case "Bike Computers":
            case "Wireless Bike Computers":
            case "Bluetooh Bike Computers":
            case "Bike GPS":
            case "Bike Speedometers":
                $taxon = "cycling-computers";
                break;
            case "Bike Grips":
                $taxon = "handlebar-grips";
                break;
            case "Bike Bar Ends":
                $taxon = "handlebar-bar-ends";
                break;
            case "Bike Pumps":
            case "Floor Pumps":
                $taxon = "bike-floor-pumps";
                break;
            case "Mini Bike Pumps":
                $taxon = "bike-hand-pumps";
                break;
            case "CO2 Inflators":
            case "CO2 Canisters":
                $taxon = "bike-co2-pumps";
                break;
            case "Bike Helmets":
                $taxon = "bike-co2-pumps";
                break;
            default:
                $taxon = "other-bikes";
                break;
        }

        return $taxon;
    }

}