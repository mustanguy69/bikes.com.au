jQuery(document).ready(function(){
	const $opener = jQuery('#narrow-by-list dt');
	$opener.click(function (e) {
		jQuery(this).toggleClass('active');
		if(jQuery(this).hasClass('active')){
			jQuery(this).next().slideDown(600);
		}
		else {
			jQuery(this).next().slideUp(600);
		}
	});
});
/*jQuery(document).ready(function(){
  const $opener = jQuery('.block-contact-header .title');
  const $dropDown = jQuery('.block-contact-header .contact-content');
  $opener.click(function (e) {
    $dropDown.toggleClass('active');
  });
});*/












