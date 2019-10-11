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


;(function($) {
    $(document).ready(function(){
        $('#popup_coupon_code').change(function() {
            var id = $('#popup_coupon_code').val();

            if ($('#popup_start_date').val() || $('#popup_end_date').val()) {} else {
                if (id in coupons_date) {
                    var dates = coupons_date[id];

                    $('#popup_start_date').val(dates.from_date);
                    $('#popup_end_date').val(dates.to_date);
                }
            }

            if (parseInt(id) > 0) {
                $('#popup_code_container').hide();
                $('#popup_coupon_fieldset').find('input, select').removeAttr('disabled');
            } else {
                $('#popup_code_container').show();
                $('#popup_coupon_fieldset').find('input, select').attr('disabled', 'disabled');
            }

            var errorText = 'This option is only available for shopping cart rules with auto generated coupons';
            var className = 'extended-time-message';
            var cetContainer = $('#popup_coupon_expiration_time_day').parent('td');

            cetContainer.find('.' + className).remove();
            cetContainer.find('select').removeAttr('disabled');

            if (! window.use_auto_generation[id]) {
                cetContainer.find('select').attr('disabled', 'disabled');
            }

            if (! window.use_auto_generation[id] && (id != 0)) {
                var messageHtml = '<span class="' + className + '">'
                    + errorText
                    + '</span>';

                cetContainer.prepend(messageHtml);
            }
        });

        $('#popup_coupon_code').trigger('change');

        $('#popup_success_page').change(checkSuccessPage);
        $('#popup_display_popup').change(checkPopupMethod);
        $('#popup_send_email').change(checkSendEmail);

        var _checkEnable = function() {
            var $chk = $(this);
            if (! $chk.is(':checked')) {
                $chk.parent().parent().addClass('not-active');
            } else {
                $chk.parent().parent().removeClass('not-active');
            }
        }

        $('.form-list .grid table.data tbody input.checkbox').click(_checkEnable).each(_checkEnable);

        varienGlobalEvents.attachEventHandler('formSubmit', function() {
            if(typeof codeEditor != 'undefined') {
                cmSyncChangesByEditor('#template_code', codeEditor);
            }
            if(typeof styleEditor != 'undefined') {
                cmSyncChangesByEditor('#template_style', styleEditor);
            }

            prepareCodeAndStyle();
        });

        varienGlobalEvents.attachEventHandler('showTab', function() {
            if(typeof codeEditor != 'undefined') {
                codeEditor.refresh();
            }
            if(typeof styleEditor != 'undefined') {
                styleEditor.refresh();
            }
        });

        varienGlobalEvents.attachEventHandler('tinymceChange', function() {
            cmSyncChangesByTextarea('#template_code', codeEditor);
            cmSyncChangesByTextarea('#template_style', styleEditor);
        });

        $('#choose_template,#template_id_picker .template-current').on('click', function(e) {
            $('#template_id_picker .template-list').toggle();
            e.stopPropagation();
        });

        $('#template_id_picker').on('click', 'li .list-table-td,li button.select_template', function() {
            var $el = $(this).parents('li');
            $('#template_id_picker li').removeClass('active');
            $el.addClass('active');
            $('#popup_template_id').val($el.data('id'));
            $('#template_id_picker .template-current').html($el.html());

            $('#loading-mask').show();
            $.get($('#template_id_picker').data('action'), {'id': $el.data('id')}, function(data) {
                if(data.code || data.style) {
                    codeEditor.setValue(data.code);
                    cmSyncChangesByEditor('#template_code', codeEditor);
                    styleEditor.setValue(data.style);
                    cmSyncChangesByEditor('#template_style', styleEditor);
                }

                for(var i in data) {
                    var $editor = tinyMCE.get('popup_'+ i);
                    if($editor != undefined) {
                        $editor.setContent(data[i]? data[i] : '');
                        continue;
                    }
                    var $field = $('#edit_tabs_labels_section_content #popup_'+ i);
                    if($field.length) {
                        $field.val(data[i]);
                    }
                }

                if(data.signup_fields) {
                    var $fieldsArea = $('#popup_signup_fieldset table.data tbody tr');
                    $fieldsArea.find('input[type=checkbox][name!="signup_fields[email][enable]"]').prop('checked', false);
                    for(var field in data.signup_fields) {
                        $fieldsArea.find('input[name="signup_fields['+ field +'][enable]"]').prop('checked', data.signup_fields[field]['enable']);
                        $fieldsArea.find('input[name="signup_fields['+ field +'][label]"]').val( data.signup_fields[field]['label'] );
                        $fieldsArea.find('input[name="signup_fields['+ field +'][sort_order]"]').val( data.signup_fields[field]['sort_order'] );
                    }
                    $('.form-list .grid table.data tbody input.checkbox').each(_checkEnable);
                }

            }, 'json')
            .always(function() {
                $('#loading-mask').hide();
                $('#template_id_picker .template-list').hide();
                Event.simulate('popup_template_id', 'change');
            });

        })
        .on('click', '.template-expand', function() {
            var $btn = pjQuery_1_12_4(this);
            var $list = $btn.parent().next('.template-wrapper').find('ul');
            $btn.toggleClass('template-minify');
            $list.toggleClass('expand-all');
        })
        // .find('li[data-id='+ $('#popup_template_id').val() +']').addClass('active').contents().clone().appendTo('.template-current');
        .find('li[data-id='+ $('#popup_template_id').val() +']').addClass('active');

        var _templateCurrentHtml = pjQuery_1_12_4('#template_id_picker li[data-id='+ $('#popup_template_id').val() +']').html();
        if(_templateCurrentHtml) {
            pjQuery_1_12_4('#template_id_picker .template-current').empty().html(_templateCurrentHtml);
        }

        $('html').on('click', function(e) {
            if($('#template_id_picker .template-list').is(':visible') && e.target != $('#template_id_picker .template-list')[0] && $(e.target).parents('#template_id_picker .template-list')[0] != $('#template_id_picker .template-list')[0]) {
                $('#template_id_picker .template-list').hide();
            }
        });

    });
})(pjQuery_1_12_4);

function checkSuccessPage()
{
    pjQuery_1_12_4('#popup_success_page option[value=__none__]').attr('disabled', 'disabled');

    $customUrl = pjQuery_1_12_4("#popup_custom_success_page").parent().parent();
    if (pjQuery_1_12_4('#popup_success_page').val() == '__custom__') {
        $customUrl.show();
    } else {
        $customUrl.hide();
        pjQuery_1_12_4("#popup_custom_success_page").val('');
    }
}

function checkPopupMethod()
{
    var method = pjQuery_1_12_4('#popup_display_popup').val();
    var $delayTime = pjQuery_1_12_4("#popup_delay_time").parent().parent();
    var $pageScroll = pjQuery_1_12_4("#popup_page_scroll").parent().parent();
    var $cssSelector = pjQuery_1_12_4("#popup_css_selector").parent().parent();
    if (method == 'after_time_delay') {
        $delayTime.show();
        $pageScroll.hide();
        $cssSelector.hide();
    } else if(method == 'on_page_scroll') {
        $delayTime.hide();
        $pageScroll.show();
        $cssSelector.hide();
    } else if(method == 'on_mouseover' || method == 'on_click') {
        $delayTime.hide();
        $pageScroll.hide();
        $cssSelector.show();
    } else {
        $delayTime.hide();
        pjQuery_1_12_4("#popup_delay_time").val('');
        $pageScroll.hide();
        pjQuery_1_12_4("#popup_page_scroll").val('');
        $cssSelector.hide();
        pjQuery_1_12_4("#popup_css_selector").val('');
    }
}

function checkSendEmail()
{
    $customUrl = pjQuery_1_12_4("#popup_email_template").parent().parent();
    if (pjQuery_1_12_4('#popup_send_email').val() == '1') {
        $customUrl.show();
    } else {
        $customUrl.hide();
        pjQuery_1_12_4("#popup_email_template").val('newsletterpopup_general_email_template');
    }
}

function addDelimiter(name)
{
    pjQuery_1_12_4('#note_' + name).removeClass('note').text('').css('margin-bottom', '40px');
}

function previewPopup() {
    // we stop the default submit behaviour
    if ('tinymce' in window) {
        var obj = tinymce.get('popup_text_description');
        if (obj) {
            pjQuery_1_12_4('#popup_text_description').val( obj.getContent() );
        }

        var obj = tinymce.get('popup_text_success');
        if (obj) {
            pjQuery_1_12_4('#popup_text_success').val( obj.getContent() );
        }
    }

    prepareCodeAndStyle();
    var oOptions = {
        method: "POST",
        parameters: Form.serialize("edit_form"),
        asynchronous: true,
        onFailure: function (oXHR) {
            $('loading-mask').hide();
        },  /*
        onLoading: function (oXHR) {
            $('feedback').update('Sending data ... <img src="images/loading_indicator.gif" title="Loading..." alt="Loading..." border="0" />');
        },*/
        onSuccess: function(oXHR) {
            $('loading-mask').hide();
            var x = window.open(previewUrl, '_blank');
            x.document.open();
            x.document.write(oXHR.responseText);
            x.document.close();
        }
    };
    var oRequest = new Ajax.Updater({success: oOptions.onSuccess.bindAsEventListener(oOptions)}, previewUrl, oOptions);
}

function previewTemplate() {
    prepareCodeAndStyle();
    var oOptions = {
        method: "POST",
        parameters: Form.serialize("edit_form"),
        asynchronous: true,
        onFailure: function (oXHR) {
            $('loading-mask').hide();
        },  /*
        onLoading: function (oXHR) {
            $('feedback').update('Sending data ... <img src="images/loading_indicator.gif" title="Loading..." alt="Loading..." border="0" />');
        },*/
        onSuccess: function(oXHR) {
            $('loading-mask').hide();
            var x = window.open(previewUrl, '_blank');
            x.document.open();
            x.document.write(oXHR.responseText);
            x.document.close();
        }
    };
    var oRequest = new Ajax.Updater({success: oOptions.onSuccess.bindAsEventListener(oOptions)}, previewUrl, oOptions);
}

function getSelectionStart(editor)
{
    var start = 0;
    var cursor = editor.getCursor();
    var line = cursor.line;
    var offset = cursor.ch;
    var lines = editor.lineCount();
    var i = 0;
    for(i = 0; i < lines; i++) {
        if(i == line) {
            start += offset;
            return start;
        }
        start += editor.lineInfo(i).text.length + 1;
    }
    return start;
}

function cmSyncSelectionByEditor(textarea, editor)
{
    var pos = getSelectionStart(editor);
    //pjQuery_1_12_4(textarea).attr('disabled', false);
    pjQuery_1_12_4(textarea).prop('selectionStart', pos);
    pjQuery_1_12_4(textarea).prop('selectionEnd', pos);
}

function cmSyncChangesByTextarea(textarea, editor)
{
    //pjQuery_1_12_4(textarea).attr('disabled', false);
    editor.setValue(pjQuery_1_12_4(textarea).val());
    editor.refresh();
}

function cmSyncChangesByEditor(textarea, editor)
{
    //pjQuery_1_12_4(textarea).attr('disabled', false);
    pjQuery_1_12_4(textarea).val(editor.getValue());
    editor.refresh();
}


function prepareCodeAndStyle()
{
    pjQuery_1_12_4('#edit_form .base64_hidden').remove();
    pjQuery_1_12_4('#edit_form').append(pjQuery_1_12_4('<input type="hidden" class="base64_hidden"/>').attr('name','code_base64').val( Base64.encode(pjQuery_1_12_4('#template_code').val()) ));
    pjQuery_1_12_4('#edit_form').append(pjQuery_1_12_4('<input type="hidden" class="base64_hidden"/>').attr('name','style_base64').val( Base64.encode(pjQuery_1_12_4('#template_style').val()) ));
    if(pjQuery_1_12_4('#template_code').val() != '') {
        pjQuery_1_12_4('#template_code').attr('disabled', true);
    }
    if(pjQuery_1_12_4('#template_style').val() != '') {
        pjQuery_1_12_4('#template_style').attr('disabled', true);
    }
}
