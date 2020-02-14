jQuery(document).ready(function(){
    var popup = "<div class='popup-overlay col-sm-12'><div class='popup-content col-sm-10 col-md-6 col-lg-5 col-xl-4'><i class='fa fa-times-circle-o fa-3x close-popup'></i> </div> </div>";
    var content = '<div class="col-sm-12">\n' +
        '<div class="header-1 col-sm-12"><h1>E-BIKE SUPER STORE</h1></div>\n' +
        '<div class="header-2 col-sm-12"><h2>OVER 25 DIFFERENT MODELS IN STOCK</h2></div>\n' +
        '<div class="col-sm-12 body">'+
          '<div class="col-sm-4 col-xs-4"><img src="/skin/frontend/tv_bikeworld_package/tv_bikeworld2/images/shimano.png"/></div>'+
          '<div class="col-sm-4 col-xs-4"><img src="/skin/frontend/tv_bikeworld_package/tv_bikeworld2/images/bosch.png"/></div>'+
          '<div class="col-sm-4 col-xs-4"><img src="/skin/frontend/tv_bikeworld_package/tv_bikeworld2/images/bafang.png"/></div>'+
        '</div>' +
        '<div class="col-sm-12 body-img"><img src="/skin/frontend/tv_bikeworld_package/tv_bikeworld2/images/popup-img.jpg"/></div>'+
        '<div class="col-sm-12 popup-footer"><h1>COME SEE US FOR A TEST RIDE TODAY !</h1></div>\n' +
        '</div>' +
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
            window.location.href = "https://www.bikes.com.au/electric-bike/";
        });
    });
});
