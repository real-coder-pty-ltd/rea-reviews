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
	<h2>RC Reviews Settings</h2>

	<?php
	$url_first = 'https://api.realestate.com.au/customer-profile/v1/ratings-reviews/agencies/' . get_option( 'rcreviews_agency_id' ) . '?since=2010-09-06T12%3A27%3A00.1Z&order=DESC';
	function delete_all_reviews() {
		$args = array(
			'post_type'      => 'rcreviews',
			'posts_per_page' => -1,
			'fields'         => 'ids', 
		);
	
		$reviews = get_posts($args);
	
		foreach ($reviews as $review_id) {
			wp_delete_post($review_id, true);
		}
	}

	function fetch_links( $url, $counter ) {
		$url_next = '';
		$message = '';
		$message_2 = '';
		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'GET' );

		$headers   = array();
		$headers[] = 'Accept: application/hal+json';
		$headers[] = 'Authorization: Bearer ' . get_option( 'rcreviews_access_token' );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );

		$result = curl_exec( $ch );
		if ( curl_errno( $ch ) ) {
			echo 'Error:' . curl_error( $ch );
		}
		curl_close( $ch );

		// Now you can process the $result
		$data = json_decode( $result, true );

		if ( isset( $data['totalCount'] ) && empty( $data['totalCount'] ) ){
			$message = '<p>No reviews found either due to listing is empty or incorrect agency ID.</p>';
		} else {
			$rating = 0;
			$role =	'Seller';
			$name =	'Seller';
			$created_date = '';
			$content = '';
			$agent_id = 0;
			$agent_name = '';
			$listing_id = 0;
			$unique_id = 0;
			$message = '<p>Total reviews found: ' . $data['totalCount'] . '</p>';

			if ( isset( $_GET['action'] ) && $_GET['action'] == 'sync' ) {

				update_option( 'rcreviews_last_import', date('d F Y H:i:s') );

				foreach ( $data['result'] as $review ) {
					if ( isset( $review['rating'] )){
						$rating = $review['rating'];
					}
					if ( isset( $review['reviewer']['role'] )){
						$role = ucfirst( $review['reviewer']['role'] );
					}
					if ( isset( $review['reviewer']['name'] )){
						$name = ucfirst( $review['reviewer']['name'] );
					}
					if ( isset( $review['createdDate'] )){
						$created_date = $review['createdDate'];
						$created_date_as_post_id = strtotime($review['createdDate']);
					}
					if ( isset( $review['content'] )){
						$content = $review['content'];
					}
					if ( isset( $review['agent']['profileId'] )){
						$agent_id = $review['agent']['profileId'];
					}
					if ( isset( $review['agent']['name'] )){
						$agent_name = $review['agent']['name'];
					}
					if ( isset( $review['listing']['id'] )){
						$listing_id = $review['listing']['id'];
					}
					$unique_id = $listing_id . '-' . $agent_id . '-' . $created_date_as_post_id;

					// Insert post
					$new_post = array(
						'post_title' => $role . ' of house',
						'post_content' => $content,
						'post_status' => 'publish',
						'post_author' => 1,
						'post_date' => $created_date,
						'post_type' => 'rcreviews',
						'meta_input'   => array(
							'rcreview_reviewer_rating' => $rating,
							'rcreview_reviewer_role' => $role,
							'rcreview_reviewer_name' => $name,
							'rcreview_agent_id' => $agent_id,
							'rcreview_agent_name' => $agent_name,
							'rcreview_listing_id' => $listing_id,
							'rcreview_unique_id' => $unique_id,
						),
					);

					$args_by_unique_id = array(
						'post_type'  => 'rcreviews',
						'meta_query' => array(
							array(
								'key'   => 'rcreview_unique_id',
								'value' => $unique_id,
							)
						)
					);

					// Insert post
					$posts = get_posts( $args_by_unique_id );

					if (!empty($posts)) {
						$new_post['ID'] = $posts[0]->ID;
						wp_update_post($new_post);

						// echo $counter . ' - ' . $listing_id;
						// echo '<hr>';
					} else{
						wp_insert_post($new_post);
					}
				}

				$url_next = $data['_links']['next']['href'];
	
				if ( 5 > $counter ){	//For testing and limitting purposes
					if ( isset( $url_next ) && $url_next ){
						fetch_links( $url_next, ++$counter );
					}
				}

				$message_2 .= '<p>Syncing of reviews is in progress.</p>';
			}
		}
		$message .= '<p>Last Import: ' . get_option('rcreviews_last_import') . '</p>';
		$message .= $message_2;

		return $message;
	}

	echo fetch_links( $url_first , 0 );

	if ( isset( $_GET['action'] ) && $_GET['action'] == 'empty' ) {
		delete_all_reviews();
		echo '<p>All reviews have been deleted</p>';
	}
	?>

	<form action="" method="GET">
		<input type="hidden" name="page" value="rcreviews">
		<button type="submit" name="action" id="submit" class="button button-primary" value="sync">Sync Reviews</button>
		<button type="submit" name="action" id="empty" class="button button-primary" value="empty">Empty Reviews</button>
	</form>
	
</div>

<script type="text/javascript">
    document.getElementById('empty').onclick = function() {
        return confirm('Are you sure you want to empty all reviews?');
    };
</script>
