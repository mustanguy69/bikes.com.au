<?php
/**
 * Plumrocket Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End-user License Agreement
 * that is available through the world-wide-web at this URL:
 * http://wiki.plumrocket.net/wiki/EULA
 * If you are unable to obtain it through the world-wide-web, please
 * send an email to support@plumrocket.com so we can send you a copy immediately.
 *
 * @package     Plumrocket_Newsletterpopup
 * @copyright   Copyright (c) 2017 Plumrocket Inc. (http://www.plumrocket.com)
 * @license     http://wiki.plumrocket.net/wiki/EULA  End-user License Agreement
 */


class Plumrocket_Newsletterpopup_Model_Values_Animation extends Plumrocket_Newsletterpopup_Model_Values_Base
{
    public function toOptionHash()
    {
        return array(
        	''               => Mage::helper('newsletterpopup')->__('None'),
            'fadeIn'         => Mage::helper('newsletterpopup')->__('Fade In'),
            'fadeInDown'     => Mage::helper('newsletterpopup')->__('Fade In (Down)'),
            'fadeInLeft'     => Mage::helper('newsletterpopup')->__('Fade In (Left)'),
            'fadeInRight'    => Mage::helper('newsletterpopup')->__('Fade In (Right)'),
            'fadeInUp'       => Mage::helper('newsletterpopup')->__('Fade In (Up)'),
            'fadeInDownBig'  => Mage::helper('newsletterpopup')->__('Slide In (Down) - Default'),
            'fadeInLeftBig'  => Mage::helper('newsletterpopup')->__('Slide In (Left)'),
            'fadeInRightBig' => Mage::helper('newsletterpopup')->__('Slide In (Right)'),
            'fadeInUpBig'    => Mage::helper('newsletterpopup')->__('Slide In (Up)'),
            'zoomIn'         => Mage::helper('newsletterpopup')->__('Zoom In'),
            'flip3d_hor'     => Mage::helper('newsletterpopup')->__('3D Flip (horizontal)'),
        );
    }
}