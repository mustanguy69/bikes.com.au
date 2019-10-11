if (Window){
    // Window (defined in Window.js) could be overwritten.
    // Store Window into ProtoTypeWindowClass to avoid using an overwritten Window.
    // It depends on the order of js being loaded.
    var ProtoTypeWindowClass = Window;
}
var WidgetModal = Class.create();
WidgetModal.prototype = {
    initialize: function (vInstanceName, vElementId, aPageContents, vDefaultContent) {
        this.vInstanceName = vInstanceName;
        this.vElementId = vElementId;
        this.aPageContents = aPageContents;
        this.vDefaultContent = vDefaultContent;

        this.createTempElement();

        // when the window size changes (direction of screen changes), close the popup
        window.addEventListener("resize", function () {
            var popup = $('zipmoney_popup_window_' + vElementId);
            if (popup && popup != undefined) {
                Windows.close('zipmoney_popup_window_' + vElementId);
            }
        });
    },

    /**
     * Create temporary invisible elements in body, so that can get the actual size of the popup
     */
    createTempElement: function () {
        var aPages = this.aPageContents;
        if (aPages === undefined || aPages.length === undefined || aPages.length <= 0) {
            return;
        }

        /**
         * Because each page could have different size, so create one for each page.
         */
        for (var i = 0; i < aPages.length; i++) {
            var tempId = 'temp_popup_' + i;
            var temp = $(tempId);
            if (temp || temp != undefined) {
                return;
            }
            var vHtml = this.getPopupHtml(true, i);
            temp = new Element('div', {
                id: tempId,
                class: 'temp overlay_zipmoney_popup_window_temp',
                style: 'position: absolute; top: 0; left: 0; display: none;'
            });
            temp.update(vHtml);
            $$('body').first().insert({'bottom': temp});
        }
    },

    /**
     * Get html for specific page
     * @param bTemp
     * @param iPage
     * @returns {string}
     */
    getPopupHtml: function (bTemp, iPage) {
        var aPages = this.aPageContents;
        var vDefaultContent = this.vDefaultContent;
        var vHtml = '';
        if (aPages === undefined || aPages.length === undefined || aPages.length <= 0) {
            vHtml += vDefaultContent;
        } else {
            if (bTemp) {    // for temporary elements
                if (!iPage) {
                    iPage = 0;
                }
                vHtml += '<div id="zipmoney-widget-frame-page-temp' + (iPage + 1) + '" class="zipmoney-widget-page-temp">';
                vHtml += aPages[iPage];
                vHtml += '</div>';
            } else {
                for (var i = 0; i < aPages.length; i++) {
                    vHtml += '<div id="zipmoney-widget-frame-page' + (i + 1) + '" class="zipmoney-widget-page ';
                    if (i !== 0) {
                        vHtml += 'hidden';
                    }
                    vHtml += '" ';
                    if (i !== 0) {
                        vHtml += 'style="display:none;"';
                    }
                    vHtml += '>' + aPages[i] + '</div>';
                }
            }
        }
        return vHtml;
    },

    showPopup: function () {
        var WindowClass;
        if (ProtoTypeWindowClass) {
            WindowClass = ProtoTypeWindowClass;
        } else {
            WindowClass = Window;
        }
        var oPopup = new WindowClass({
            id: 'zipmoney_popup_window_' + this.vElementId,
            className: 'zipmoney_popup_window',
            minimizable: false,
            maximizable: false,
            showEffectOptions: {
                duration: 0.4
            },
            hideEffectOptions: {
                duration: 0.4
            },
            destroyOnClose: true
        });

        var vInstanceName = this.vInstanceName;
        var aPages = this.aPageContents;
        var vHtml = this.getPopupHtml(false, null);

        // add controllers (close/previous/next buttons) to the modal
        vHtml = '<a id="zipmoney-widget-modal-close" title="Close" class="windowjs-item windowjs-close" href="javascript:;" onclick="' + vInstanceName + '.closePopup();"></a>' + vHtml;
        if (aPages !== undefined && aPages.length !== undefined && aPages.length >= 2) {
            vHtml += '<a title="Previous" id="zipmoney-widget-modal-previous" class="windowjs-nav windowjs-prev" href="javascript:;" onclick="' + vInstanceName + '.previousPage();"><span></span></a>';
            vHtml += '<a title="Next" id="zipmoney-widget-modal-next" class="windowjs-nav windowjs-next" href="javascript:;" onclick="' + vInstanceName + '.nextPage();"><span></span></a>';
        }

        oPopup.getContent().update(vHtml);
        oPopup.setZIndex(10000);
        this.popup = oPopup;
        this.resizePopup(true, 0);      // show the popup
        window.scroll(0,0);
    },

    /**
     * show the popup, or resize and relocate it
     * @param bFirst
     * @param iPage
     */
    resizePopup: function (bFirst, iPage) {
        var oPopup = this.popup;
        var tempId = 'temp_popup_' + iPage;
        var temp = $(tempId);
        if (temp && temp != undefined) {
            var modalWidth = temp.getWidth();
            var modalHeight = temp.getHeight();

            var bOverHeight = false;
            if (modalWidth > window.innerWidth - 40) {
                modalWidth = window.innerWidth - 40;
            }
            if (modalHeight > window.innerHeight - 40) {
                bOverHeight = true;
                modalHeight = window.innerHeight - 40;
            }
            oPopup.setSize(modalWidth, modalHeight, true);  // set the size of the popup

            // get the position of the popup
            var windowScroll = WindowUtilities.getWindowScroll(oPopup.options.parent);
            var pageSize = WindowUtilities.getPageSize(oPopup.options.parent);
            var top = (pageSize.windowHeight - (oPopup.height + oPopup.heightN + oPopup.heightS)) / 2;
            top += windowScroll.top;
            var left = (pageSize.windowWidth - (oPopup.width + oPopup.widthW + oPopup.widthE)) / 2;
            left += windowScroll.left;

            if (bFirst) {
                if (bOverHeight) {
                    oPopup.showCenter(false, top, left);
                } else {
                    oPopup.showCenter(true);
                }
            } else {
                oPopup.setLocation(top, left);
                oPopup.toFront();
            }
        }
    },

    addListenerToElement: function (vElementId) {
        var parentElement = this;
        if (!vElementId) {
            vElementId = parentElement.vElementId
        }

        document.observe('dom:loaded', function () {
            var oEle = $(vElementId);
            if (oEle == undefined) {
                return;
            }
            oEle.observe('click', function (oEvent) {
                parentElement.showPopup();
                Event.stop(oEvent);
            });
        }.bind(parentElement));
    },

    addListenerToMultipleElements: function (vEleSelector) {
        var parentElement = this;
        $$(vEleSelector).each(function (oEle) {
            if (oEle == undefined) {
                return;
            }
            oEle.observe('click', function (oEvent) {
                parentElement.showPopup();
                Event.stop(oEvent);
            });
        });
    },

    nextPage: function () {
        var iTotalNum = this.aPageContents.length;
        var iCurPageNum = this.findCurrentPage();

        // get next page number
        var iNextPageNum = ((iCurPageNum + 1) > iTotalNum) ? 1 : (iCurPageNum + 1);
        var cur = $('zipmoney-widget-frame-page' + iCurPageNum);
        var next = $('zipmoney-widget-frame-page' + iNextPageNum);
        cur.addClassName('hidden');
        cur.hide();
        next.removeClassName('hidden');
        next.show();

        this.resizePopup(false, iNextPageNum - 1);
    },

    previousPage: function () {
        var iTotalNum = this.aPageContents.length;
        var iCurPageNum = this.findCurrentPage();

        // get previous page number
        var iPrePageNum = ((iCurPageNum - 1) <= 0) ? iTotalNum : (iCurPageNum - 1);
        var cur = $('zipmoney-widget-frame-page' + iCurPageNum);
        var pre = $('zipmoney-widget-frame-page' + iPrePageNum);
        cur.addClassName('hidden');
        cur.hide();
        pre.removeClassName('hidden');
        pre.show();

        this.resizePopup(false, iPrePageNum - 1);
    },

    findCurrentPage: function () {
        var iCurPageNum = 0;
        $$(".zipmoney-widget-page").each(function (elmt) {
            if (!hasClass(elmt, "hidden")) {
                // found current page
                var vId = elmt.id;
                iCurPageNum = parseInt(vId.replace('zipmoney-widget-frame-page', ''));
                return false;
            }
        });
        return iCurPageNum;
    },

    closePopup: function () {
        Windows.close('zipmoney_popup_window_' + this.vElementId);
    }
};

var AdditionLink = Class.create();
AdditionLink.prototype = {
    initialize: function (vButtonId, vLkSelector) {
        this.vButtonId = vButtonId;
        this.vLkSelector = vLkSelector;
    },

    adjustWidth: function () {
        var vButtonId = this.vButtonId;
        var vLkSelector = this.vLkSelector;

        $$(vLkSelector).each(function (oEle) {
            if (oEle == undefined) {
                return;
            }
            var iButtonWidth = $(vButtonId).offsetWidth;
            var iLinkWidth = 0;
            var iFontSize = 13; //px

            iLinkWidth = oEle.offsetWidth;
            while (iButtonWidth < iLinkWidth && iFontSize > 7) {
                iFontSize = iFontSize - 1;
                oEle.style.fontSize = iFontSize + 'px';
                iLinkWidth = oEle.offsetWidth;
            }
        });
    }
};

var StripBanner = Class.create();
StripBanner.prototype = {
    initialize: function (vEleId, vEleClass, vHtml) {
        this.vEleId = vEleId;
        this.vEleClass = vEleClass;
        this.vHtml = vHtml;
    },

    adjustHeight: function () {
        var vEleClass = this.vEleClass;
        var temp = new Element('div', {
            class: 'temp',
            style: 'position: absolute; top: 0; left: -9999999em'
        }), width, height;
        temp.update(this.vHtml);
        $$('body').first().insert({'bottom': temp});
        height = temp.getHeight() + 5;
        temp.remove();
        $$("." + vEleClass).each(function (elmt) {
            elmt.style.height = height + 'px';
        });
    }
};

function hasClass(el, selector) {
    var className = " " + selector + " ";
    if ((" " + el.className + " ").replace(/[\n\t]/g, " ").indexOf(className) > -1) {
        return true;
    }
    return false;
}