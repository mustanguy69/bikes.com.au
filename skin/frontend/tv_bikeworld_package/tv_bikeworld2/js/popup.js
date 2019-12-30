jQuery(document).ready(function(){
    var popup = "<div class='popup-overlay col-sm-12'><div class='popup-content col-sm-11 col-md-8 col-lg-6 col-xl-5'><i class='fa fa-times-circle-o fa-3x close-popup'></i> </div> </div>";
    var content = '<div class="col-sm-9 col-sm-offset-3">\n' +
        '<div class="maps">&nbsp;</div>\n' +
        '<div class="maps" ><span style="font-size: 43px;">ALL <br> <strong>SUMMER <br> LONG</strong></span><br><br></div>\n' +
        '<div class="maps"><span style="font-size: 20px;"><br><br>MASSIVE SAVINGS ON ALL SCOTT & FOCUS BIKES .</span></div>';

    jQuery( window ).load(function() {
        jQuery('.cms-home2 .wrapper').prepend(popup);
        jQuery('.popup-content').append(content);
        jQuery('.popup-overlay, .close-popup').on('click', function() {
            jQuery('.popup-overlay').remove();
        });
        jQuery('.popup-content').on('click', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            window.location.href = "https://www.bikes.com.au/clearance-sale/focus_scott/";
        });
    });
});