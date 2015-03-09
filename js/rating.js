jQuery(document).ready(function($){

	// Star rating
	var update_rating_display = function( el, value ){
		if(typeof value === "undefined"){
			value = $(el).attr('data-value');
		}
		var width = value / 5 * 100;
		$(el).find('.progress').css('width', width+'%');
	};

	$('.br-stars:not(.visible)')
		.text('')
		.addClass('visible')
		.prepend('<div class="progress"></div>')
		.each(function(){
			if('false' === $(this).attr('data-readonly')){
				$(this)
					.append('<span></span>')
					.append('<span></span>')
					.append('<span></span>')
					.append('<span></span>')
					.append('<span></span>');
			}
			update_rating_display(this);
		});

	$('.br-stars.visible').each(function(){
		var holder = this;

		$(holder).find('span')
		.click(function(){
			$(holder).attr('data-value', $(this).index());
			update_rating_display(holder);

			var field = $(holder).attr('data-field');
			$(field).val($(this).index());
		})
		.hover(
			function(){
				update_rating_display(holder, $(this).index());
			},
			function(){
				update_rating_display(holder);
			}
		);

	});
});