<?php
/**
 * Plugin Name:       WPMU DEV Plugin Test
 * Description:       A plugin focused on testing coding skills.
 * Requires at least: 6.1
 * Requires PHP:      7.4
 * Version:           0.1.0
 * Author:            Juan Sebastian Saavedra Alvarez
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpmudev-plugin-test
 *
 * @package           create-block
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Support for site-level autoloading.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}


// Plugin version.
if ( ! defined( 'WPMUDEV_PLUGINTEST_VERSION' ) ) {
	define( 'WPMUDEV_PLUGINTEST_VERSION', '1.0.0' );
}

// Define WPMUDEV_PLUGINTEST_PLUGIN_FILE.
if ( ! defined( 'WPMUDEV_PLUGINTEST_PLUGIN_FILE' ) ) {
	define( 'WPMUDEV_PLUGINTEST_PLUGIN_FILE', __FILE__ );
}

// Plugin directory.
if ( ! defined( 'WPMUDEV_PLUGINTEST_DIR' ) ) {
	define( 'WPMUDEV_PLUGINTEST_DIR', plugin_dir_path( __FILE__ ) );
}

// Plugin url.
if ( ! defined( 'WPMUDEV_PLUGINTEST_URL' ) ) {
	define( 'WPMUDEV_PLUGINTEST_URL', plugin_dir_url( __FILE__ ) );
}

// Assets url.
if ( ! defined( 'WPMUDEV_PLUGINTEST_ASSETS_URL' ) ) {
	define( 'WPMUDEV_PLUGINTEST_ASSETS_URL', WPMUDEV_PLUGINTEST_URL . '/assets' );
}

// Shared UI Version.
if ( ! defined( 'WPMUDEV_PLUGINTEST_SUI_VERSION' ) ) {
	define( 'WPMUDEV_PLUGINTEST_SUI_VERSION', '2.12.23' );
}


/**
 * WPMUDEV_PluginTest class.
 */
class WPMUDEV_PluginTest {

	/**
	 * Holds the class instance.
	 *
	 * @var WPMUDEV_PluginTest $instance
	 */
	private static $instance = null;

	/**
	 * Return an instance of the class
	 *
	 * Return an instance of the WPMUDEV_PluginTest Class.
	 *
	 * @return WPMUDEV_PluginTest class instance.
	 * @since 1.0.0
	 *
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Class initializer.
	 */
	public function load() {
		load_plugin_textdomain(
			'wpmudev-plugin-test',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);

		WPMUDEV\PluginTest\Loader::instance();
	}
}

// Init the plugin and load the plugin instance for the first time.
add_action(
	'plugins_loaded',
	function () {
		WPMUDEV_PluginTest::get_instance()->load();
	}
);


function google_oauth_shortcode() {
    echo 'Shortcode executed';
    if ( is_user_logged_in() ) {
        // User is logged in, display personalized message
        $current_user = wp_get_current_user();
        return 'Welcome, ' . esc_html( $current_user->user_login );
    } else {
        // User is not logged in, display Google OAuth login link
        ob_start();
        ?>
			<form id="google-oauth-form">
				<label for="email">Email:</label>
				<input type="email" id="email" name="email" required>
				<input type="submit" value="Login with Google">
			</form>
			<div id="login-result"></div>
			<script>
				document.getElementById('google-oauth-form').addEventListener('submit', function(event) {
					event.preventDefault();

					const client_email = document.getElementById('email').value;
					const state = generateStateParameter();

					localStorage.setItem('oauth_state', state);
					localStorage.setItem('oauth_email', email);

					fetch('<?php echo rest_url('/wpmudev/v1/auth/auth-url'); ?>', {
							method: 'GET',
							headers: {
								'Content-Type': 'application/json'
							}
						}).then(response => response.json())
						.then(data => {
							if (data.error) {
								document.getElementById('login-result').textContent = 'Error: ' + data.error;
								return;
							}

							const clientId = data.client_id;
							const redirectUri = '/wpmudev/v1/auth/confirm';

							const authUrl = `https://accounts.google.com/o/oauth2/auth?client_id=${clientId}&redirect_uri=${redirectUri}&response_type=code&scope=email&state=${state}&login_hint=${encodeURIComponent(email)}`;

							window.location.href = authUrl;
						})
						.catch(error => {
							console.error('Error:', error);
							document.getElementById('login-result').textContent = 'Error fetching client ID.';
						});
				});

				function generateStateParameter() {
					return Math.random().toString(36).substring(2);
				}

				function handleOAuthCallback() {
					const params = new URLSearchParams(window.location.search);
					const code = params.get('code');
					const state = params.get('state');

					if (code && state) {
						const storedState = localStorage.getItem('oauth_state');
						const email = localStorage.getItem('oauth_email');

						if (state === storedState) {
							fetch('<?php echo rest_url('/wpmudev/v1/auth/confirm'); ?>', {
								method: 'GET',
								headers: {
									'Content-Type': 'application/json'
								},
								body: JSON.stringify({
									code: code,
									state: state,
									email: email
								})
							})
							.then(response => response.json())
							.then(data => {
								if (data.error) {
									document.getElementById('login-result').textContent = 'Error: ' + data.error;
								} else {
									window.location.href = '<?php echo home_url(); ?>';
								}
							})
							.catch(error => {
								console.error('Error:', error);
							});
						} else {
							document.getElementById('login-result').textContent = 'Error: State parameter mismatch';
						}
					}
				}

				document.addEventListener('DOMContentLoaded', handleOAuthCallback);
			</script>
        <?php
        return ob_get_clean();
    }
}

add_shortcode( 'google_oauth', 'google_oauth_shortcode' );