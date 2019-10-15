jQuery(document).ready(function(){
    var popup = "<div class='popup-overlay col-sm-12'><div class='popup-content col-sm-4'><i class='fa fa-times-circle-o fa-3x close-popup'></i> </div> </div>";
    var content = '<div class="col-sm-12">\n' +
        '<div class="maps">&nbsp;</div>\n' +
        '<div class="maps" style="text-align: center;"><span style="font-size: x-large;"><strong>YES WE ARE OPEN!</strong></span></div>\n' +
        '<div class="maps" style="text-align: center;">&nbsp;</div>\n' +
        '<div class="maps" style="text-align: center;"><span style="font-size: x-large;"><strong>REGULAR TRADING HOURS</strong></span></div>\n' +
        '<div class="maps" style="text-align: center;">&nbsp;</div>\n' +
        '<div class="maps" style="text-align: center;"><span style="font-size: small;"><strong>FROM OCTOBER 13TH THROUGH TO OCTOBER 27TH HIGH STREET IS CLOSED TO VEHICLES BETWEEN ST KILDA &amp; WILLIAMS ROADS. </strong></span></div>\n' +
        '<div class="maps" style="text-align: center;"><span style="font-size: small;"><strong>THERE IS PARKING ON CHAPEL STREET. DURING THIS TIME, SOME SIDE STREETS WILL ALLOW TWO WAY ACCESS FROM MALVERN ROAD AS PER THE DIAGRAM BELOW.</strong></span></div>\n' +
        '<div class="maps" style="text-align: center;">&nbsp;</div>\n' +
        '<div class="maps" style="text-align: center;">&nbsp;</div>\n' +
        '<div class="maps" style="text-align: center;"><span style="font-size: small;"><strong><img alt="" src="'+ document.location.href +'/media/wysiwyg/Major_Works.JPG" /></strong></span></div>';
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