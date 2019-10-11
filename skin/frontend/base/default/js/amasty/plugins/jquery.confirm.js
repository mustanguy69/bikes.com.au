document.observe("dom:loaded", function () {
    AmAjaxShoppCartLoad();

    (function ($) {
        $.confirm = function (params) {
            $('#confirmOverlay', window.parent.document).remove();

            var buttonHTML = '',
                value;
            $.each(params.buttons, function (name, obj) {

                // Generating the markup for the buttons:
                value = obj['name'];

                if (obj['timer']) {
                    value += obj['timer'];

                }

                buttonHTML += '<button href="#" class="button ' + obj['class'] + '" title="' + obj['name'] + '">' + value + '</button>';

                if (!obj.action) {
                    obj.action = function () {
                    };
                }
            });

            var confirmOverlay = document.createElement('div');
            confirmOverlay = $(confirmOverlay); // fix for IE
            confirmOverlay.attr('id', 'confirmOverlay');

            var hideDiv = $('<div/>', {
                class: 'amcart-hide'
            }).appendTo(confirmOverlay);
            hideDiv.click(function () {
                $.confirm.hide();
            });

            var confirmBox = document.createElement('div');
            confirmBox = $(confirmBox); // fix for IE
            confirmBox.attr('id', 'confirmBox');
            switch (AmAjaxObj.options['align']) {
                case "1":
                    confirmBox.addClass('am-top');
                    break;
                case "2":
                    confirmBox.addClass('am-top-left');
                    break;
                case "3":
                    confirmBox.addClass('am-top-right');
                    break;
                case "4":
                    confirmBox.addClass('am-left');
                    break;
                case "5":
                    confirmBox.addClass('am-right');
                    break;
                case "0":
                default:
                    confirmBox.addClass('am-center');
            }
            confirmBox.appendTo(confirmOverlay);

            var cross = $('<span class="cross"></span>').html('&times;');
            cross.click(function () {
                jQuery.confirm.hide()
            });
            confirmBox.append(cross);

            var confirmButtons = document.createElement('div');
            confirmButtons = $(confirmButtons); // fix for IE
            confirmButtons.attr('id', 'confirmButtons');
            confirmButtons.html(buttonHTML);
            confirmButtons.appendTo(confirmBox);

            var messageBox = document.createElement('div');
            messageBox = $(messageBox); // fix for IE
            messageBox.attr('id', 'messageBox');
            messageBox.html(params.message);
            messageBox.insertBefore(confirmButtons);

            if (params.related) {
                var related = document.createElement('div');
                related = $(related); // fix for IE
                related.attr('class', 'amcart-related-block');
                related.html(params.related);
                related.appendTo(confirmBox);
            }

            confirmOverlay.hide().appendTo($('body', window.parent.document));

            confirmOverlay.fadeIn();

            var buttons = $('#confirmButtons button', window.parent.document),
                i = 0;

            $.each(params.buttons, function (name, obj) {
                buttons.eq(i++).click(function () {
                    obj.action();
                    return false;
                });
            });
            $.confirm.timer();
        };

        $.confirm.timer = function () {
            var elem = $('#confirmButtons .timer', window.parent.document),
                value = elem.text(),
                sec = parseInt(value.replace(/\D+/g, ""));
            if (sec) {
                document.timer = setInterval("AmAjaxObj.oneSec();", 1000);
            }
        }

        $.confirm.hide = function () {
            $('#confirmOverlay', window.parent.document).fadeOut(function () {
                var hasPopupOptions = $('#confirmBox .super-attribute-select', window.parent.document).length;
                $(this).remove();
                if ($('.col-main', window.parent.document).length && hasPopupOptions) {
                    if ('undefined' != typeof(currentOptionsPrice)) {
                        optionsPrice = currentOptionsPrice;
                    }
                    if ('undefined' != typeof(currentopConfig)) {
                        opConfig = currentopConfig;
                    }
                    if ('undefined' != typeof(currentspConfig)) {
                        spConfig = currentspConfig;
                    }
                }
                if ('undefined' != typeof(parent.jQuery.fancybox)) {
                    parent.jQuery.fancybox.close();
                }

            });
        }
    })(jQuery);

});
