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


class Plumrocket_Newsletterpopup_Block_Adminhtml_Templates_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {   
        $this->_blockGroup = 'newsletterpopup';
        $this->_controller = 'adminhtml_templates';
        $this->_mode = 'edit';
        parent::__construct();

        $template = Mage::registry('template');
        
        if($template->canDelete()) {
            $this->_updateButton('delete', 'onclick', 'deleteConfirm(\''. Mage::helper('newsletterpopup')->__('By deleting popup you will also delete history. Are you sure?')
                        .'\', \'' . $this->getDeleteUrl() . '\')');
        }else{
            $this->_removeButton('delete');
        }
        
        if(!$template->isBase()) {
            $this->_updateButton('save', 'label', Mage::helper('newsletterpopup')->__('Save Theme'));
            $this->_addButton('saveandcontinue', array(
                'label'     => Mage::helper('newsletterpopup')->__('Save and Continue Edit'),
                'onclick'   => 'saveAndContinueEdit(\''.$this->_getSaveAndContinueUrl().'\')',
                'class'     => 'save',
            ), -100);
        }else{
            Mage::getSingleton('adminhtml/session')->addNotice(Mage::helper('newsletterpopup')->__('This theme is one of the default Newsletter popup themes. It cannot be edited or deleted. Instead, you can duplicate it and then edit.'));
            $this->_removeButton('save');
        }

        if (Mage::registry('template')->getId()) {
            $this->_addButton('duplicate', array(
                'label'     => Mage::helper('newsletterpopup')->__('Duplicate'),
                'onclick'   => "setLocation('" . $this->_getDuplicateUrl() . "')",
                'class'     => 'duplicate',
            ), 1, 5);

            $this->_addButton('preview', array(
                'label'     => Mage::helper('newsletterpopup')->__('Preview'),
                'onclick'   => 'previewTemplate()',
                'class'     => 'preview',
            ), 1, 6);
        }
    }

    public function getSaveUrl()
    {
        return $this->getUrl('*/*/save');
    }

    protected function _getDuplicateUrl()
    {
        $template = Mage::registry('template');
        $id = ($template)? $template->getId(): 0;

        return $this->getUrl('*/*/duplicate', array(
            'id' => $id
        ));
    }
    
    protected function _getSaveAndContinueUrl()
    {
        return $this->getUrl('*/*/save', array(
            '_current'  => true,
            'back'      => 'edit',
            'active_tab'       => '{{tab_id}}'
        ));
    }

    /**
     * Retrieve text for header element depending on loaded page
     *
     * @return string
     */
    public function getHeaderText()
    { 
        if (Mage::registry('template')->getId()) {
            return Mage::helper('newsletterpopup')->__("Edit Theme \"%s\"",
                $this->htmlEscape(ucfirst(Mage::registry('template')->getName()))
            );
        } else {
            return Mage::helper('newsletterpopup')->__("New Theme");
        }
    }

    /**
     * @see Mage_Adminhtml_Block_Widget_Container::_prepareLayout()
     */
    protected function _prepareLayout()
    {
        $tabsBlock = $this->getLayout()->getBlock('newsletterpopup_edit_tabs');
        if ($tabsBlock) {
            $tabsBlockJsObject = $tabsBlock->getJsObjectName();
            $tabsBlockPrefix = $tabsBlock->getId() . '_';
        } else {
            $tabsBlockJsObject = 'edit_tabsJsTabs';
            $tabsBlockPrefix = 'edit_tabs_';
        }

        $helper = Mage::helper('newsletterpopup');
        $previewUrl = Mage::helper('newsletterpopup/adminhtml')->getFrontendUrl('newsletterpopup/index/preview/is_template/1', array(), true);
        $this->_formScripts[] = "
            previewUrl = '". $helper->validateUrl($previewUrl) ."';

            function saveAndContinueEdit(urlTemplate) {
                var tabsIdValue = " . $tabsBlockJsObject . ".activeTab.id;
                var tabsBlockPrefix = '" . $tabsBlockPrefix . "';
                if (tabsIdValue.startsWith(tabsBlockPrefix)) {
                    tabsIdValue = tabsIdValue.substr(tabsBlockPrefix.length)
                }
                var template = new Template(urlTemplate, /(^|.|\\r|\\n)({{(\w+)}})/);
                var url = template.evaluate({tab_id:tabsIdValue});
                editForm.submit(url);
            }
            

            CodeMirror.commands.autocomplete = function(cm) {
                cm.showHint({hint: CodeMirror.hint.anyword});
            }

            CodeMirror.hint.anyword = function(cm) {
              var inner = {from: cm.getCursor(), to: cm.getCursor(), list: []};
              inner.list = ". json_encode($helper->getTemplatePlaceholders(true)) ."
              return inner;
            };
            
            CodeMirror.defineMode('mustache', function(config, parserConfig) {
                var mustacheOverlay = {
                    token: function(stream, state) {
                        var ch;
                        if (stream.match('{{')) {
                            while ((ch = stream.next()) != null)
                                if (ch == '}' && stream.next() == '}') {
                                    stream.eat('}');
                                    return 'mustache';
                                }
                        }
                        while (stream.next() != null && !stream.match('{{', false)) {}
                        return null;
                    }
                };
                return CodeMirror.overlayMode(CodeMirror.getMode(config, parserConfig.backdrop || 'text/html'), mustacheOverlay);
            });

            var codeEditor = CodeMirror.fromTextArea(document.getElementById('template_code'), {
                //mode: 'text/html',
                mode: 'mustache',
                theme: 'monokai',
                autoCloseBrackets: true,
                autoCloseTags: true,

                styleActiveLine: true,
                lineNumbers: true,
                lineWrapping: true,
                viewportMargin: Infinity,

                //matchTags: { bothTags: true },
                extraKeys: {
                    'Ctrl-Space': 'autocomplete',
                    'F1': 'autocomplete',
                    'Ctrl-J': 'toMatchingTag',
                    'F11': function(cm) {
                        cm.setOption('fullScreen', !cm.getOption('fullScreen'));
                    },
                    'Esc': function(cm) {
                        if (cm.getOption('fullScreen')) cm.setOption('fullScreen', false);
                    }
                },

                //value: document.documentElement.innerHTML
            });

            var styleEditor = CodeMirror.fromTextArea(document.getElementById('template_style'), {
                mode: 'text/css',
                theme: 'monokai',
                autoCloseBrackets: true,

                styleActiveLine: true,
                lineNumbers: true,
                lineWrapping: true,
                viewportMargin: Infinity,

                //matchTags: { bothTags: true },
                extraKeys: {
                    'Ctrl-Space': 'autocomplete',
                    'F1': 'autocomplete',
                    'Ctrl-J': 'toMatchingTag',
                    'F11': function(cm) {
                        cm.setOption('fullScreen', !cm.getOption('fullScreen'));
                    },
                    'Esc': function(cm) {
                        if (cm.getOption('fullScreen')) cm.setOption('fullScreen', false);
                    }
                },

                //value: document.documentElement.innerHTML
            })

            pjQuery_1_12_4(document).ready(function() {
                codeEditor.on('change', function() { pjQuery_1_12_4('#template_code').val(codeEditor.getValue()); });
                styleEditor.on('change', function() { pjQuery_1_12_4('#template_style').val(styleEditor.getValue()); });
            });

        ";
        return parent::_prepareLayout();
    }


}
