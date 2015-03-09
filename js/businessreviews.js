(function($){
	var masonry_exists = false;
	
	var filter_location = function(){

		var show_location;
		

		if( '' !== window.location.hash ){
			show_location = window.location.hash.match(new RegExp('#(.*)'))[1];
		}
		else {
			show_location = 'all';
		}
		
		if( show_location === 'all' ){
			$('.br-review').show();
		}
		else {
			$( '.br-review' ).hide();
			$( '.br-review.br-location-'+show_location ).show();
		}

		gridify();
	};

	var update_location = function(e){
		if( typeof e !== 'undefined' ) { 
			e.preventDefault();
			// If we get this on document ready, we will not have an element to refer
			window.location.hash = $(this).attr('href');
		}
	};

	var gridify = function(){
		if( masonry_exists ){
			$('.br-wrapper').masonry('destroy');
		}

		$('.br-wrapper').masonry({
			itemSelector: '.br-review'
		});
		masonry_exists = true;
	};

	$(document).ready(function(){

		$('.timeago').timeago();
		$('.br-comment').readmore({
			afterToggle: function(){
				gridify();
			}
		});

		filter_location();
		$('#br-location-filter .br-filter a').click(update_location);


		$('#submit-review').fancybox({
			href: br.ajax_url+'?action=review_form'
		});

		$('.inputmask').inputmask();
	});

	$(window).on('hashchange', function(){
	    filter_location();
	});
})(jQuery);