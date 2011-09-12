jQuery(function($) {
	$('.GeoLocationField input[type=text]').each(function() {
		$(this).autocomplete({
			source: $(this).attr('rel'),
			minLength: 2,
			select: function( e, ui ) {
				$(this).siblings('input[type=hidden]').val(ui.item.id);
			}
		});
	});
});