jQuery(document).ready(function(){
    var popup = "<div class='popup-overlay col-sm-12'><div class='popup-content col-sm-11 col-md-7 col-lg-6 col-xl-6'><i class='fa fa-times-circle-o fa-3x close-popup'></i> </div> </div>";
    var content = '<div class="col-sm-9">\n' +
        '<div class="maps">&nbsp;</div>\n' +
        '<div class="maps" ><span style="font-size: 43px;">ALL <br> <strong>SUMMER <br> LONG</strong></span><br><br><br></div></div>\n' +
        '<div class="col-sm-12" style="margin-left:0;background-color: rgba(249, 246, 9,0.9);padding-top: 15px;padding-bottom: 8px">\n' +
        '<div class="maps"><span style="font-size: 1.7vw; color: red;padding-left: 15px; font-weight: bold" class="text-yellow">MASSIVE SAVINGS ON ALL SCOTT & FOCUS BIKES</span></div>'+
        '</div>';

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