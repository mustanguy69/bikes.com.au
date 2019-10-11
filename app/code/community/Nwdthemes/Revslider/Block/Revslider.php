<?php

/**
 * Nwdthemes Revolution Slider Extension
 *
 * @package     Revslider
 * @author		Nwdthemes <mail@nwdthemes.com>
 * @link		http://nwdthemes.com/
 * @copyright   Copyright (c) 2014. Nwdthemes
 * @license     http://themeforest.net/licenses/terms/regular
 */

$libFolder = Mage::getBaseDir('lib') . '/Nwdthemes/Revslider';

//include framework files
require_once $libFolder . '/framework/include_framework.php';

//include bases
require_once $folderIncludes . 'base.class.php';
require_once $folderIncludes . 'elements_base.class.php';
require_once $folderIncludes . 'base_front.class.php';

//include product files
require_once $libFolder . '/revslider_settings_product.class.php';
require_once $libFolder . '/revslider_globals.class.php';
require_once $libFolder . '/revslider_operations.class.php';
require_once $libFolder . '/revslider_slider.class.php';
require_once $libFolder . '/revslider_output.class.php';
require_once $libFolder . '/revslider_slide.class.php';
require_once $libFolder . '/revslider_params.class.php';
require_once $libFolder . '/revslider_tinybox.class.php';

// include main classes
require_once $libFolder . '/revslider/revslider_front.php';

class Nwdthemes_Revslider_Block_Revslider extends Mage_Core_Block_Template {

	protected $_revSliderFront;
	protected $_slider;
	protected $_content;

	protected function _construct()
	{
        parent::_construct();
		$this->setTemplate('nwdthemes/revslider/revslider.phtml');
		$this->_revSliderFront = Mage::getSingleton('RevSliderFront');
	}

	protected function _renderSlider()
	{
		if ( is_null($this->_slider) ) {
			ob_start();
			$this->_slider = RevSliderOutput::putSlider($this->getData('alias'));
			$this->_content = ob_get_contents();
			ob_clean();
			ob_end_clean();
		}
	}

	public function getCacheKeyInfo()
	{
		$this->_renderSlider();
		$key = parent::getCacheKeyInfo();
		$key[] = $this->getData('alias');
		$key[] = $this->_slider->getParam("disable_on_mobile", "off");

		return $key;
	}

	public function renderSlider()
	{
		if ( Mage::helper('nwdall')->getCfg('general/enabled', 'nwdrevslider_config') ) {
			$this->_renderSlider();
			$disable_on_mobile = $this->_slider->getParam("disable_on_mobile", "off");
			if($disable_on_mobile == 'on')
			{
				$mobile = (
						strstr($_SERVER['HTTP_USER_AGENT'],'Android')
						|| strstr($_SERVER['HTTP_USER_AGENT'],'webOS')
						|| strstr($_SERVER['HTTP_USER_AGENT'],'iPhone')
						||strstr($_SERVER['HTTP_USER_AGENT'],'iPod')
						|| strstr($_SERVER['HTTP_USER_AGENT'],'iPad')
					) ? true : false;
				if ($mobile)
				{
					$this->_content = '';
				}
			}
		} else {
			$this->_content = '';
		}

		return $this->_content;
	}

}
