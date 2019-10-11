jQuery(document).ready(function(){
	const $bodyTag = jQuery('body');
	const $btnClose = jQuery('.btn-close');
	const $btnOpener = jQuery('.menu-opener');
	const $mobileMenu = jQuery('#menu-group-1');
	const $menuButton = jQuery('#menu-group-1 a');
	$btnOpener.click(function (e) {
		$bodyTag.addClass('menu-opened');
		$mobileMenu.addClass('open');
		e.preventDefault();
	});
	$btnClose.click(function (e) {
		$bodyTag.removeClass('menu-opened');
		$mobileMenu.removeClass('open');
		e.preventDefault();
	});
	$mobileMenu.click(function (e) {
		if (jQuery(e.target).is('#menu-group-1')) {
			$bodyTag.removeClass('menu-opened');
			$mobileMenu.removeClass('open');
		}
	});
	if ($mobileMenu.css('position') == 'fixed') {
		$menuButton.click(function (e) {
			if (jQuery(this).next().is('*') && !jQuery(this).hasClass('btn-back')) {
				jQuery(this.parentNode.parentNode).removeClass('active');
				jQuery(this).nextAll().addClass('active');
				e.preventDefault();
			}
			if (jQuery(this).hasClass('btn-back')) {
				if (jQuery(this.parentNode.parentNode.parentNode.parentNode).hasClass('level0')) {
					jQuery(this.parentNode.parentNode).removeClass('active');
					jQuery(this.parentNode.parentNode.parentNode.parentNode).addClass('active');
				}
				else {
					jQuery(this.parentNode.parentNode.parentNode).removeClass('active');
					jQuery(this.parentNode.parentNode.parentNode.parentNode.parentNode).addClass('active');
				}
				e.preventDefault();
			}
		});
	}
});