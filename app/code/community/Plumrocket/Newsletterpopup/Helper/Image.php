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


class Plumrocket_Newsletterpopup_Helper_Image extends Mage_Core_Helper_Abstract
{
    public function getSquareImage($imgUrl, $width, $height)
    {
        $imgPath = $this->_splitImageValue($imgUrl, "path");
        $imgName = $this->_splitImageValue($imgUrl, "name");

        // Path with Directory Seperator
        $imgPath = str_replace("/",DS,$imgPath);

        // Absolute full path of Image
        $imgPathFull = Mage::getBaseDir("media").DS.$imgPath.DS.$imgName;

        // Resize folder is widthXheight
        $resizeFolder='cache'.DS.$width."x".$height;

        // Image resized path will then be
        $imageResizedPath = Mage::getBaseDir("media").DS.$imgPath.DS.$resizeFolder.DS.$imgName;

        /**
         * First check in cache i.e image resized path
         * If not in cache then create image of the width=X and height = Y
         */
        if (!file_exists($imageResizedPath))
        {
            if (file_exists($imgPathFull))
            {
                $imageObj = new Varien_Image($imgPathFull);
                $imageObj->constrainOnly(TRUE);
                $imageObj->keepAspectRatio(TRUE);
                $imageObj->keepFrame(FALSE);
                $imageObj->quality(100);

                $imageObj->resize($width, $height);
                $imageObj->save($imageResizedPath);

                unset($imageObj);

                if (!file_exists($imageResizedPath))
                {
                    return false;
                }
            }
            else
                return false;
        }

        // Return full http path of the image

        return Mage::getBaseUrl("media") . $imgPath."/".$resizeFolder."/".$imgName;
    }

    private function _splitImageValue($imageValue, $attr){
        $imArray = explode('/',$imageValue);

        $name = $imArray[count($imArray)-1];
        if($attr == 'path'){
            return implode('/',array_diff($imArray,array($name)));
        }
        else
            return $name;

    }

    public function resize($url, $width, $height = 0)
    {
        if ($height == 0) {
            $height = $width;
        }
        return $this->getSquareImage($url, $width, $height);
    }
}
