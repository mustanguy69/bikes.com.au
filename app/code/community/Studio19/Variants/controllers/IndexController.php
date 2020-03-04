<?php
 /**
 * Variants frontend controller
 *
 * @author Magento
 */
class Studio19_Variants_IndexController extends Mage_Core_Controller_Front_Action
{
    const ACTION_TYPE_ENTITY = 'entity';
    const ACTION_TYPE_COLLECTION  = 'collection';
    
    public function indexAction()
    {   
        $store = $this->_getStore();

        // Parameters for debugging:
        // $debug should default to false, but if true, will allow viewing raw product data in the response.
        // $attributes are comma separated extra attributes to select for viewing in the raw data.
        $debug = Mage::app()->getRequest()->getParam('debug') === 'true';
        $attributes = Mage::app()->getRequest()->getParam('attributes');

        if ($attributes)
            $attributes = explode(',', $attributes);
        else
            $attributes = array();

        // Paging parameters. Can be given in querystring or as rewwritten form (/top/50/skip/0/).
        $top = Mage::app()->getRequest()->getParam('top');
        $skip = Mage::app()->getRequest()->getParam('skip');

        // Extra filtering parameters.
        $excludeLinked = Mage::app()->getRequest()->getParam('excludeLinked') === 'true';

        // Generous defaults, should the parameters not be provided.
        if (!$top)
            $top = 500;
        if (!$skip)
            $skip = 0;
        
        // Fetch only configurable and simple products, assuming other product types will not be rented.
        // Bundled products may be supported, but only where the quantity is not user provided.
        $collection = Mage::getResourceModel('catalog/product_collection')
            ->addStoreFilter($store->getId())
            ->addPriceData($this->_getCustomerGroupId(), $store->getWebsiteId())
            ->addAttributeToSelect(array_merge($attributes, array('name', 'description', 'short_description', 'manufacturer_value', 'image', 'small_image')))
            ->addAttributeToFilter('visibility', array('neq' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE))
            ->addAttributeToFilter('status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED))
            ->addAttributeToFilter('type_id', array('in' => array(
                Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
                Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE)));

        $select = $collection->getSelect();
        
        // Filter out simple products which are linked to a configurable/bundled product; these will be included as variants of the
        // parent product.
        if ($excludeLinked)
        {
            $select
                ->joinLeft(array('link_table' => 'catalog_product_super_link'), 'link_table.product_id = e.entity_id', array('product_id'))
                ->where('link_table.product_id IS NULL');
        }
        
        $select->limit($top, $skip);
        $products = $collection->load();

        $productArray = array();

        /** @var Mage_Catalog_Model_Product $product */
        foreach ($products as $product) {
            $this->_prepareProductForResponse($product);
            $productData = $product->getData();
            $productOutput = [
                    'id' => $productData['entity_id'],
                    'title' => $productData['name'],
                    'link' => $productData['url'],
                    'image_link' => $productData['image_url'],
                    'description' => $productData['description'] ? $productData['description'] : $productData['short_description'],
                    'brand' => $productData['manufacturer_value'],
                    'price' => Mage::helper('core')->currency($productData['final_price_with_tax'], true, false),
                    'categories' => $productData['categories'],
                    'variants' => $productData['variants'],
                    'options' => $productData['custom_options'],
                ];

            foreach ($attributes as $attribute)
            {
                if ($attribute != '*')
                    $productOutput[$attribute] = $productData[$attribute];
            }

            // If $debug is true, add an extra property to dump the raw data out.
            if ($debug)
                $productOutput['raw_data'] = $productData;

            array_push($productArray, (object)$productOutput);
        }

        $response = (object)[
            'count' => sizeof($productArray),
            'items' => $productArray
        ];

        // Return a nextPage link only if we are not returning less than requested, i.e. the last remaining products.
        if ($top == sizeof($productArray))
            $response->nextPage = Mage::getUrl(Mage::app()->getRequest()->getRouteName(), array('top' => $top, 'skip' => $top + $skip));

        $json = Mage::helper('core')->jsonEncode($response);

        $this->getResponse()->setHeader('Content-Type', 'application/json');
        $this->getResponse()->setBody($json);

        return;
    }

    /**
     * Add special fields to product get response
     *
     * @param Mage_Catalog_Model_Product $product
     */
    protected function _prepareProductForResponse(Mage_Catalog_Model_Product $product)
    {
        /** @var $productHelper Mage_Catalog_Helper_Product */
        $productHelper = Mage::helper('catalog/product');
        $productData = $product->getData();
        $product->setWebsiteId($this->_getStore()->getWebsiteId());
        // customer group is required in product for correct prices calculation
        $product->setCustomerGroupId($this->_getCustomerGroupId());
        // calculate prices
        $finalPrice = $product->getFinalPrice();
        $productData['regular_price_with_tax'] = $this->_getPrice($product->getPrice(), $product, true);
        $productData['regular_price_without_tax'] = $this->_getPrice($product->getPrice(), $product, false);
        $productData['final_price_with_tax'] = $this->_getPrice($finalPrice, $product, true);
        $productData['final_price_without_tax'] = $this->_getPrice($finalPrice, $product, false);

        // Return a URL for either image type depending on which is available.
        if ($productData['image'])
            $productData['image_url'] = (string)Mage::helper('catalog/image')->init($product, 'image')->resize(500,500);
        else
            $productData['image_url'] = (string)Mage::helper('catalog/image')->init($product, 'small_image')->resize(500,500);

        // define URLs
        $productData['url'] = $productHelper->getProductUrl($product->getId());

        /** @var $stockItem Mage_CatalogInventory_Model_Stock_Item */
        $stockItem = $product->getStockItem();
        if (!$stockItem) {
            $stockItem = Mage::getModel('cataloginventory/stock_item');
            $stockItem->loadByProduct($product);
        }
        $productData['is_in_stock'] = $stockItem->getIsInStock();

        $categories = $product
            ->getCategoryCollection()
            ->addNameToResult();
        $productData['categories'] = array();

        foreach ($categories as $category) {
            // {
            //     "entity_id": "2",
            //     "entity_type_id": "3",
            //     "attribute_set_id": "3",
            //     "parent_id": "1",
            //     "created_at": "2017-04-12 04:52:46",
            //     "updated_at": "2017-04-12 04:52:46",
            //     "path": "1/2",
            //     "position": "1",
            //     "level": "1",
            //     "children_count": "1",
            //     "product_id": "1",
            //     "name": "Default Category"
            // }
            $categoryData = $category->getData();

            // Convert path to human readable form (i.e. using names rather than ids).
            if ($categoryData['path']) {
                $string = '';

                foreach (explode('/', $categoryData['path']) as $path_id) {
                    $categoryInfo = Mage::getModel('catalog/category')->load($path_id)->getData();

                    if ($categoryInfo) {
                        if (!$string)
                            $string = $categoryInfo['name'];
                        else
                            $string .= ' &gt; '.$categoryInfo['name'];
                    }
                }

                $categoryData['path'] = $string;
            }

            array_push($productData['categories'], $categoryData['path']);
        }

        // Depending on the product type, we may need to populate variants
        $productData['variants'] = array();

        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            $attributes = $product->getTypeInstance(true)
                ->getConfigurableAttributeCollection($product)
                ->load();

            $basePrice = $product->getFinalPrice();
            $pricesByAttributeValues = array();
            $attributeCodes = array();
            
            // Cache access to the prices for each attribute value.
            foreach ($attributes as $attribute) {
                array_push($attributeCodes, $attribute->getProductAttribute()->getAttributeCode());
                $prices = $attribute->getPrices();
                
                foreach ($prices as $price) {
                    $pricesByAttributeValues[$price['value_index']] = array('name' => $price['label']);

                    if ($price['is_percent'])
                        $pricesByAttributeValues[$price['value_index']]['price'] = (float)$price['pricing_value'] * $basePrice / 100;
                    else
                        $pricesByAttributeValues[$price['value_index']]['price'] = (float)$price['pricing_value'];
                }
            }
            
            $subProducts = $product->getTypeInstance(true)
                ->getUsedProductCollection($product)
                ->addAttributeToSelect(array_merge($attributeCodes, array('name', 'description', 'image')))
                ->load();

            foreach ($subProducts as $variant) {
                $variantData = $variant->getData();
                $totalPrice = $basePrice;
                $attributeValues = array();

                // Sum the total cost of the variation
                $dataArray = array();
                foreach ($attributes as $attribute) {
                    $code = $attribute->getProductAttribute()->getAttributeCode();
                    $attributeValues[$attribute->getData('label')] = array(
                        'id' => intval($variantData[$code]),
                        'name' => $pricesByAttributeValues[$variantData[$code]]['name']
                    );
                    
                    if (isset($pricesByAttributeValues[$variantData[$code]]))
                        $totalPrice += $pricesByAttributeValues[$variantData[$code]]['price'];
                }

                array_push($productData['variants'], (object)[
                        'id' => $variantData['entity_id'],
                        'title' => $variantData['name'],
                        'image_link' => (string)Mage::helper('catalog/image')->init($product, 'image')->resize(500,500),
                        'description' => $variantData['description'],
                        'attributes' => $attributeValues,
                        'price' => $totalPrice,
                    ]);
            }
        }

        // Find custom options configured for this product
        $customOptions = $product->getProductOptionsCollection();
        $productData['custom_options'] = array();

        foreach ($customOptions as $option) {
            // {
            //     "option_id": "1",
            //     "product_id": "1",
            //     "type": "checkbox",
            //     "is_require": "1",
            //     "sku": null,
            //     "max_characters": null,
            //     "file_extension": null,
            //     "image_size_x": null,
            //     "image_size_y": null,
            //     "sort_order": "0",
            //     "default_title": "Title",
            //     "store_title": null,
            //     "title": "Title",
            //     "default_price": null,
            //     "default_price_type": null,
            //     "store_price": null,
            //     "store_price_type": null,
            //     "price": null,
            //     "price_type": null
            // }

            $optionData = $option->getData();

            foreach ($option->getValues() as $value) {
                // {
                //     "option_type_id": "1",
                //     "option_id": "1",
                //     "sku": "123",
                //     "sort_order": "0",
                //     "default_title": "One option",
                //     "store_title": null,
                //     "title": "One option",
                //     "default_price": "12.0000",
                //     "default_price_type": "fixed",
                //     "store_price": null,
                //     "store_price_type": null,
                //     "price": "12.0000",
                //     "price_type": "fixed"
                // }
                $valueData = $value->getData();
                array_push($productData['custom_options'], (object)[
                    'id' => $valueData['option_type_id'],
                    'name' => $valueData['title'],
                    'category' => $optionData['title'],
                    'price' => floatval($valueData['price']),
                ]);
            }

        }

        $product->addData($productData);
    }

    protected function _getStore(){
        return Mage::app()->getStore($this->getRequest()->getParam('store'));
    }

    /**
     * Default implementation. May be different for customer/guest/admin role.
     *
     * @return null
     */
    protected function _getCustomerGroupId()
    {
        return null;
    }

    /**
     * Retrive tier prices in special format
     *
     * @return array
     */
    protected function _getTierPrices(Mage_Catalog_Model_Product $product)
    {
        $tierPrices = array();
        foreach ($product->getTierPrice() as $tierPrice) {
            $tierPrices[] = array(
                'qty' => $tierPrice['price_qty'],
                'price_with_tax' => $this->_applyTaxToPrice($tierPrice['price']),
                'price_without_tax' => $this->_applyTaxToPrice($tierPrice['price'], false)
            );
        }
        return $tierPrices;
    }

    /**
     * Default implementation. May be different for customer/guest/admin role.
     *
     * @param float $price
     * @param bool $withTax
     * @return float
     */
    protected function _applyTaxToPrice($price, $withTax = true)
    {
        $price = $this->_getPrice($price, $withTax, null, null, $customer->getTaxClassId());

        return $price;
    }

    /**
     * Get product price with all tax settings processing
     *
     * @param float $price inputed product price
     * @param Mage_Catalog_Model_Product $product
     * @param bool $includingTax return price include tax flag
     * @param null|Mage_Customer_Model_Address $shippingAddress
     * @param null|Mage_Customer_Model_Address $billingAddress
     * @param null|int $ctc customer tax class
     * @param bool $priceIncludesTax flag that price parameter contain tax
     * @return float
     * @see Mage_Tax_Helper_Data::getPrice()
     */
    protected function _getPrice($price, $product, $includingTax = null, $shippingAddress = null,
        $billingAddress = null, $ctc = null, $priceIncludesTax = null
    ) {
        $store = $this->_getStore();

        if (is_null($priceIncludesTax)) {
            /** @var $config Mage_Tax_Model_Config */
            $config = Mage::getSingleton('tax/config');
            $priceIncludesTax = $config->priceIncludesTax($store) || $config->getNeedUseShippingExcludeTax();
        }

        $percent = $product->getTaxPercent();
        $includingPercent = null;

        $taxClassId = $product->getTaxClassId();
        if (is_null($percent)) {
            if ($taxClassId) {
                $request = Mage::getSingleton('tax/calculation')
                    ->getRateRequest($shippingAddress, $billingAddress, $ctc, $store);
                $percent = Mage::getSingleton('tax/calculation')->getRate($request->setProductClassId($taxClassId));
            }
        }
        if ($taxClassId && $priceIncludesTax) {
            $taxHelper = Mage::helper('tax');
            if ($taxHelper->isCrossBorderTradeEnabled($store)) {
                $includingPercent = $percent;
            } else {
                $request = Mage::getSingleton('tax/calculation')->getDefaultRateRequest($store);
                $includingPercent = Mage::getSingleton('tax/calculation')
                    ->getRate($request->setProductClassId($taxClassId));
            }
        }

        if ($percent === false || is_null($percent)) {
            if ($priceIncludesTax && !$includingPercent) {
                return $price;
            }
        }
        $product->setTaxPercent($percent);

        if (!is_null($includingTax)) {
            if ($priceIncludesTax) {
                if ($includingTax) {
                    /**
                     * Recalculate price include tax in case of different rates
                     */
                    if ($includingPercent != $percent) {
                        $price = $this->_calculatePrice($price, $includingPercent, false);
                        /**
                         * Using regular rounding. Ex:
                         * price incl tax   = 52.76
                         * store tax rate   = 19.6%
                         * customer tax rate= 19%
                         *
                         * price excl tax = 52.76 / 1.196 = 44.11371237 ~ 44.11
                         * tax = 44.11371237 * 0.19 = 8.381605351 ~ 8.38
                         * price incl tax = 52.49531773 ~ 52.50 != 52.49
                         *
                         * that why we need round prices excluding tax before applying tax
                         * this calculation is used for showing prices on catalog pages
                         */
                        if ($percent != 0) {
                            $price = Mage::getSingleton('tax/calculation')->round($price);
                            $price = $this->_calculatePrice($price, $percent, true);
                        }
                    }
                } else {
                    $price = $this->_calculatePrice($price, $includingPercent, false);
                }
            } else {
                if ($includingTax) {
                    $price = $this->_calculatePrice($price, $percent, true);
                }
            }
        } else {
            if ($priceIncludesTax) {
                if ($includingTax) {
                    $price = $this->_calculatePrice($price, $includingPercent, false);
                    $price = $this->_calculatePrice($price, $percent, true);
                } else {
                    $price = $this->_calculatePrice($price, $includingPercent, false);
                }
            } else {
                if ($includingTax) {
                    $price = $this->_calculatePrice($price, $percent, true);
                }
            }
        }

        return $store->roundPrice($price);
    }

    /**
     * Calculate price imcluding/excluding tax base on tax rate percent
     *
     * @param float $price
     * @param float $percent
     * @param bool $includeTax true - for calculate price including tax and false if price excluding tax
     * @return float
     */
    protected function _calculatePrice($price, $percent, $includeTax)
    {
        /** @var $calculator Mage_Tax_Model_Calculation */
        $calculator = Mage::getSingleton('tax/calculation');
        $taxAmount = $calculator->calcTaxAmount($price, $percent, !$includeTax, false);

        return $includeTax ? $price + $taxAmount : $price - $taxAmount;
    }
}
