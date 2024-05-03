<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://realcoder.com.au
 * @since      1.0.0
 *
 * @package    Rcreviews
 * @subpackage Rcreviews/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->

<div class="wrap">
	<h1>RC Reviews Sync</h1>
	<h2>Sync or Empty Reviews</h2>
	<p>Please do not close the browser window while the reviews are being processed.</p>
	<hr>

	<?php

	$args = array(
		'post_type'      => 'rcreviews',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
	);

	$query       = new WP_Query( $args );
	$total_posts = $query->found_posts;

	$access_token = get_option( 'rcreviews_access_token' );
	$agency_id    = get_option( 'rcreviews_agency_id' );
	$last_import  = get_option( 'rcreviews_last_import' );

	$minimum_star_rating = get_option( 'rcreviews_minimum_star_rating' );
	$numbers = '';

	if ($minimum_star_rating){
		for ($i = $minimum_star_rating; $i <= 5; $i++) {
			$numbers .= $i . ',';
		}
		$minimum_star_rating = '&ratings=' . rtrim($numbers, ',');
	} else {
		$minimum_star_rating = '';
	}
	$url          = 'https://api.realestate.com.au/customer-profile/v1/ratings-reviews/agencies/' . $agency_id . '?since=2010-09-06T12%3A27%3A00.1Z&order=DESC' . $minimum_star_rating;

	$ch = curl_init();

	curl_setopt( $ch, CURLOPT_URL, $url );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'GET' );

	$headers   = array();
	$headers[] = 'Accept: application/hal+json';
	$headers[] = 'Authorization: Bearer ' . $access_token;

	curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
	$result = curl_exec( $ch );

	if ( curl_errno( $ch ) ) {
		echo 'Error:' . curl_error( $ch );
	}

	curl_close( $ch );

	$data = json_decode( $result, true );

	// echo '<pre>';
	// print_r( $data );
	// echo '</pre>';
	?>

	<?php if ( get_option( 'rcreviews_access_token' ) == '' ): ?>
		<p class="rcreviews-error">Please check if client credentials are correct in the settings page.</p>
	<?php elseif ( get_option( 'rcreviews_agency_id' ) == '' ): ?>
		<p class="rcreviews-error">Please enter the agency ID in the settings page.</p>
	<?php elseif ( isset( $data['totalCount'] ) && empty( $data['totalCount'] ) ): ?>
		<p class="rcreviews-error">No reviews found either due to listing is empty or incorrect agency ID.</p>
	<?php else: ?>
		<p class="rcreviews-total-posts">Total Existing Posts: <span class="rcreviews-posts"><?php echo $total_posts; ?></span></p>
		<p class="rcreviews-total-reviews">Total Reviews Found: <span class="rcreviews-reviews"><?php echo $data['totalCount']; ?></span></p>
		<p class="rcreviews-processed-wrapper d-none">Total Processed Items: <span class="rcreviews-processed">0</span>/<span class="rcreviews-total"><?php echo $data['totalCount']; ?></span></p>
		<div class="rcreviews-progress-wrapper d-none"><progress id="rcreviews-progress" value="0" max="<?php echo ceil( $data['totalCount'] / 10 ); ?>"></progress></div>
		<p class="rcreviews-last-import-wrapper">Last Import: <span class="rcreviews-last-import"><?php echo $last_import; ?></span></p>
		<div class="button-container">
			<button type="submit" id="rcreviews-submit" class="button button-primary">Sync Reviews</button>
			<button type="submit" id="rcreviews-empty" class="button button-primary">Empty Reviews</button>
		</div>
	<?php endif; ?>
</div>