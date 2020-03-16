<?php


class BikeExchange_WS_Block_Adminhtml_Taxons_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        // Instantiate a new form to display our brand for editing.
        $form = new Varien_Data_Form(array(
            'id' => 'edit_form',
            'action' => $this->getUrl(
                'bikeexchange/adminhtml_taxons/edit',
                array(
                    '_current' => true,
                    'continue' => 0,
                )
            ),
            'method' => 'post',
        ));

        $form->setUseContainer(true);
        $this->setForm($form);

        // Define a new fieldset. We need only one for our simple entity.
        $fieldset = $form->addFieldset(
            'general',
            array(
                'legend' => $this->__('Taxons Details')
            )
        );

        $helper = Mage::helper('bikeexchange_ws');
        $taxonBikeExchangeRequest = $helper->bikeExchangeApiCall('taxons?page[size=1000]', 'GET');
        $taxonBikeExchangeDecoded = json_decode($taxonBikeExchangeRequest, true);
        foreach ($taxonBikeExchangeDecoded['data'] as $taxonsBikeExchange) {
            $values[] = [
                'value' => $taxonsBikeExchange['attributes']['slug'],
                'label' => $taxonsBikeExchange['attributes']['name']
            ];
        }


        $taxon = Mage::registry('current_taxon');

        $this->_addFieldsToFieldset($fieldset, array(
            'category_id' => array(
                'label' => $this->__('Category Name'),
                'input' => 'select',
                'required' => true,
                'values' => $this->getCategoriesTreeView(),
                'value' => $taxon->getCategoryId(),
                'onchange' => "selectCategory()",
            ),
            'category_name' => array(
                'input' => 'hidden',
                'required' => true,
                'value' => $taxon->getCategoryName()
            ),
            'taxon_slug' => array(
                'label' => $this->__('Taxon Name'),
                'input' => 'select',
                'required' => true,
                'values' => $values,
                'value' => $taxon->getTaxonSlug(),
                'onchange' => "selectSlug();"
            ),
            'taxon_name' => array(
                'input' => 'hidden',
                'required' => true,
                'value' => $taxon->getTaxonName(),
            ),
        ));

        return $this;
    }

    protected function _addFieldsToFieldset(Varien_Data_Form_Element_Fieldset $fieldset, $fields)
    {
        $requestData = new Varien_Object($this->getRequest()
            ->getPost());

        foreach ($fields as $name => $_data) {
            if ($requestValue = $requestData->getData($name)) {
                $_data['value'] = $requestValue;
            }


            // Wrap all fields with brandData group.
            $_data['name'] = "taxonData[$name]";

            // Generally, label and title are always the same.
            $_data['title'] = $_data['label'];

            // Finally, call vanilla functionality to add field.
            $field = $fieldset->addField($name, $_data['input'], $_data);
            $field->setAfterElementHtml('<script>
                function selectCategory() {
                    var categoryId = document.getElementById("category_id");
                    var category = categoryId.options[categoryId.selectedIndex].innerHTML;
                    var categoryName = document.getElementById("category_name");
                    categoryName.value = category;
                }

                function selectSlug() {
                    var taxonSlug = document.getElementById("taxon_slug");
                    var taxon = taxonSlug.options[taxonSlug.selectedIndex].innerHTML;
                    var taxonName = document.getElementById("taxon_name");
                    taxonName.value = taxon;
                }
            </script>');

        }

        return $this;
    }

    function getCategoriesTreeView() {
        $categories = Mage::getModel('catalog/category')
            ->getCollection()
            ->addAttributeToSelect('name')
            ->addAttributeToSort('path', 'asc')
            ->addFieldToFilter('is_active', array('eq'=>'1'))
            ->load()
            ->toArray();

        // Arrange categories in required array
        $categoryList = array();
        foreach ($categories as $catId => $category) {
            if (isset($category['name'])) {
                $categoryList[] = array(
                    'label' => $category['name'],
                    'value' => $catId
                );
            }
        }
        return $categoryList;
    }

}