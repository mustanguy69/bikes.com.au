
var expressWidgetController = {
    initExpressButtonConfig: function(expressCheckoutEnabled)
    {
        if (!expressCheckoutEnabled) {
            showExpressButtonCart(false);
            showExpressButtonProduct(false);
        }
    },
    initWidgetCartConfig: function(expressCheckoutEnabled, expressCartEnabled)
    {
        if (expressCheckoutEnabled && expressCartEnabled) {
            enableWidgetCart(false);
        }
    },
    initWidgetProductConfig: function(expressCheckoutEnabled, expressProductEnabled)
    {
        if (expressCheckoutEnabled && expressProductEnabled) {
            enableWidgetProduct(false);
        }
    },
    onExpressEnabledChange: function(value)
    {
        if (value == 1) {
            /*
             Express Checkout enabled
                Show Express-Cart and Express-Product, and set them to 'Yes' (default)
                Disable Widget-Cart and Widget-Product
             */
            showExpressButtonCart(true);
            showExpressButtonProduct(true);
            enableWidgetCart(false);
            enableWidgetProduct(false);
        } else {
            /*
             Express Checkout disabled
                Hide Express-Cart and Express-Product, and set them to 'No'
                Enable Widget-Cart and Widget-Product, and set them to 'Yes' (default)
             */
            showExpressButtonCart(false);
            showExpressButtonProduct(false);
            enableWidgetCart(true);
            enableWidgetProduct(true);
        }
    },
    onExpressCartChange: function(value) {
        if (value == 1) {
            /*
             Express-Cart enabled
                Disable Widget-Cart
             */
            enableWidgetCart(false);
        } else {
            /*
             Express-Cart disabled
                Enable Widget-Cart, and set them to 'Yes' (default)
             */
            enableWidgetCart(true);
        }
    },
    onExpressProductChange: function(value) {
        if (value == 1) {
            /*
             Express-Product enabled
                Disable Widget-Product
             */
            enableWidgetProduct(false);
        } else {
            /*
             Express-Product disabled
                Enable Widget-Product, and set them to 'Yes' (default)
             */
            enableWidgetProduct(true);
        }
    }
};

function showExpressButtonCart(bShow) {
    var rowElement = $("row_payment_zipmoney_express_checkout_cart_express_button_active");
    var element = $("payment_zipmoney_express_checkout_cart_express_button_active");
    if (bShow) {
        rowElement.show();
        element.setValue(1);
    } else {
        rowElement.hide();
    }
}

function showExpressButtonProduct(bShow) {
    var rowElement = $("row_payment_zipmoney_express_checkout_product_express_button_active");
    var element = $("payment_zipmoney_express_checkout_product_express_button_active");
    if (bShow) {
        rowElement.show();
        element.setValue(1);
    } else {
        rowElement.hide();
    }
}

function enableWidgetCart(bEnable) {
    var element = $("payment_zipmoney_widgets_onfiguration_cartactive");
    if (bEnable) {
        element.setValue('enabled');
        element.enable();
    } else {
        element.disable();
    }
}

function enableWidgetProduct(bEnable) {
    var element = $("payment_zipmoney_widgets_onfiguration_productactive");
    if (bEnable) {
        element.setValue('enabled');
        element.enable();
    } else {
        element.disable();
    }
}



var frontendExperienceController = {
    initConfig: function(frontendExperienceEnabled)
    {
        if (!frontendExperienceEnabled) {
            showFrontendExperienceSub(false);
        }
    },

    onEnabledChange: function(value)
    {
        if (value == 1) {
            showFrontendExperienceSub(true);
        } else {
            showFrontendExperienceSub(false);
        }
    }
};

function showFrontendExperienceSub(bShow) {
    $$("#payment_zipmoney_marketing_banners .nested").each(function (elmt) {
        if (bShow) {
            elmt.show();
        } else {
            elmt.hide();
        }
    });
}
