jQuery(document).ready(function(){
    var popup = "<div class='popup-overlay col-sm-12'><div class='popup-content col-sm-4'><i class='fa fa-times-circle-o fa-3x close-popup'></i> </div> </div>";
    var content = '<div class="col-sm-12">\n' +
        '<div class="maps">&nbsp;</div>\n' +
        '<div class="maps" style="text-align: center;"><span style="font-size: x-large;"><strong>STORE OPEN MONDAY</strong></span></div>\n' +
        '<div class="maps" style="text-align: center;"><span style="font-size: x-medium;"><strong>9AM - 6PM</strong></span></div>\n' +
        '<div class="maps" style="text-align: center;"><span style="font-size: x-large;"><strong>CLOSED - TUESDAY 5th</strong></span></div>\n' +
        '<div class="maps" style="text-align: center;"><span style="font-size: x-medium;"><strong>MELBOURNE CUP DAY</strong></span></div>\n' +
        '<div class="maps" style="text-align: center;"><span style="font-size: small;"><strong><img alt="" src="'+ document.location.href +'/skin/frontend/tv_bikeworld_package/tv_bikeworld2/images/hero_melbourne_cup.png" /></strong></span></div>';
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