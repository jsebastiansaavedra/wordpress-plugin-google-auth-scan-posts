<?php
/**
 * Posts Maintenance block.
 *
 * @link          https://wpmudev.com/
 * @since         1.0.0
 *
 * @author        Juan Sebastian Saavedra Alvarez
 * @package       WPMUDEV\PluginTest
 *
 * @copyright     2023, Incsub (http://incsub.com)
 */

namespace WPMUDEV\PluginTest\App\Admin_Pages;

// Abort if called directly.
defined( 'WPINC' ) || die;

use WPMUDEV\PluginTest\Base;
use WPMUDEV\PluginTest\CLI\ScanPostsCommand;
use WP_CLI;

class PostsMaintenance extends Base {

	/**
	 * Initializes the page.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function init() {
		add_action( 'admin_menu', [ $this, 'register_admin_page' ] );
		add_action( 'admin_init', [ $this, 'handle_scan_posts' ] );
		add_action( 'wp', [ $this, 'schedule_daily_scan' ] );
		add_action( 'daily_scan_posts', [ $this, 'daily_scan_posts' ] );

		// Register the custom WP-CLI command.
		WP_CLI::add_command( 'scan-posts', new ScanPostsCommand() );
	}

	public function register_admin_page() {
		// Use 'admin_menu' hook to register the admin page.
		add_menu_page(
			'Posts Maintenance', // Page title
			'Posts Maintenance', // Menu title
			'manage_options', // Capability required
			'wpmudev_plugintest_posts_maintenance', // Menu slug
			[ $this, 'callback' ], // Callback function to display page content
			'dashicons-hammer', // Icon
			6 // Menu position
		);
	}

	/**
	 * The admin page callback method.
	 *
	 * @return void
	 */
	public function callback() {
		$this->view();
	}

	/**
	 * Prints the wrapper element which React will use as root.
	 *
	 * @return void
	 */
	protected function view() {
		echo '<div class="wrap">';
		echo '<h1>Posts Maintenance</h1>';
		echo '<form method="post">';
		echo '<input type="hidden" name="scan_posts" value="1">';
		echo '<input type="submit" class="button button-primary" value="Scan Posts">';
		echo '</form>';
		echo '</div>';

		if ( get_settings_errors( 'wpmudev_scan_posts' ) ) {
			settings_errors( 'wpmudev_scan_posts' );
		}
	}

	public function handle_scan_posts() {
		$post_types = [ 'post', 'page' ];

		foreach ( $post_types as $post_type ) {
			$args = [
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => -1, // Retrieve all posts of the specified type.
			];

			$posts = get_posts( $args );

			if ( $posts ) {
				foreach ( $posts as $post ) {
					update_post_meta( $post->ID, 'wpmudev_test_last_scan', current_time( 'timestamp' ) );
				}
			}
		}

		add_settings_error( 'wpmudev_scan_posts', 'scan_posts_success', __( 'Posts scanned successfully!', 'wpmudev-plugin-test' ), 'updated' );
	}

	// Schedule the daily scan.
	public function schedule_daily_scan() {
		if ( ! wp_next_scheduled( 'daily_scan_posts' ) ) {
			wp_schedule_event( time(), 'daily', 'daily_scan_posts' );
		}
	}

	// Hook to run the daily scan.
	public function daily_scan_posts() {
		$this->handle_scan_posts(); // Call the handle_scan_posts method.
	}
}