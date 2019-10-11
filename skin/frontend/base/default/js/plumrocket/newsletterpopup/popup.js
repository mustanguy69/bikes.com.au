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


function newsletterPopupClass(_settings)
{
    var $ = pjQuery_1_12_4
        , loaded = {}
        , locked = {}
        , globalSettings = _settings
        , areaSettings = {
            area: _settings.area,
            w: screen.width,
            referer: document.URL,
            cmsPage: _settings.cmsPage,
            categoryId: _settings.categoryId,
            productId: _settings.productId,
        }
        , firstId = 0
        , prevCursorY = 0
        , _this = this;

    var _dublicateClasses = ['newspopup-blur', 'newspopup_ov_hidden'];

    _this.currentPopupId = 0;

    var _parseArguments = function(args) {
        result = {
            'id': 0,
            'callback': false
        }

        if (args.length > 1) {
            if (jQuery.isFunction(args[1])) {
                result.callback = args[1];
            }
            result.id = parseInt(args[0], 10);
        } else if (args.length == 1) {
            if (jQuery.isFunction(args[0])) {
                result.callback = args[0];
            } else {
                result.id = parseInt(args[0], 10);
            }
        }
        return result;
    }

    this.load = function() {

        if (window.navigator && window.navigator.userAgent && window.navigator.userAgent.match('/bot|crawl|slurp|spider/i')) {
            return;
        }

        var data = {}
        var args = _parseArguments(arguments);

        if (args.id > 0) {
            if (args.id in loaded) {
                if (args.callback) {
                    args.callback(true, false);
                }
                return false;
            }
            data['id'] = args.id;
        }

        // copy area settings
        for (k in areaSettings) {
            data[k] = areaSettings[k];
        }

        $.ajax({
            type:         'POST', /* need to be post for Varnish & FPC compatibility */
            url:         globalSettings.block_url,
            dataType:     'html',
            data:         data
        })
        .success(function (responseData, statusText, xhr ) {
            _afterLoad(statusText, responseData, args.callback);
        })
        .error(function(xhr, statusText) {
            _afterLoad(statusText, '', args.callback);
        });
        return true;
    }

    var _afterLoad = function(status, html, callback) {
        var succ = false;
        if (status == 'success' && html) {
            // support old templates
            if ($('#newspopup_up_bg').length > 0) {
                $('#newspopup_up_bg').remove();
                firstId = 0;
            }
            $('body').prepend(html);
            succ = true;
        }

        if (callback) {
            callback(succ, true);
        }
    }

    if (areaSettings.area != 'account') {
        this.load();
    }

    this.updateSettings = function(settings) {
        if (firstId == 0) {
            firstId = settings.id;
        }

        // Switch beetwen modes
        switch (settings.display_popup) {
            case 'after_time_delay':
                setTimeout(function() {
                    _this.show(settings.id);
                }, settings.delay_time * 1000);
                break;
            case 'leave_page':
                var actMouseOut = true;
                (function (obj, evt, fn) {
                    if(!actMouseOut) return;
                    if (obj.addEventListener) {
                        obj.addEventListener(evt, fn, false);
                    } else if (obj.attachEvent) {
                        obj.attachEvent("on" + evt, fn);
                    }
                }) (document, "mouseout", function(e) {
                    if(!actMouseOut) return;
                    e = e ? e : window.event;
                    var from = e.relatedTarget || e.toElement;
                    var cursorY = e.pageY - jQuery(document).scrollTop();
                    if ((cursorY < 0 && prevCursorY < 300) && (!from || from.nodeName == "HTML")) {
                        _this.show(settings.id);
                        actMouseOut = false;
                    }
                    prevCursorY = cursorY;
                });
                break;
            case 'on_page_scroll':
                var actScroll = true;
                $(window).on('scroll', function() {
                    if(!actScroll) return;
                    if( $(window).scrollTop() / ($(document).height() - $(window).height()) >= settings.page_scroll / 100 ) {
                        _this.show(settings.id);
                        actScroll = false;
                    }
                });
                break;
            case 'on_mouseover':
                $('body').on('mouseover.np', settings.css_selector, function() {
                    _this.show(settings.id);
                    $('body').off('mouseover.np');
                });
                break;
            case 'on_click':
                var always = false;
                $('body').on('mousedown.np', settings.css_selector, function(e) {
                    _this.show(settings.id);
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    e.preventDefault();
                    if (!always) {
                        $('body').off('mousedown.np');
                    }
                    return false;
                });
                break;
            case 'manually':
                //bindPopupLogin('a, button');
                break;
        }

        var $popupSuccess = $('.newspopup-message-success');
        var $popup = $('#newspopup_up_bg_' + settings.id);
        // Convert to new template system
        if (($popup.length == 0) && (settings.id == firstId)) {
            $popup = $('#newspopup_up_bg').addClass('newspopup_up_bg').attr('id', '#newspopup_up_bg_' + settings.id);
        }
        var olds = ['newspopup_up_bg_form', 'newspopup-animated-form', 'prpop-addedoverlay', 'newspopup-messages-holder'];
        for (var i = 0, len = olds.length; i < len; i++) {
            $popup.find('#' + olds[i]).addClass(olds[i]).removeAttr('id');
        }

        // Prepare related popups.
        $popup.find('*[data-npid]').on('click', function() {
            var popupId = $(this).data('npid');
            if (popupId > 0) {
                newsletterPopup.show(popupId);
                var npaction = $(this).data('npaction')? $(this).data('npaction') : '"Switched Popup #' + settings.id + ' to #' + popupId;
                send(globalSettings.history_url, 'Confirmed', function(data) { return (data +'&'+ jQuery.param({'npaction': npaction})); }, function(m, a) {});
            }
            return false;
        })
        .each(function() {
            var popupId = $(this).data('npid');
            if (popupId > 0) {
                newsletterPopup.load(popupId);
            }
        });

        $popup.find('*[data-npaction="Cancel"]').on('click', function() {
            popupClose();
            send(globalSettings.cancel_url, 'Cancel', function(data) { return data; }, function(m, a) {});
            return false;
        });

        // --- end ---
        var $messagesHolder    = $popup.find('.newspopup-messages-holder-tr');
        var $form             = $popup.find('form');

        loaded[ settings.id ] = $popup;

        var prepareSendData = function(loadFunction) {
            // copy area settings
            var data = {'id': settings.id}
            for (k in areaSettings) {
                data[k] = areaSettings[k];
            }
            data = loadFunction(jQuery.param(data))
            return data;
        }

        var send = function (url, action, loadFunction, finalFunction) {
            $form.find('.ajax-loader').show();
            $messagesHolder.empty();
            eventTracking(action, 'Send request', settings.id);
            blockForm(settings.id);
            _this.isSubscribed = false;

            $.ajax({
                type:         'POST',
                url:         url,
                dataType:     'json',
                data:         prepareSendData(loadFunction)
            })
            .success(function (responseData, statusText, xhr ) {
                $form.find('.ajax-loader').hide();
                if (statusText == 'success') {
                    if (responseData.error == 0) {
                        if (areaSettings.area != 'account') {
                            setCookieForDisable();
                        }
                        eventTracking(action, 'Success', settings.id);
                        finalFunction(responseData.messages, action, responseData.hasSuccessTextPlaceholders);
                    } else {
                        showMessages(responseData.messages, action);
                    }
                }
            })
            .complete(function() {
                unblockForm(settings.id);
                $form.find('.ajax-loader').hide();
            })
            .fail(function() {
                unblockForm(settings.id);
            });
        }

        $form.submit(function() {
            if (isFormBlocked(settings.id)) {
                return false;
            }

            // validation
            var validator  = new Validation( $form.get(0) );
            if (validator && validator.validate()) {} else {
                jQuery('.validation-advice').mouseenter(function(){ jQuery(this).fadeOut(); });
                return false;
            }

            send(globalSettings.action_url, 'Subscribe', function (data) {
                return data + '&' + $form.serialize();
            }, function(messages, action, hasSuccessTextPlaceholders) {
                _this.isSubscribed = true;
                showMessages(messages, action);
                $popupSuccess.find('.newspopup-message-content').html(messages.success);
                // can contain just success or nothing
                if (messages){
                    if (hasSuccessTextPlaceholders) {
                        $popupSuccess.find('.newspopup-message-close').on('click', function() {
                            popupClose();
                            popupRedirect(settings.success_url);
                        });
                    } else {
                        setTimeout(function(){
                            popupClose();
                            popupRedirect(settings.success_url);
                        }, 5000);
                    }
                } else {
                    popupClose();
                    popupRedirect(settings.success_url);
                }
            });
            return false;
        });

        $popup.find('.send').on('click', function() {
            $form.submit();
            return false;
        });

        // Close button.
        $popup.find('.close').on('click', function() {
            if (! isFormBlocked(settings.id)) {
                popupClose();
                if(!_this.isSubscribed) {
                    send(globalSettings.cancel_url, 'Cancel', function(data) { return data; }, function(m, a) {});
                }
            }
            return false;
        });

        // Close if click to background.
        if (!settings.isWidget) {
            $('#newspopup_up_bg_'+settings.id).on('click', function(e) {
                if(e.target != this || nsStopClose) return;
                if (! isFormBlocked(settings.id)) {
                    popupClose();
                    if(!_this.isSubscribed) {
                        send(globalSettings.cancel_url, 'Cancel', function(data) { return data; }, function(m, a) {});
                    }
                }
            });
        }

        $popupSuccess.find('.newspopup-message-close').on('click', function() {
            $(this).parent().hide();
        });

        // Close if press esc-button.
        $(document).keydown(function(e) {
            if (settings.id != _this.currentPopupId) return;

            var code = e.keyCode? e.keyCode : e.which;
            if(code === 27) {
                // esc
                if (! isFormBlocked(settings.id)) {
                    popupClose();
                    if(!_this.isSubscribed) {
                        send(globalSettings.cancel_url, 'Cancel', function(data) { return data; }, function(m, a) {});
                    }
                }
            }
        });

        $form.find('input').placeholder();

        var popupClose = function() {

            for(var i=0;i<_dublicateClasses.length;i++) {
                var cl = _dublicateClasses[i]+'-'+_this.currentPopupId;
                $('.'+cl).removeClass(cl);
            }
            _this.currentPopupId = 0;

            $('#wrapper,#wrap,.wrapper').removeClass('newspopup-blur');
            $('.newspopup_up_bg').hide();
            // $('body').css('overflow-y', 'auto');
            $('body').removeClass('newspopup_ov_hidden');
            $popup.hide();
            setCookieForDisable();
            if (settings.display_popup == 'leave_page') {
                $popup.remove();
                delete(loaded[settings.id]);
            }
        }

        var setCookieForDisable = function()
        {
            var seconds = (settings.cookie_time_frame > 0)
                ? settings.cookie_time_frame
                : 3650 * 86400; // emulate never expire

            setCookie('newsletterpopup_disable_popup_' + settings.id, 'yes', {
                expires: seconds,
                path: '/'
            });
        }

        var showMessages = function(messages, action)
        {
            if(messages.success || !messages.error) {
                $popup.find('.newspopup-theme').hide();
                $popup.find('.newspopup-message-success').eq(0).show();
                return;
            }

            if ($(messages).length) {
                for (_type in messages) {
                    for (var i = 0, len = messages[_type].length; i < len; i++) {
                        text = messages[_type][i] + ' <a style="float: right;" onclick="jQuery(this).parent().hide().empty(); return false;" href="#">'+ Translator.translate('Close') +'</a>';
                        $('<div></div>').addClass(_type).html(text).appendTo($messagesHolder);
                        if (_type == 'error') {
                            eventTracking(action, 'Error: ' + messages[_type][i], settings.id);
                        }
                    }
                }
            }
        }
    }

    this.show = function () {
        var id = firstId;
        var succ = false;

        args = _parseArguments(arguments);
        if (args.id > 0) {
            id = args.id;
        }

        if (id in loaded && $('#newspopup_up_bg_'+id+' .newspopup-up-form').is(':visible') == false) {

            $('#newspopup_up_bg_'+id+' .newspopup-up-form').show();
            $('#newspopup_up_bg_'+id+' .newspopup-message-success').hide();

            $('.newspopup_up_bg, #newspopup_up_bg').hide(0);
            // $('body').css('overflow-y', 'hidden');
            $('body').addClass('newspopup_ov_hidden');
            $('#wrapper,#wrap,.wrapper').addClass('newspopup-blur');
            loaded[id].show();
            succ = true;
            nsStopClose = true;
            setTimeout(function() {
                nsStopClose = false;
            }, 2000);

            _this.currentPopupId = id;
            for(var i=0;i<_dublicateClasses.length;i++) {
                $('.'+_dublicateClasses[i]).addClass(_dublicateClasses[i]+'-'+id);
            }
        }

        if (args.callback) {
            args.callback(succ);
        }
        // for bind on <a>
        return false;
    }

    var eventTracking = function(action, label, popupId) {
        if (globalSettings.enable_analytics) {
            if (typeof ga !== 'undefined' && ga !== false) {
                ga('send', 'event', 'Newsletter Popup ' + popupId, action, label.replace(/(<([^>]+)>)/ig, ''));
            } else if (typeof _gaq !== 'undefined' && _gaq !== false) {
                _gaq.push(['_trackEvent', 'Newsletter Popup ' + popupId, action, label.replace(/(<([^>]+)>)/ig, '')]);
            }
        }
    }

    var popupRedirect = function(success_url) {
        if (success_url) {
            window.location.href = success_url;
        }
    }

    var blockForm = function (id) {
        locked[id] = true;
    }

    var unblockForm = function (id){
        delete locked[id];
    }

    var isFormBlocked = function (id){
        return (id in locked) && locked[id];
    }
}

// -----------------------------------------------------

function getCookie(name) {
    var matches = document.cookie.match(new RegExp(
        "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
    ));
    return matches ? decodeURIComponent(matches[1]) : undefined;
}

function setCookie(name, value, options) {
    options = options || {};

    var expires = options.expires;

    if (typeof expires == "number" && expires) {
        var d = new Date();
        d.setTime(d.getTime() + expires*1000);
        expires = options.expires = d;
    }
    if (expires && expires.toUTCString) {
        options.expires = expires.toUTCString();
    }

    value = encodeURIComponent(value);
    var updatedCookie = name + "=" + value;

    for(var propName in options) {
        updatedCookie += "; " + propName;
        var propValue = options[propName];
        if (propValue !== true) {
            updatedCookie += "=" + propValue;
        }
    }

    document.cookie = updatedCookie;
}

function deleteCookie(name) {
    setCookie(name, "", { expires: -1, path: '/' });
}

/*! http://mths.be/placeholder v2.0.7 by @mathias */
(function(f,h,$){var a='placeholder' in h.createElement('input'),d='placeholder' in h.createElement('textarea'),i=$.fn,c=$.valHooks,k,j;if(a&&d){j=i.placeholder=function(){return this};j.input=j.textarea=true}else{j=i.placeholder=function(){var l=this;l.filter((a?'textarea':':input')+'[placeholder]').not('.placeholder').bind({'focus.placeholder':b,'blur.placeholder':e}).data('placeholder-enabled',true).trigger('blur.placeholder');return l};j.input=a;j.textarea=d;k={get:function(m){var l=$(m);return l.data('placeholder-enabled')&&l.hasClass('placeholder')?'':m.value},set:function(m,n){var l=$(m);if(!l.data('placeholder-enabled')){return m.value=n}if(n==''){m.value=n;if(m!=h.activeElement){e.call(m)}}else{if(l.hasClass('placeholder')){b.call(m,true,n)||(m.value=n)}else{m.value=n}}return l}};a||(c.input=k);d||(c.textarea=k);$(function(){$(h).delegate('form','submit.placeholder',function(){var l=$('.placeholder',this).each(b);setTimeout(function(){l.each(e)},10)})});$(f).bind('beforeunload.placeholder',function(){$('.placeholder').each(function(){this.value=''})})}function g(m){var l={},n=/^jQuery\d+$/;$.each(m.attributes,function(p,o){if(o.specified&&!n.test(o.name)){l[o.name]=o.value}});return l}function b(m,n){var l=this,o=$(l);if(l.value==o.attr('placeholder')&&o.hasClass('placeholder')){if(o.data('placeholder-password')){o=o.hide().next().show().attr('id',o.removeAttr('id').data('placeholder-id'));if(m===true){return o[0].value=n}o.focus()}else{l.value='';o.removeClass('placeholder');l==h.activeElement&&l.select()}}}function e(){var q,l=this,p=$(l),m=p,o=this.id;if(l.value==''){if(l.type=='password'){if(!p.data('placeholder-textinput')){try{q=p.clone().attr({type:'text'})}catch(n){q=$('<input>').attr($.extend(g(this),{type:'text'}))}q.removeAttr('name').data({'placeholder-password':true,'placeholder-id':o}).bind('focus.placeholder',b);p.data({'placeholder-textinput':q,'placeholder-id':o}).before(q)}p=p.removeAttr('id').hide().prev().attr('id',o).show()}p.addClass('placeholder');p[0].value=p.attr('placeholder')}else{p.removeClass('placeholder')}}}(this,document,pjQuery_1_12_4));
