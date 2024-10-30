jQuery(document).ready(function ($) {
	$(".lgs-clear-cache").on( "click", function() {
		var data = {
			'action': 'lgs_clear_cache',
		};
		// We can also pass the url value separately from ajaxurl for front end AJAX implementations
		jQuery.post(ajax_object.ajax_url, data, function (response) {
			if(response=='lgs cache cleared') {
				$(".lgs-clear-cache").text('LGS Cache Cleared!');
			}
		});
	});
});