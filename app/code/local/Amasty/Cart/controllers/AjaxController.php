<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Cart
 */
class Amasty_Cart_AjaxController extends Mage_Core_Controller_Front_Action
{
    protected $isProductView = false;

    public function indexAction()
    {
        $params = Mage::app()->getRequest()->getParams();
        $this->isProductView = isset($params['IsProductView']) && $params['IsProductView'];
        $responseText = '';
        $idProduct = $this->getProductId($params);
        /* Compatibility with Amasty Product Matrix*/
        list($configurableQty, $attributeId) = $this->getConfigurableMatrixQty($params);

        unset($params['IsProductView']);

        $related = $this->getRequest()->getParam('related_product');
        if ($related) unset($params['related_product']);

        $product = Mage::getModel('catalog/product')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->load($idProduct);

        if ($product->getId()) {
            if (!array_key_exists('qty', $params)) {
                $params['qty'] = $product->getStockItem()->getMinSaleQty();
            }
            $cart = Mage::getSingleton('checkout/cart');

            try {
                if (($product->getTypeId() == 'simple' && !($product->getRequiredOptions()
                            || (Mage::getStoreConfig('amcart/general/display_options') && $product->getHasOptions()))
                    )
                    || count($params) > 2
                    || ($product->getTypeId() == 'virtual' && !($product->getRequiredOptions()
                            || (Mage::getStoreConfig('amcart/general/display_options') && $product->getHasOptions())))
                ) {
                    /* Compatibility with Amasty Product Matrix*/
                    if ($configurableQty) {
                        $filter = new Zend_Filter_LocalizedToNormalized(
                            array('locale' => Mage::app()->getLocale()->getLocaleCode())
                        );

                        /* empty qty validation*/
                        $summQty = 0;
                        foreach ($configurableQty as $option => $qty) {
                            $summQty += abs($qty);
                        }
                        if ($summQty == 0) {
                            Mage::throwException(
                                $this->__(' Please specify the quantity of product(s).')
                            );
                        }

                        foreach ($configurableQty as $option => $qty) {
                            $tmpParams = $params;
                            $tmpParams['super_attribute'][$attributeId] = $option;
                            $tmpParams['qty'] = $filter->filter($qty);

                            if ($tmpParams['qty'] > 0) {
                                $cart->addProduct((string)$product->getId(), $tmpParams);
                            }
                        }
                    } else {
                        $cart->addProduct($product, $params);
                    }

                    if (!empty($related)) {
                        $cart->addProductsByIds(explode(',', $related));
                    }
                    $cart->save();

                    Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
                    Mage::dispatchEvent(
                        'checkout_cart_add_product_complete',
                        array(
                            'product'  => $product,
                            'request'  => $this->getRequest(),
                            'response' => $this->getResponse()
                        )
                    );

                    if (!$cart->getQuote()->getHasError()) {
                        $responseText = $this->addToCartResponse($product, $cart, $params, 0);
                    } else {
                        $errors = $cart->getQuote()->getErrors();
                        foreach ($errors as $message) {
                            $responseText .= $message->getText();
                        }
                        $responseText = $this->addToCartResponse($product, $cart, $params, $responseText);
                    }
                } else {
                    $responseText = $this->showOptionsResponse($product);
                }

            } catch (Exception $e) {
                $responseText = $this->addToCartResponse($product, $cart, $params, $e->getMessage());
            }
        }
        $this->getResponse()->setBody($responseText);
    }

    /**
     * @param array $params
     * @return int
     */
    protected function getProductId(&$params)
    {
        $idProduct = Mage::app()->getRequest()->getParam('product');
        unset($params['product']);

        /* Compatibility  with old version */
        if (!isset($idProduct)) {
            $idProduct = Mage::app()->getRequest()->getParam('product_id');
            unset($params['product_id']);
        }

        return $idProduct;
    }

    /**
     * @param array $params
     * @return array|null
     */
    protected function getConfigurableMatrixQty(&$params)
    {
        $configurableQty = $attributeId = null;
        if (array_key_exists('configurable-option', $params)
            && count($params['configurable-option']) > 0
        ) {
            $configurableQty = $params['configurable-option'];
            $attributeId = array_shift(array_keys($configurableQty));
            $configurableQty = array_shift($configurableQty);
            unset($params['configurable-option']);
        }

        return array($configurableQty, $attributeId);
    }

    protected function showOptionsResponse($product)
    {
        $layout = Mage::app()->getLayout();
        Mage::register('current_product', $product);
        Mage::register('product', $product);

        $block = $layout->createBlock('catalog/product_view', 'catalog.product_view');
        $textScript = (Mage::getStoreConfig('amconf/list/enable_list')
            && 'true' == (string)Mage::getConfig()->getNode('modules/Amasty_Conf/active')
            && !$this->isProductView) ?
            ' optionsPrice[' . $product->getId() . '] = new Product.OptionsPrice(' . $block->getJsonConfig() . ');'
            : '';
        $html = '<script type="text/javascript">
                    optionsPrice = new Product.OptionsPrice(' . $block->getJsonConfig() . '); 
                    ' . $textScript . '
                    $("messageBox").addClassName("amcart-options"); 
                 </script><form id="product_addtocart_form" enctype="multipart/form-data">'
                .'<div class="amcart-title">'
                .'<a href="' . $product->getProductUrl() . '" title="'.$product->getName() .'">'
                . $product->getName()
                . '</a>'
                . '</div>';

        switch ($product->getTypeId()) {
            case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE :
                //if Colors Swatches Pro
                if ('true' == (string)Mage::getConfig()->getNode('modules/Amasty_Conf/active')
                    && Mage::getStoreConfig('amconf/list/enable_list')
                    && !$this->isProductView
                ) {
                    $html .= Mage::helper('amconf')->getHtmlBlock($product);
                } else {
                    $configurable =
                        $layout->createBlock('catalog/product_view_type_configurable', 'product_configurable_options')
                            ->setTemplate('catalog/product/view/type/options/configurable.phtml')
                            ->setProduct($product);
                    $blockRenderer = $layout->createBlock(
                        "core/text_list",
                        "product.info.options.configurable.renderers"
                    );
                    $configurable->setChild('attr_renderers', $blockRenderer);
                    $blockRendererAfter = $layout->createBlock(
                        "core/text_list",
                        "product.info.options.configurable.after"
                    );
                    $configurable->setChild('after', $blockRendererAfter);
                    $configurableData = $layout->createBlock(
                        'catalog/product_view_type_configurable',
                        'product_type_data'
                    )
                        ->setTemplate('catalog/product/view/type/configurable.phtml');
                    $configurableData->setProduct($product);
                    $html .= $configurable->toHtml() .
                        $configurableData->toHtml();
                }
                //fix for reloading price - myst be price container
                $html .= '<div id="product-price-' . $product->getId() . '" style="display: none;"></div>';
                break;
            case Mage_Catalog_Model_Product_Type::TYPE_GROUPED :
                $blockGr = $layout->createBlock(
                    'catalog/product_view_type_grouped',
                    'catalog.product_view_type_grouped'
                )->setTemplate('catalog/product/view/type/grouped.phtml');
                $html .= $blockGr->toHtml();
                break;
            case Mage_Catalog_Model_Product_Type::TYPE_BUNDLE :
                $blockBn = $layout->createBlock(
                    'bundle/catalog_product_view_type_bundle',
                    'product.info.bundle.options'
                );
                if ($blockBn) {
                    $blockBn->addRenderer('select', 'bundle/catalog_product_view_type_bundle_option_select');
                    $blockBn->addRenderer('multi', 'bundle/catalog_product_view_type_bundle_option_multi');
                    $blockBn->addRenderer(
                        'radio',
                        'bundle/catalog_product_view_type_bundle_option_radio',
                        'bundle/catalog/product/view/type/bundle/option/radio.phtml'
                    );
                    $blockBn->addRenderer(
                        'checkbox',
                        'bundle/catalog_product_view_type_bundle_option_checkbox',
                        'bundle/catalog/product/view/type/bundle/option/checkbox.phtml'
                    );
                    $blockBn->setTemplate('bundle/catalog/product/view/type/bundle/options.phtml');
                    $html .= $blockBn->toHtml();
                    $blockBn->setTemplate('bundle/catalog/product/view/type/bundle.phtml');
                    $html .= $blockBn->toHtml();
                }
                break;
            case 'downloadable' :
                $downloadable = $layout->createBlock(
                    'downloadable/catalog_product_links',
                    'product_downloadable_options'
                )->setTemplate('downloadable/catalog/product/links.phtml');
                $html .= $downloadable->toHtml();
                break;
            case 'amgiftcard' :
                if (class_exists('Amasty_GiftCard_Block_Catalog_Product_View_Type_GiftCard')) {
                    $amgiftcard = $layout->createBlock(
                        'amgiftcard/catalog_product_view_type_giftCard',
                        'product.info.amgiftcard'
                    )->setTemplate('amasty/amgiftcard/catalog/product/view/type/giftcard.phtml');
                    $html .= $amgiftcard->toHtml();
                }
                break;
        }
        $js = $layout->createBlock('core/template', 'product_js')
            ->setTemplate('catalog/product/view/options/js.phtml');
        $js->setProduct($product);
        $html .= $js->toHtml();
        $options = $layout->createBlock('catalog/product_view_options', 'product_options')
            ->setTemplate('catalog/product/view/options.phtml')
            ->addOptionRenderer(
                'text',
                'catalog/product_view_options_type_text',
                'catalog/product/view/options/type/text.phtml'
            )
            ->addOptionRenderer(
                'select',
                'catalog/product_view_options_type_select',
                'catalog/product/view/options/type/select.phtml'
            )
            ->addOptionRenderer(
                'file',
                'catalog/product_view_options_type_file',
                'catalog/product/view/options/type/file.phtml'
            )
            ->addOptionRenderer(
                'date',
                'catalog/product_view_options_type_date',
                'catalog/product/view/options/type/date.phtml'
            );

        $options->setProduct($product);
        $html .= $options->toHtml();
        if ($product->getTypeId() !== Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
            $price = $layout->createBlock('catalog/product_view', 'product_view')
                ->setTemplate('catalog/product/view/price_clone.phtml');
            if (Mage::helper('core')->isModuleEnabled('Amasty_GiftCard')) {
                $price->addPriceBlockType(
                    'amgiftcard',
                    'amgiftcard/catalog_product_price',
                    'amasty/amgiftcard/catalog/product/price.phtml'
                );
            }
            $html .= $price->toHtml();
        }

        //add input for checking count options
        $html .= '<input type="hidden" name="amasty_check_options" value="1"></form>';
        $result = array(
            'title' => $this->__('Set options'),
            'message' => $html,
            'b1_name' => $this->__('Cancel'),
            'b2_name' => $this->__('Add to cart'),
            'b1_action' => 'jQuery.confirm.hide();',
            'b2_action' => 'AmAjaxObj.sendAjax(' . $product->getId() . ', 1);',
            'align' => 'jQuery.confirm.hide();',
            'is_add_to_cart' => '0'
        );
        $result = $this->replaceJs($result);
        return Zend_Json::encode($result);
    }

    //reload my cart
    public function cartAction()
    {
        $_SERVER['REQUEST_URI'] = str_replace(Mage::getBaseUrl(), '/', $this->_getRefererUrl());
        $myCart = Mage::app()->getLayout()->createBlock('checkout/cart_sidebar', 'cart_sidebar')
            ->setTemplate('checkout/cart/sidebar.phtml');
        $this->getResponse()->setBody($myCart->toHtml());
    }

    //reload top cart
    public function mcartAction()
    {
        $_SERVER['REQUEST_URI'] = str_replace(Mage::getBaseUrl(), '/', $this->_getRefererUrl());
        $template = Mage::getStoreConfig('amcart/reloading/path');

        $myCart = Mage::app()->getLayout()->createBlock('checkout/cart_sidebar', 'cart_sidebar')
            ->setTemplate($template)
            ->addItemRender('grouped', 'checkout/cart_item_renderer_grouped', 'checkout/cart/sidebar/default.phtml')
            ->addItemRender(
                'configurable',
                'checkout/cart_item_renderer_configurable',
                'checkout/cart/sidebar/default.phtml'
            );
        $html = $myCart->toHtml();

        if ($html == "") {
            $this->loadLayout('default');
            $block = Mage::app()->getLayout('default')->getBlock('minicart_head');
            if (is_object($block))
                $html = $block->toHtml();
        }

        if ($html == "") {
            $myCart->setTemplate('checkout/cart/sidebar_header.phtml');
            $html = $myCart->toHtml();
        }
        if ($html == "") {
            $myCart->setTemplate('checkout/cart/mini.phtml');
            $html = $myCart->toHtml();
        }

        $this->getResponse()->setBody($html);

    }

    //reload shoppingcart
    public function checkoutAction()
    {
        $_SERVER['REQUEST_URI'] = str_replace(Mage::getBaseUrl(), '/', $this->_getRefererUrl());
        $this->loadLayout(array('checkout_cart_index'));
        $myCart = Mage::app()->getLayout('checkout_cart_index')->getBlock('checkout.cart');
        $this->getResponse()->setBody($myCart->toHtml());
    }

    //reload minicart
    public function minicartAction()
    {
        $_SERVER['REQUEST_URI'] = str_replace(Mage::getBaseUrl(), '/', $this->_getRefererUrl());
        //$myCart = Mage::app()->getLayout()->createBlock('checkout/cart_sidebar', 'cart_sidebar')
        //    ->setTemplate('amasty/amcart/checkout/cart/mini_cart.phtml');
        $myCart = Mage::app()->getLayout()->createBlock('checkout/cart_sidebar', 'ajax_cart_sidebar_mini')
            ->setTemplate('themevast/ajaxcart/checkout/cart/topcart.phtml');
        $this->getResponse()->setBody($myCart->toHtml());
    }

    //reload compare
    public function compareAction()
    {
        $_SERVER['REQUEST_URI'] = str_replace(Mage::getBaseUrl(), '/', $this->_getRefererUrl());
        $myCart = Mage::app()->getLayout()->createBlock(
            'catalog/product_compare_sidebar',
            'product_compare_sidebar'
        )
            ->setTemplate('catalog/product/compare/sidebar.phtml');
        $this->getResponse()->setBody($myCart->toHtml());
    }

//reload wishlist
    public function wishlistAction()
    {
        $_SERVER['REQUEST_URI'] = str_replace(Mage::getBaseUrl(), '/', $this->_getRefererUrl());
        $myCart = Mage::app()->getLayout()->createBlock(
            'wishlist/customer_sidebar',
            'wishlist_customer_sidebar'
        )
            ->setTemplate('wishlist/sidebar.phtml');
        $this->getResponse()->setBody($myCart->toHtml());
    }

    //reload count
    public function dataAction()
    {
        $block = Mage::app()->getLayout()->createBlock('amcart/config', 'amcart.config');
        if (Mage::getSingleton('checkout/cart')->getSummaryQty() == 1) {
            $html = $this->__('There is') . ' <a href="' . $block->getUrl('checkout/cart') . '" id="am-a-count" class="link">1'
                . $this->__(' item') . '</a> ' . $this->__('in your cart.');
        } else {
            $html = $this->__('There are') . ' <a href="' . $block->getUrl('checkout/cart') . '" id="am-a-count" class="link">'
                . Mage::getSingleton('checkout/cart')->getSummaryQty() . $this->__(' items')
                . '</a> ' . $this->__('in your cart.');
        }
        $priceHtml = $this->getAmcartHelper()->getSubtotalText();
        $result = array(
            'count' => $html,
            'price' => $priceHtml
        );
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    /**
     * creating finale popup
     *
     */
    protected function addToCartResponse(Mage_Catalog_Model_Product $product, $cart, $params, $text)
    {
        $summaryQty = Mage::getSingleton('checkout/cart')->getSummaryQty();
        $count = $this->getCountText($summaryQty);

        $result = array(
            'title' => $this->__('Information'),
            'b1_name' => $this->__('View Cart'),
            'b2_name' => $this->__('Continue Shopping'),
            'b3_name' => $this->__('Go to checkout'),
            'count' => $count,
            'b1_action' => $this->getButtonTwoAction(),
            'b2_action' => $this->getButtonOneAction($params),
            'b3_action' => $this->getButtonThirdAction(),
            'checkout_button' => $this->getCheckoutButton(),
            'is_add_to_cart' => '1',
            'timer' => $this->getTimerText()
        );

        if ($text) {
            $result['message'] = '<p class="amcart-text">' . $text . '</p>';
        } else {
            $this->registerCurrentProduct($product);
            $this->saveSimpleProductToSession($product, $params);

            $result['related'] = $this->getRelatedBlockHtml($product);
            $block = Mage::app()->getLayout()
                ->createBlock('amcart/dialog', 'amcart.dialog')
                ->setProduct($product);
            $result['message'] = $block->toHtml();
        }

        $result = $this->replaceJs($result);
        Mage::getSingleton('checkout/session')->setContinueShoppingUrl($_SERVER['HTTP_REFERER']);

        return Zend_Json::encode($result);

    }

    //replace js in one place    
    protected function replaceJs($result)
    {
        $arrScript = array();
        $result['script'] = '';
        preg_match_all('#(<!--\[if[^\n]*>\s*(<script.*</script>)+\s*<!\[endif\]-->)|(<script.*</script>)#isU', $result['message'], $arrScript);
        $result['message'] =
            preg_replace('#(<!--\[if[^\n]*>\s*(<script.*</script>)+\s*<!\[endif\]-->)|(<script.*</script>)#isU', '', $result['message']);
        foreach ($arrScript[0] as $script) {
            $result['script'] .= $script;
        }
        $result['script'] = preg_replace('/<script[^>]*>/i', '', $result['script']);
        $result['script'] = preg_replace('/<\/script>/i', '', $result['script']);
        $result['script'] = str_replace(
            'Product.Config({"attributes"',
            'Product.Config({"containerId":"confirmBox", "attributes"',
            $result['script']
        );

        return $result;
    }

    public function linkcompareAction()
    {
        $productId = (int)$this->getRequest()->getParam('product_id');
        $result = array(
            'title' => $this->__('Information'),
            'message' => $this->__('An error occurred while adding product to comparison list'),
            'b1_name' => $this->__('Continue'),
            'b2_name' => $this->__('Compare'),
            'b1_action' => 'jQuery.confirm.hide();',
            'b2_action' => 'popWin("' . Mage::getUrl('catalog/product_compare/index')
                . '","compare","top:0,left:0,width=820,height=600,resizable=yes,scrollbars=yes")',
            'align' => 'jQuery.confirm.hide();',
            'timer' => $this->getTimerText()
        );

        if ($productId && (Mage::getSingleton('log/visitor')->getId()
                || Mage::getSingleton('customer/session')->isLoggedIn())
        ) {
            $product = Mage::getModel('catalog/product')
                ->setStoreId(Mage::app()->getStore()->getId())
                ->load($productId);

            if ($product->getId()) {
                Mage::getSingleton('catalog/product_compare_list')->addProduct($product);

                Mage::dispatchEvent('catalog_product_compare_add_product', array('product' => $product));

                $result['message'] = $this->__(
                    'The product %s has been added to comparison list.',
                    Mage::helper('core')->escapeHtml($product->getName())
                );
            }

            Mage::helper('catalog/product_compare')->calculate();
        } else {
            if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
                $result['message'] = $this->__('Please login for adding product to comparison list.');
            }
            $url = Mage::getUrl('customer/account/login');
            $result['redirect'] = 'document.location = "' . $url . '";';
        }

        $this->getResponse()->setBody(
            Zend_Json::encode($result)
        );
    }

    /**
     * @return Amasty_Cart_Helper_Data
     */
    protected function getAmcartHelper()
    {
        return Mage::helper('amcart');
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @throws Mage_Core_Exception
     */
    protected function registerCurrentProduct(Mage_Catalog_Model_Product $product)
    {
        Mage::unregister('current_product');
        Mage::unregister('product');
        Mage::register('current_product', $product);
        Mage::register('product', $product);
    }

    /**
     * @param $summaryQty
     * @return string
     */
    protected function getCountText($summaryQty)
    {
        return ($summaryQty > 1) ?
            ' (' . $summaryQty . $this->__(' items)')
            : ' (' . $summaryQty . $this->__(' item)');
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param array $params
     * @throws Mage_Core_Exception
     */
    protected function saveSimpleProductToSession(Mage_Catalog_Model_Product $product, $params)
    {
        if ($this->getAmcartHelper()->displayProductImage()) {
            if ($product->isConfigurable()
                && (Mage::getStoreConfig('amcart/configurable/image')
                    || Mage::getStoreConfig('amcart/configurable/name'))
            ) {
                $simpleProduct = $product->getTypeInstance()->getProductByAttributes($params['super_attribute']);
                Mage::register('simpleProduct', $simpleProduct);
            }
        }
    }

    /**
     * @return string
     */
    protected function getTimerText()
    {
        $text = '';
        $time = $this->getAmcartHelper()->getTime();
        if (0 < $time) {
            $text = '<span class="timer"> (' . $time . ')</span>';
        }

        return $text;
    }

    /**
     * @param $params
     * @return string
     */
    protected function getButtonOneAction($params)
    {
        $action = 'jQuery.confirm.hide();';

        if ($this->isProductView && $this->getAmcartHelper()->getProductButton()
            && (Mage::registry('current_category') || $params['current_category'])
        ) {
            if (Mage::registry('current_category')) {
                $url = Mage::registry('current_category')->getUrl();
            } else {
                $url = $params['current_category'];
            }
            if ($url != "undefined") {
                $action = 'document.location = "' . $url . '";';
            }
        }

        return $action;
    }

    /**
     * @return string
     */
    protected function getButtonTwoAction()
    {
        $action = 'document.location = "' . Mage::helper('checkout/cart')->getCartUrl() . '";';

        return $action;
    }

    /**
     * @return string
     */
    protected function getButtonThirdAction()
    {
        $action = 'document.location = "' . Mage::helper('checkout/url')->getCheckoutUrl() . '";';

        return $action;
    }

    protected function getCheckoutButton()
    {
        $html = '';
        if ($this->getAmcartHelper()->displayGoToCheckout()) {
            $html = sprintf(
                '<a class="amcart-go-checkout" href="%s">%s</a>',
                Mage::helper('checkout/url')->getCheckoutUrl(),
                $this->__('Go to Checkout')
            );
        }

        return $html;
    }

    /**
     * @return string
     */
    protected function getRelatedBlockHtml($product)
    {
        $html = '';
        if ($this->getAmcartHelper()->isRelatedBlockEnabled()) {
            $relatedBlock = Mage::app()->getLayout()
                ->createBlock('amcart/catalog_product_list_related', 'product_list_related')
                ->setTemplate('amasty/amcart/catalog/product/list/related.phtml');

            $html = $relatedBlock->toHtml();
        }

        return $html;
    }
}
