jQuery(document).ready(function ($) {

    function processReviews(url, page_counter, item_counter) {

        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'rcreviews_process_reviews',
                url: url,
                item_counter: item_counter
            },
            success: function (response) {
                // Increment the counter
                page_counter++;

                // Update the progress bar
                $('#rcreviews-progress').val(page_counter);
                $('.rcreviews-last-import').text(response['last_import']);
                $('.rcreviews-posts').text(response['total_posts']);
                $('.rcreviews-processed').text(response['item_counter']);

                // Call the AJAX function again with the updated counter
                console.log('Current Review Page: ' + page_counter);

                if (response['item_counter'] == $('.rcreviews-reviews').text()) {
                    alert('Sync completed.');
                    $('#rcreviews-submit').removeAttr('disabled');
                    $('#rcreviews-empty').removeAttr('disabled');
                } else {
                    processReviews(response['url_next'], page_counter, response['item_counter']);
                }
            }
        });
    }

    function emptyReviews() {
        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'rcreviews_empty_reviews'
            },
            success: function (response) {
                $('.rcreviews-posts').text(response['total_posts']);
                $('#rcreviews-submit').removeAttr('disabled');
                $('#rcreviews-empty').removeAttr('disabled');
                alert('All reviews have been deleted.');
            }
        });
    }

    $('#rcreviews-submit').click(function (e) {
        e.preventDefault();

        var userConfirmed = confirm("Are you sure you want to proceed?\nPlease do not close the browser window until the process is complete.");
        if (userConfirmed) {
            $('.rcreviews-progress-wrapper').removeClass('d-none');
            $('.rcreviews-processed-wrapper').removeClass('d-none');
            $('#rcreviews-submit').attr('disabled', true);
            $('#rcreviews-empty').attr('disabled', true);
            processReviews(ajax_object.url_first, 0, 0);
        }
    });

    $('#rcreviews-empty').click(function (e) {
        e.preventDefault();

        var userConfirmed = confirm("Are you sure you want to empty the reviews?");
        if (userConfirmed) {
            $('.rcreviews-progress-wrapper').addClass('d-none');
            $('.rcreviews-processed-wrapper').addClass('d-none');
            $('#rcreviews-submit').attr('disabled', true);
            $('#rcreviews-empty').attr('disabled', true);
            emptyReviews();
        }
    });
});