<?php
/**
 * @category  Aligent
 * @package   zipmoney
 * @author    Andi Han <andi@aligent.com.au>
 * @copyright 2014 Aligent Consulting.
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.aligent.com.au/
 */

class Zipmoney_ZipmoneyPayment_Block_Widget_Top extends Zipmoney_ZipmoneyPayment_Block_Widget_Abstract
{
    protected $_vBannerType = Zipmoney_ZipmoneyPayment_Model_Config::MARKETING_BANNERS_TYPE_STRIP;
    protected $_vPosition = Zipmoney_ZipmoneyPayment_Model_Config::MARKETING_BANNERS_POSITION_TOP;

    public function __construct()
    {
        parent::__construct();
    }

    protected function _prepareLayout()
    {
        if ($this->_isShow($this->_vBannerType, $this->_vPosition)) {
            $this->_setBannerBlock($this->_vBannerType);
        }
        return parent::_prepareLayout();
    }

    public function checkVersionResponsive()
    {
        $bVerResponsive = false;
        $vEdition = Mage::getEdition();
        switch ($vEdition) {
            case 'Community':
                $bVerResponsive = version_compare(Mage::getVersion(), '1.9', '>=');
                break;
            case 'Enterprise':
                $bVerResponsive = version_compare(Mage::getVersion(), '1.14', '>=');
                break;
            default:
                break;
        }
        return $bVerResponsive ? 'true' : 'false';
    }

    public function isShow()
    {
        return $this->_isShow($this->_vBannerType, $this->_vPosition);
    }
}