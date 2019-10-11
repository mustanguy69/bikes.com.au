var iframeCheckout = Class.create();
iframeCheckout.prototype = {
    initialize: function (vUrl, vType, vErrorMessage) {
        this.vUrl = vUrl;
        this.vType = vType;
        this.vErrorMessage = vErrorMessage;
    },

    redirectToCheckout: function () {
        var vUrl = this.vUrl;
        var vType = this.vType;
        var vErrorMessage = this.vErrorMessage;
        new Ajax.Request(vUrl, {
            parameters: {isAjax: 1, method: 'GET'},
            onSuccess: function(transport) {
                try{
                    var response = eval('(' + transport.responseText + ')');
                } catch (e) {
                    response = {};
                }
                if (!response.url) {
                    alert(vErrorMessage);
                    resetButton(vType);
                    return;
                }
                var vRedirectUrl = response.url;        // example: http://app.dev1.zipmoney.com.au/#/cart/10002/91179

                if (typeof(zipMoney) != 'undefined') {
                    zipMoney.checkout(vRedirectUrl);    // call zipMoney iframe library.
                    window.scroll(0, 0);
                } else {
                    alert(vErrorMessage);
                }
                resetButton(vType);
            },
            onFailure: function() {
                alert(vErrorMessage);
                resetButton(vType);
            }
        });
    }
};

function resetButton(vType)
{
    if (vType == 'pdp') {
        toggleExpressButton(true);
    } else if (vType == 'cart') {
        resetRedirectButtonText();
    } else if (vType == 'onestepcheckout') {
        // do nothing
    } else if (vType == 'onepagecheckout') {
        // do nothing
    }
}

function toggleExpressButton($bShowButton)
{
    if ($bShowButton) {
        $$('.zip-express-btn').each(function (oEle) {
            if (oEle == undefined) {
                return true;
            }
            oEle.show();
        });
        $$('.wait-for-redirecting-to-zip').each(function (oEle) {
            if (oEle == undefined) {
                return true;
            }
            oEle.hide();
        });
    } else {
        $$('.zip-express-btn').each(function (oEle) {
            if (oEle == undefined) {
                return true;
            }
            oEle.hide();
        });
        $$('.wait-for-redirecting-to-zip').each(function (oEle) {
            if (oEle == undefined) {
                return true;
            }
            oEle.show();
        });
    }
}

function toggleButton($bShowButton)
{
    var cartButton = $('zipmoney-express-cart');
    var checkoutButton = $('zipmoney-checkout-express-payment-button');
    var waitingImg = $('redirecting-to-zipmoney');
    if ($bShowButton) {
        if (cartButton) {
            cartButton.show();
        }
        if (checkoutButton) {
            checkoutButton.show();
        }
        if (waitingImg) {
            waitingImg.hide();
        }
    } else {
        if (cartButton) {
            cartButton.hide();
        }
        if (checkoutButton) {
            checkoutButton.hide();
        }
        if (waitingImg) {
            waitingImg.show();
        }
    }
}

function showRedirectingText()
{
    toggleButton(false);
}

function resetRedirectButtonText()
{
    toggleButton(true);
}