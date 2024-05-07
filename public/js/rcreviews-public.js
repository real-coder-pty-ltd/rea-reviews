jQuery( document ).ready(function($) {
	'use strict';

	var $icon_down  = '<svg class="CK__Icon--medium" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" width="16" height="16"><path fill-rule="evenodd" clip-rule="evenodd" d="M1.146 4.646a.5.5 0 0 0 0 .708l6.5 6.5a.5.5 0 0 0 .708 0l6.5-6.5a.5.5 0 0 0-.708-.708L8 10.793 1.854 4.646a.5.5 0 0 0-.708 0Z" fill="#00639E"></path></svg>';

	function truncateContent() {
		$('.rcreviews--content').each(function() {
			var $this = $(this);
			var lineHeight = parseInt($this.css('line-height'), 10);
			var lines = Math.ceil($this[0].scrollHeight / lineHeight);
			
			if ( 2 < lines && !$this.hasClass('rcreviews--has-truncate') ){
				$this.addClass('rcreviews--has-truncate');
				$this.addClass('rcreviews--truncate');
				$this.after('<div class="rcreviews--read-more-wrapper"><span class="rcreviews--read-more">Read More ' + $icon_down + '</span></div>');
			}
		});
	}
	
	truncateContent();

	$('.rcreviews--btn').on('click', function() {
		$('.rcreviews--hidden-review').each(function() {
			$(this).toggleClass('d-none');
		});
		
		truncateContent();

		var label = $('.rcreviews--label');
		var count = $('.rcreviews--count');
		label.toggleClass('active');
		count.toggleClass('d-none');
	
		if (label.hasClass('active')) {
			label.text('Show less');
		} else {
			label.text('Show');
		}
	});

	$(document).on('click', '.rcreviews--read-more', function() {
		$(this).parent().prev().removeClass('rcreviews--truncate');
		$(this).parent().remove();
		console.log('click');
	});

});


