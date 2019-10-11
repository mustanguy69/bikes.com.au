
jQuery(document).ready(function($){
	var specialOffer = jQuery('#header-special-offer');
	specialOffer.find('.header-special-offer-close').click(function() {
		specialOffer.slideUp('slow');
	});
	jQuery("#back-top").hide();
	// fade in #back-top
	jQuery(function () {
		jQuery(window).scroll(function () {
			if (jQuery(this).scrollTop() > 100) {
				jQuery('#back-top').fadeIn();
			} else {
				jQuery('#back-top').fadeOut();
			}
		});
		// scroll body to 0px on click
		jQuery('#back-top').click(function () {
			jQuery('body,html').animate({
				scrollTop: 0
			}, 800);
			return false;
		});
	});

});

jQuery(document).ready(function($) {
	$('.block-social-right .social-icon').mouseenter(function () {
	    $(this).stop();
	    $(this).animate({width: '160'}, 500, 'swing', function () {
	    });
	});
	$('.block-social-right .social-icon').mouseleave(function () {
	    $(this).stop();
	    $(this).animate({width: '43'}, 500, 'swing', function () {
	    });
	});        
}); 


jQuery(document).ready(function($){    // Add toggle show function to Free Text Search 
    $(".search-below-768px").click(function(){ 
               
        if (!$("#search_mini_form").length)    {
            $("#freetextsearch_form").show();
          }else{
            $("#search_mini_form").show();
          }
    });    // Add toggle hide function to Free Text Search 
          
    $('.form-search .fa-times').click(function(){        
        if (!$("#search_mini_form").length){        
            $("#freetextsearch_form").hide();        
        }else{
            $("#search_mini_form").hide();
        }    
    });
    
    $('.form-search button.button').click(function(){
        var windowSize = $(window).width();
        if (windowSize < 992) {
			$('#freetextsearch_form').hide();
			$('#search_mini_form').hide();
		}else{
			$('#freetextsearch_form').show();
			$('#search_mini_form').show();			
		}
    });
    $( window ).resize(function() {
        var windowSize = $(window).width();
        if (windowSize > 992) {
            $('#freetextsearch_form').show();
            $('#search_mini_form').show();
        }
    });

    $(".header-toplinks").click(function(){
        $(".quick-access-links").toggle();
    });
    $(".form-language .drop-trigger").click(function(){
        $(".drop-trigger .sub-lang").toggle();
    });
    $(".form-currency .drop-trigger").click(function(){
        $(".drop-trigger .sub-currency").toggle();
    });

	$("#dl-menu-1 .search-below-768px").click(function(){
		$(".searchformwrapper").css({ 'z-index': "999999" })
	});

});

jQuery(window).scroll(function () {
      if (jQuery(this).scrollTop() > 200) {
       jQuery('.header').addClass("header-sticky");
      } else {
       jQuery('.header').removeClass("header-sticky");
      }
    });


( function( $ ) {
$( document ).ready(function() {
$('#accordion li > span').on('click', function(){
		$(this).removeAttr('href');
		var element = $(this).parent('li');
		if (element.hasClass('open')) {
			element.removeClass('open');
			element.find('li').removeClass('open');
			element.find('ul').slideUp();
		}
		else {
			element.addClass('open');
			element.children('ul').slideDown();
			element.siblings('li').children('ul').slideUp();
			element.siblings('li').removeClass('open');
			element.siblings('li').find('li').removeClass('open');
			element.siblings('li').find('ul').slideUp();
		}
	});


	function rgbToHsl(r, g, b) {
	    r /= 255, g /= 255, b /= 255;
	    var max = Math.max(r, g, b), min = Math.min(r, g, b);
	    var h, s, l = (max + min) / 2;

	    if(max == min){
	        h = s = 0;
	    }
	    else {
	        var d = max - min;
	        s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
	        switch(max){
	            case r: h = (g - b) / d + (g < b ? 6 : 0); break;
	            case g: h = (b - r) / d + 2; break;
	            case b: h = (r - g) / d + 4; break;
	        }
	        h /= 6;
	    }
	    return l;
	}
});
} )( jQuery );

