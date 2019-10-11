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
 * @copyright   Copyright (c) 2018 Plumrocket Inc. (http://www.plumrocket.com)
 * @license     http://wiki.plumrocket.net/wiki/EULA  End-user License Agreement
 */


class Plumrocket_Newsletterpopup_Block_Popup_Fields_Agreement extends Plumrocket_Newsletterpopup_Block_Popup_Fields_Field
{
    /**
     * Cms page identifiers that will show in modal window
     *
     * @var array
     */
    protected $_pageIdentifiers = array();

    /**
     * Retrieve lable of checkbox
     *
     * @return string
     */
    public function getLabel()
    {
        $html = parent::getLabel();
        $html = $this->prepareLinks($html);

        return $html;
    }

    /**
     * Find all links and collect cms page identifiers
     *
     * @param  string $html
     * @return string
     */
    public function prepareLinks($html)
    {
        preg_match_all(
            '#<a.+?href=["\']\#([^"\']+?)["\'].*?>.+?</a>#uis',
            $html,
            $matches
        );

        foreach ($matches[1] as $identifier) {
            $this->_pageIdentifiers[] = $identifier;
        }

        return $html;
    }

    /**
     * Retrieve html element id
     *
     * @param  string|int $identifier
     * @return string
     */
    public function getAgreementContentId($identifier)
    {
        return 'nl_agreement_' . $this->getPopup()->getId() . '_page_' . $identifier;
    }

    /**
     * Retrieve list of all identifiers
     *
     * @return array
     */
    public function getPageIdentifiers()
    {
        return array_unique($this->_pageIdentifiers);
    }

    /**
     * Retrieve cms page content by identifier
     *
     * @param  string|int $identifier
     * @return string
     */
    public function getPageContent($identifier)
    {
        return Mage::getModel('cms/page')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->load($identifier)
            ->getContent();
    }
}
