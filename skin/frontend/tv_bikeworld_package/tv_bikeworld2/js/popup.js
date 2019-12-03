jQuery(document).ready(function(){
    var popup = "<div class='popup-overlay col-sm-12'><div class='popup-content col-sm-4'><i class='fa fa-times-circle-o fa-3x close-popup'></i> </div> </div>";
    var content = '<div class="col-sm-12">\n' +
        '<div class="maps">&nbsp;</div>\n' +
        '<div class="maps" style="text-align: center;"><span style="font-size: x-large;"><strong>FOR DELIVERY BEFORE CHRISTMAS <br> WEB ORDERS MUST BE PLACED BY :</strong></span><br><br></div>\n' +
        '<div class="maps" style="text-align: center;"><span style="font-size: x-medium;"><strong>- PERTH 15TH DEC</strong></span></div>\n' +
        '<div class="maps" style="text-align: center;"><span style="font-size: x-medium;"><strong>- BRISBANE 16TH DEC</strong></span></div>\n' +
        '<div class="maps" style="text-align: center;"><span style="font-size: x-medium;"><strong>- MELBOURNE / ADELAIDE / SYDNEY 19TH DEC</strong></span></div>\n' +
        '<div class="maps" style="text-align: center;"><span style="font-size: x-medium;"><strong>- REGIONAL AREAS - ADD AT LEAST 1 WEEK TO CAPITAL CITY DATES FOR DELIVERY BEFORE CHRISTMAS.</strong></span></div>\n';
    jQuery( window ).load(function() {
        jQuery('.cms-home2 .wrapper').prepend(popup);
        jQuery('.popup-content').append(content);
        jQuery('.popup-overlay, .close-popup').on('click', function() {
            jQuery('.popup-overlay').remove();
        });
        jQuery('.popup-content').on('click', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
        });
    });
});