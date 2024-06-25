<?php
/**
 * Google oAuth Shortcode.
 *
 * @link          https://wpmudev.com/
 * @since         1.0.0
 *
 * @author        Juan Sebastian Saavedra Alvarez
 * @package       WPMUDEV\PluginTest
 *
 * @copyright     2023, Incsub (http://incsub.com)
 */

namespace WPMUDEV\PluginTest\Shortcodes;

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
							console.log(data); // Debug
							if (data.error) {
								document.getElementById('login-result').textContent = 'Error: ' + data.error;
								return;
							}

							const clientId = data.client_id;
							const redirectUri = esc_js(rest_url('your_namespace/v1/auth/confirm'));

							const authUrl = `https://accounts.google.com/o/oauth2/auth?client_id=${clientId}&redirect_uri=${redirectUri}&response_type=code&scope=email&state=${state}&login_hint=${encodeURIComponent(email)}`;

							console.log('Redirecting to:', authUrl); // Debug
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

add_shortcode( 'google_oauth', 'WPMUDEV\PluginTest\Shortcodes\google_oauth_shortcode' );

