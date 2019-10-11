<?php
/**
 * Magmodules.eu - http://www.magmodules.eu.
 *
 * NOTICE OF LICENSE
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.magmodules.eu/MM-LICENSE.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@magmodules.eu so we can send you a copy immediately.
 *
 * @category      Magmodules
 * @package       Magmodules_Googleshopping
 * @author        Magmodules <info@magmodules.eu>
 * @copyright     Copyright (c) 2017 (http://www.magmodules.eu)
 * @license       https://www.magmodules.eu/terms.html  Single Service License
 */

class Magmodules_Googleshopping_Block_Adminhtml_Widget_Info_Info
    extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface
{

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $magentoVersion = Mage::getVersion();
        $moduleVersion = Mage::getConfig()->getNode()->modules->Magmodules_Googleshopping->version;
        $logoLink = '//www.magmodules.eu/logo/google-shopping/' . $moduleVersion . '/' . $magentoVersion . '/logo.png';
        $html = '<div style="background:url(\'' . $logoLink . '\') no-repeat scroll 15px center #EAF0EE; 
          border:1px solid #CCCCCC;margin-bottom:10px;padding:10px 5px 5px 200px;">
          <h4>About Magmodules.eu</h4>
            <p>We are a Magento only E-commerce Agency located in the Netherlands.<br>
            <br />
            <table width="500px" border="0">
              <tr>
                <td width="58%">More Extensions from Magmodules:</td>
                <td width="42%">
                  <a href="http://www.magentocommerce.com/magento-connect/developer/Magmodules" target="_blank">
                    Magento Connect
                  </a>
                </td>
              </tr>
              <tr>
                <td>For Help:</td>
                <td><a href="https://www.magmodules.eu/support.html?ext=google-shopping">Visit our Support Page</a></td>
              </tr>
              <tr>
                <td height="30">Visit Our Website:</td>
                <td><a href="http://www.magmodules.eu" target="_blank">www.Magmodules.eu</a></td>
              </tr>
              <tr>
                <td height="30">
                  <strong>Read everything about the extension configuration in our 
                    <a href="https://www.magmodules.eu/help/google-shopping" target="_blank">Knowledgebase</a>
                  </strong>.
                </td>
                <td>&nbsp;</td>
              </tr>
            </table>
         </div>';

        if (Mage::helper('googleshopping')->checkOldVersion('Googleshopping')) {
            $msg = '<div id="messages">
             <ul class="messages">
              <li class="error-msg">
               <ul>
                <li>
                <span>
                 ' . Mage::helper('googleshopping')->__('Old version detected on the server, please remove the directory <u>app/code/local/Magmodules/Beslist</u> and flush cache!') . '
                 </span>
                </li>
               </ul>
              </li>
             </ul>
            </div>';
            $html = $msg . $html;
            $oldversion = 1;
        }

        $flatProduct = Mage::getStoreConfig('catalog/frontend/flat_catalog_product');
        $flatCategory = Mage::getStoreConfig('catalog/frontend/flat_catalog_category');
        if ((!$flatProduct) || (!$flatCategory)) {
            $msg = '<div id="messages">
             <ul class="messages">
              <li class="error-msg">
               <ul>
                <li>
                 <span>
                  ' . Mage::helper('googleshopping')->__('Please enable "Flat Catalog Category" and "Flat Catalog Product" for the extension to work properly. <a href="https://www.magmodules.eu/help/enable-flat-catalog/" target="_blank">More information.</a>') . '
                 </span>
                </li>
               </ul>
              </li>
             </ul>
             </div>';
            $html = $html . $msg;
        }

        if (empty($oldversion)) {
            if (Mage::getStoreConfig('catalog/frontend/flat_catalog_product')) {
                $storeId = Mage::helper('googleshopping')->getStoreIdConfig();
                $nonFlatAttributes = Mage::helper('googleshopping')->checkFlatCatalog(Mage::getModel("googleshopping/googleshopping")->getFeedAttributes('', $storeId, 'flatcheck'));
                if (count($nonFlatAttributes) > 0) {
                    $html .= '<div id="messages"><ul class="messages"><li class="error-msg"><ul><li><span>';
                    $html .= $this->__('Warning: The following used attribute(s) were not found in the flat catalog: %s. This can result in empty data or higher resource usage. Click <a href="%s">here</a> to add these to the flat catalog. ', implode($nonFlatAttributes, ', '), $this->getUrl('*/googleshopping/addToFlat'));
                    $html .= '</span></ul></li></ul></div>';
                }
            }
        }

        return $html;
    }

}