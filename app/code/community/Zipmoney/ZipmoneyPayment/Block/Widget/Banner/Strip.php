<?php
/**
 * @category  Aligent
 * @package   zipmoney
 * @author    Andi Han <andi@aligent.com.au>
 * @copyright 2014 Aligent Consulting.
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.aligent.com.au/
 */

class Zipmoney_ZipmoneyPayment_Block_Widget_Banner_Strip extends Zipmoney_ZipmoneyPayment_Block_Abstract
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('zipmoney/zipmoneypayment/widget/banner/strip.phtml');
    }

    public function getHtml()
    {
        $vBannerType = Zipmoney_ZipmoneyPayment_Model_Config::MARKETING_BANNERS_TYPE_STRIP;
        $vHtml = Mage::helper("zipmoneypayment/widget")->getBannerHtml($vBannerType, null);
        return $vHtml;
    }
}