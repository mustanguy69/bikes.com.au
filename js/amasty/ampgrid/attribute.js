/**
* @author Amasty Team
* @copyright Copyright (c) 2010-2011 Amasty (http://www.amasty.com)
* @package Amasty_Pgrid
*/

var amPattribute = new Class.create();

amPattribute.prototype = {

    initialize: function(title)
    {
        this.title = title;
    },
    
    showConfig: function()
    {
        attributeDialog = Dialog.info($('pAttribute_block').innerHTML, {
            draggable: true,
            closable: true,
            className: "magento",
            windowClassName: "popup-window",
            title: this.title,
            width: 700,
            height: 600,
            zIndex: 1000,
            recenterAuto: false,
            hideEffect: Element.hide,
            showEffect: Element.show,
            destroyOnClose: false,
            showProgress: true,
            child: 'saveNewTemplate',
            id: 'attributeDialog',
            onShow: function()
            {
                pAttributeForm = new VarienForm('form-pattribute');
            },
            onFocus: function()
            {
                if ($(this.child)) {
                    saveNewTemplatePopup.close();
                }
            }
        });
    },

    unCheckAll: function()
    {
        $$(".pattribute:checked, .category:checked").each(function(obj){
            obj.checked = false;
        });
    },

    changeGroup: function(element)
    {
        $('change-template').value = 1;
        $('save-template').click();
    },

    saveNewTemplate: function()
    {
        saveNewTemplatePopup = Dialog.info(
            $('new-template-popup').innerHTML, {
                draggable: true,
                closable: true,
                className: "magento",
                windowClassName: "popup-window",
                title: this.title,
                width: 340,
                height: 70,
                parent: $('attributeDialog'),
                zIndex: 1100,
                top: 490,
                recenterAuto: false,
                hideEffect: Element.hide,
                showEffect: Element.show,
                id: 'saveNewTemplate',
                onShow: function()
                {
                    Validation.add('validate-new-template','This is a required field.',function(){
                        if (!$('template-name').value && $('is-new-template').value == 1) {
                            alert('New Template Name is a required field.');
                            return false;
                        }
                        return true;
                    });
                    this.parent.show();
                    $('new-template-name').value = $('template-name').value;
                    $('is-new-template').value = 1;
                },
                onClose: function()
                {
                    $('is-new-template').value = 0;
                }
            });
    },

    closeConfig: function()
    {
        attributeDialog.close();
    }
};