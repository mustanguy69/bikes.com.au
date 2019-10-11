jQuery.noConflict();
jQuery(document).ready(function() {
    jQuery("#zipmoney-learnmore").fancybox({
        'autoSize': false,
        'href': '',
        'width': 690,
        'height': 640,
        'transitionIn': 'elastic',
        'transitionOut': 'elastic',
        'type': 'iframe',
        'padding': 5,
        'fitToView': true
    });
    jQuery(".zipmoney-hover").hover(function() {
        jQuery(this).click();
    });


});
   