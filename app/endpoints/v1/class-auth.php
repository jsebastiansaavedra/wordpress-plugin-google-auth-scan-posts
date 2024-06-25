<?php
/**
 * Google Auth Shortcode.
 *
 * @link          https://wpmudev.com/
 * @since         1.0.0
 *
 * @author        WPMUDEV (https://wpmudev.com)
 * @package       WPMUDEV\PluginTest
 *
 * @copyright     2023, Incsub (http://incsub.com)
 */

namespace WPMUDEV\PluginTest\Endpoints\V1;

// Abort if called directly.
defined( 'WPINC' ) || die;

use WPMUDEV\PluginTest\Endpoint;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WPMUDEV\PluginTest\Core\Google_Auth\Auth as GoogleAuthAuth;

class Auth extends Endpoint {

	/**
	 * API endpoint for the current endpoint.
	 *
	 * @since 1.0.0
	 *
	 * @var string $endpoint
	 */
	protected $endpoint = 'auth/auth-url';
	protected $endpoint_confirm = 'auth/confirm';

	/**
	 * Register the routes for handling auth functionality.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function register_routes() {
		// TODO
		// Add a new Route to logout.

		// Route to get auth url.
		register_rest_route(
			$this->get_namespace(),
			$this->endpoint,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_credentials' ],
					'permission_callback' => [ $this, 'edit_permission' ],
				],
			]
		);

		// Route to post auth url.
		register_rest_route(
			$this->get_namespace(),
			$this->endpoint,
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'save_credentials' ],
					'permission_callback' => [ $this, 'edit_permission' ],
					'args'                => [
						'client_id'     => [
							'required'    => true,
							'description' => __( 'The client ID from Google API project.', 'wpmudev-plugin-test' ),
							'type'        => 'string',
						],
						'client_secret' => [
							'required'    => true,
							'description' => __( 'The client secret from Google API project.', 'wpmudev-plugin-test' ),
							'type'        => 'string',
						],
					],
				],
			]
		);

		// Register the confirm callback route.
		register_rest_route(
			$this->get_namespace(),
			$this->endpoint_confirm,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'confirm_callback' ],
					'permission_callback' => [ $this, 'edit_permission' ],
				],
			]
		);
	}

	/**
	 * Save the client ID and secret.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 * @since 1.0.0
	 */
	public function save_credentials( WP_REST_Request $request ) {
		$client_id     = sanitize_text_field( $request->get_param( 'client_id' ) );
		$client_secret = sanitize_text_field( $request->get_param( 'client_secret' ) );

		if ( empty( $client_id ) || empty( $client_secret ) ) {
			return new WP_Error( 'missing_credentials', __( 'Client ID and Secret are required.', 'wpmudev-plugin-test' ), [ 'status' => 400 ] );
		}

		$credentials = [
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
		];

		update_option( 'wpmudev_plugin_test_settings', $credentials );

		return $this->get_response( [ 'message' => 'Credentials saved successfully.' ] );
	}

	/**
	 * Check user permissions.
	 *
	 * @return bool
	 */
	public function edit_permission( $request ) {
		return true;
	}

	/**
	 * Handle the OAuth confirmation callback.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response
	 */
	public function confirm_callback( WP_REST_Request $request ) {
		// Check if the request contains the necessary parameters from Google OAuth callback.
		$code  = $request->get_param( 'code' );
		$state = $request->get_param( 'state' );
		$email = $request->get_param( 'email' );

		if (empty($code) || empty($state) || empty($email)) {
			return new WP_REST_Response(
				['error' => 'Invalid OAuth callback parameters'],
				400
			);
		}

		$stored_state = get_transient('oauth_state_' . $email);
		if ($state !== $stored_state) {
			return new WP_REST_Response(
				['error' => 'Invalid state parameter'],
				400
			);
		}

		// Retrieve stored credentials from options.
		$credentials = get_option( 'wpmudev_plugin_test_settings' );
		if ( empty( $credentials ) || ! isset( $credentials['client_id'] ) || ! isset( $credentials['client_secret'] ) ) {
			return new WP_REST_Response(
				['error' => 'Google OAuth credentials not set',],
				400
			);
		}

		// Initialize Google client with stored credentials.
		$client = GoogleAuthAuth::instance()->set_up( $credentials['client_id'], $credentials['client_secret'], $email);

		// Exchange authorization code for access token.
		$token = $client->fetchAccessTokenWithAuthCode( $code );

		if ( ! isset( $token['access_token'] ) ) {
			return new WP_REST_Response(
				[
					'error' => 'Failed to retrieve access token',
				],
				400
			);
		}

		// Use access token to fetch user info from Google API.
		$oauth2    = new Google_Service_Oauth2( $client );
		$user_info = $oauth2->userinfo->get();

		$user_email = $user_info->getEmail();

		if ( $user_email !== $email ) {
			return new WP_REST_Response(
				[
					'error' => 'Email mismatch',
				],
				400
			);
		}

		$user = get_user_by( 'email', $user_email );

		if ( $user ) {
			// User exists, log in.
			wp_set_current_user( $user->ID );
			wp_set_auth_cookie( $user->ID );
			do_action( 'wp_login', $user->user_login, $user );
		} else {
			// User doesn't exist, create a new user.
			$username = $user_info->getGivenName(); 
			$password = wp_generate_password( 12 );
			$new_user_id = wp_create_user( $username, $password, $user_email );

			if ( ! is_wp_error( $new_user_id ) ) {
				// User created successfully, log in.
				wp_set_current_user( $new_user_id );
				wp_set_auth_cookie( $new_user_id );
				do_action( 'wp_login', $username, get_userdata( $new_user_id ) );
			}
		}

		delete_transient('oauth_state_' . $email);
		// Redirect the user to the appropriate page after authentication.
		wp_redirect( home_url() );
		exit;
	}

	/**
	 * Get the stored credentials.
	 *
	 * @return WP_REST_Response
	 */
	public function get_credentials() {
		$stored_credentials = GoogleAuthAuth::instance()->get_settings();

		// Check if credentials exist.
		if ( ! empty( $stored_credentials ) ) {
			// Credentials found, return them.
			return new WP_REST_Response( $stored_credentials, 200 );
		} else {
			// No credentials found, return error response.
			return new WP_REST_Response(
				[
					'error' => 'No credentials found',
				],
				404
			);
		}
	}
}
