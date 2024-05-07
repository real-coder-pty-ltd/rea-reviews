jQuery( document ).ready(function($) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	var $icon_down  = '<svg class="CK__Icon--medium" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" width="16" height="16"><path fill-rule="evenodd" clip-rule="evenodd" d="M1.146 4.646a.5.5 0 0 0 0 .708l6.5 6.5a.5.5 0 0 0 .708 0l6.5-6.5a.5.5 0 0 0-.708-.708L8 10.793 1.854 4.646a.5.5 0 0 0-.708 0Z" fill="#00639E"></path></svg>';

	$('.rcreviews-content').each(function() {
		var $this = $(this);
		var lineHeight = parseInt($this.css('line-height'), 10);
		var lines = Math.ceil($this[0].scrollHeight / lineHeight);
		
		if ( 2 < lines ){
			$this.addClass('truncate');
			$this.after('<div class="read-more-wrapper"><span class="read-more">Read More ' + $icon_down + '</span></div>');
		}
	});

	$('.read-more').on('click', function() {
		$(this).parent().prev().removeClass('truncate');
		$(this).remove();
	});

});


