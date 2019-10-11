jQuery(function($){
        $('.menu-holder').on("hover",function() {
            $('.lazy').lazy({
                bind: "event",
                delay: 0
            });
        });
		$('.belazy').lazy({visibleOnly:true});
		$('.gridlazy').lazy();
});