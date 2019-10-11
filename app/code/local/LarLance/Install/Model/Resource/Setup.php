<?php

class LarLance_Install_Model_Resource_Setup extends Mage_Core_Model_Resource_Setup
{
    protected $_blockData;
    protected $_pageData;

    /**
     * @param $data
     * @param bool $update
     */
    public function addCmsBlock($data, $update = false)
    {
        $this->_blockData = $data;
        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
        if ($update) {
            $cmsBlock = Mage::getModel('cms/block')->load($this->_blockData['identifier']);

            if (!is_object($cmsBlock)) {
                throw new Exception("Cms block can't be updated. Incorrect identifier.");
            } else {
                $this->_validateCmsBlockData();
                foreach ($data as $key => $value) {
//                    if ('identifier' == $key) {
//                        continue;
//                    }
                    $cmsBlock->setData($key, $value);
                }
                $cmsBlock->save();
            }

        } else {
            $this->_validateCmsBlockData();
            Mage::getModel('cms/block')->setData($this->_blockData)->save();
        }

        return;
    }

    protected function _validateCmsBlockData()
    {
        $requiredData = array('title', 'identifier', 'stores', 'is_active', 'content');
        foreach ($requiredData as $requiredKey) {
            if (!isset($this->_blockData[$requiredKey])) {
                throw new Exception("Field " . $requiredKey . " is required");
            }

            if ($requiredKey == 'identifier') {
                if (!preg_match('/^[a-zA-Z]+[a-zA-Z0-9_-]+$/', $this->_blockData[$requiredKey])) {
                    throw new Exception("Please enter a valid XML-identifier. For example something_1, block5, id-4.");
                }
            }
            if ($requiredKey == 'stores') {
                if (is_string($this->_blockData[$requiredKey])) {
                    $this->_blockData[$requiredKey] = array_filter(explode(',', $this->_blockData[$requiredKey]));
                }
            }
        }
    }

    public function addCmsPage($data, $update = false)
    {
        $this->_pageData = $data;
        if ($update) {
            $cmsPage = Mage::getModel('cms/page')->load($this->_pageData['identifier']);
            if (!is_object($cmsPage)) {
                throw new Exception("Cms page can't be updated. Incorrect identifier.");
            } else {
                $this->_validateCmsPageData();
                foreach ($data as $key => $value) {
                    if ('identifier' == $key) {
                        continue;
                    }
                    $cmsPage->setData($key, $value);
                }
                $cmsPage->save();
            }
        } else {
            $this->_validateCmsPageData();
            Mage::getModel('cms/page')->setData($this->_pageData)->save();
        }

        return;
    }

    protected function _validateCmsPageData()
    {
        $requiredData = array('title', 'identifier', 'stores', 'is_active', 'content', 'root_template');
        foreach ($requiredData as $requiredKey) {
            if (!isset($this->_pageData[$requiredKey])) {
                throw new Exception("Field " . $requiredKey . " is required");
            }

            if ($requiredKey == 'identifier') {
                if (!preg_match('/^[a-zA-Z]+[a-zA-Z0-9_-]+$/', $this->_pageData[$requiredKey])) {
                    throw new Exception("Please enter a valid XML-identifier. For example something_1, block5, id-4.");
                }
            }
            if ($requiredKey == 'stores') {
                if (is_string($this->_pageData[$requiredKey])) {
                    $this->_pageData[$requiredKey] = array_filter(explode(',', $this->_pageData[$requiredKey]));
                }
            }
        }
    }
}