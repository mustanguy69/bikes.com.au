var $j = jQuery.noConflict();
$j(document).ready(function(){
	$j(".prd_count").css({"background": "#fff", "border": "0", "color":"#555", "float":"left"});

$j(document).on("click", "tr.pointer,.checkbox" , function() {
     var count = ($j(".checkbox:checked").length) - ($j(".checkbox:first:checked").length);
    $j(".prd-cnt").text(count);
        });
});