jQuery( function () {
	var $ = jQuery;

	var source = $('.header-toplinks');
	var dest = $('.menu-creator-pro');

	var menu = $('<li class="mcpdropdown parent level0 desktop-hidden"></li>');

	var title = $('<a href="#" target="_self"></a>');
	title.html(source.find('.title').html());
	title.click(function (event) {
		event.preventDefault();
		dest.removeClass('active');
		$(event.target).closest('li').find('div').addClass('active');
	});
	menu.append(title);

	var items = $('<div></div>');
	var backBtn = $('<a title="back" class="nav-header mobile">&lt; Back</a>');
	backBtn.click(function (event) {
		event.preventDefault();
		$(event.target).closest('div').removeClass('active');
		dest.addClass('active');
	});
	items.append(backBtn);

	source.find('a').each(function (index, value) {
		var $value = $(value);
		var item = $('<a class="hav-header"></a>');
		item.attr('href', $value.attr('href'));
		item.attr('title', $value.attr('title'));
		item.html($value.html());
		items.append(item);
	});

	menu.append(items);

	dest.append(menu);
});
