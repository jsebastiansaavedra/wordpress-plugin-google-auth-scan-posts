<?php
/**
 * Custom WP-CLI command to scan posts.
 */

namespace WPMUDEV\PluginTest\CLI;

use WP_CLI_Command;
use WP_CLI;

class ScanPostsCommand extends WP_CLI_Command {
	/**
	 * Execute the Scan Posts action.
	 *
	 * @param array $args
	 * @param array $flags
	 */
	public function __invoke( $args, $flags ) {
		$post_types = [ 'post', 'page' ];

		foreach ( $post_types as $post_type ) {
			$query_args = [
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => -1, // Retrieve all posts of the specified type.
			];

			$posts = get_posts( $query_args );

			if ( $posts ) {
				foreach ( $posts as $post ) {
					update_post_meta( $post->ID, 'wpmudev_test_last_scan', current_time( 'timestamp' ) );
				}
			}
		}

		WP_CLI::success( 'Posts scanned successfully!' );
	}
}