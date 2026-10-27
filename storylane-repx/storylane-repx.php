<?php
/**
 * Plugin Name:       Storylane RepX
 * Description:       Adds the Storylane RepX AI sales agent to your website.
 * Version:           1.0.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            Storylane
 * Author URI:        https://www.storylane.io
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       storylane-repx
 *
 * @package StorylaneRepX
 */

defined( 'ABSPATH' ) || exit;

define( 'STORYLANE_REPX_VERSION', '1.0.0' );
define( 'STORYLANE_REPX_OPTION', 'storylane_repx_settings' );
define( 'STORYLANE_REPX_HANDLE', 'storylane-repx' );
define( 'STORYLANE_REPX_SCRIPT_URL', 'https://js.storylane.io/js/v1/repx.js' );

/**
 * Extract the RepX ID from the RepX script tag.
 *
 * Only the full script is accepted: it must load js.storylane.io/js/v1/repx.js and carry a data-id.
 *
 * @param string $input Raw user input.
 * @return string Valid lowercase ID, or an empty string if the input is not a RepX script.
 */
function storylane_repx_extract_id( $input ) {
	$input = trim( (string) $input );
	if ( false === stripos( $input, 'js.storylane.io/js/v1/repx.js' ) ) {
		return '';
	}
	if ( ! preg_match( '/data-id\s*=\s*(["\'])(.*?)\1/i', $input, $matches ) ) {
		return '';
	}
	return storylane_repx_validate_id( $matches[2] );
}

/**
 * Validate a stored RepX ID.
 *
 * @param string $id Candidate ID.
 * @return string Valid lowercase ID, or an empty string.
 */
function storylane_repx_validate_id( $id ) {
	$id = strtolower( trim( (string) $id ) );
	return preg_match( '/^[a-z0-9]{6,64}$/', $id ) ? $id : '';
}

/**
 * The RepX script tag for a saved ID, shown back in the settings box.
 *
 * @param string $id RepX ID.
 * @return string Script tag, or an empty string when there is no ID.
 */
function storylane_repx_script_tag( $id ) {
	if ( '' === $id ) {
		return '';
	}
	return '<script src="' . STORYLANE_REPX_SCRIPT_URL . '" data-id="' . $id . '" async></script>';
}

/**
 * Get saved settings merged with defaults.
 *
 * @return array{id:string,enabled:int}
 */
function storylane_repx_get_settings() {
	$saved = get_option( STORYLANE_REPX_OPTION, array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	$settings = wp_parse_args(
		$saved,
		array(
			'id'      => '',
			'enabled' => 0,
		)
	);
	return array(
		'id'      => storylane_repx_validate_id( $settings['id'] ),
		'enabled' => empty( $settings['enabled'] ) ? 0 : 1,
	);
}

/**
 * Sanitize callback for the settings option.
 *
 * @param mixed $input Submitted values.
 * @return array Sanitized settings.
 */
function storylane_repx_sanitize_settings( $input ) {
	static $error_added = false;

	$old   = storylane_repx_get_settings();
	$input = is_array( $input ) ? $input : array();

	if ( isset( $input['script'] ) ) {
		// From the form: only the RepX script is accepted. Only the ID inside it is stored.
		$raw = trim( (string) $input['script'] );
		$id  = '' === $raw ? '' : storylane_repx_extract_id( $raw );
		if ( '' !== $raw && '' === $id ) {
			if ( ! $error_added ) {
				add_settings_error(
					'storylane_repx_messages',
					'storylane_repx_invalid_script',
					esc_html__( 'That is not a RepX script. Paste the full script from your Storylane account. Your previous settings were kept.', 'storylane-repx' ),
					'error'
				);
				$error_added = true;
			}
			return $old;
		}
	} elseif ( isset( $input['id'] ) ) {
		// When the option is first created, WordPress runs this callback a second time on the
		// already-sanitized array, which holds 'id' instead of 'script'.
		$id = storylane_repx_validate_id( $input['id'] );
	} else {
		$id = '';
	}

	return array(
		'id'      => $id,
		'enabled' => empty( $input['enabled'] ) ? 0 : 1,
	);
}

/**
 * Register the setting.
 */
function storylane_repx_register_settings() {
	register_setting(
		'storylane_repx',
		STORYLANE_REPX_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'storylane_repx_sanitize_settings',
			'default'           => array(
				'id'      => '',
				'enabled' => 0,
			),
		)
	);
}
add_action( 'admin_init', 'storylane_repx_register_settings' );

/**
 * Add the Settings > RepX page.
 */
function storylane_repx_add_menu() {
	add_options_page(
		__( 'Storylane RepX', 'storylane-repx' ),
		__( 'RepX', 'storylane-repx' ),
		'manage_options',
		'storylane-repx',
		'storylane_repx_render_page'
	);
}
add_action( 'admin_menu', 'storylane_repx_add_menu' );

/**
 * Render the settings page.
 */
function storylane_repx_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$settings = storylane_repx_get_settings();
	$name     = STORYLANE_REPX_OPTION;
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'storylane_repx' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Show RepX', 'storylane-repx' ); ?></th>
					<td>
						<label><input type="checkbox" id="storylane-repx-enabled" name="<?php echo esc_attr( $name ); ?>[enabled]" value="1" <?php checked( (int) $settings['enabled'], 1 ); ?> /> <?php esc_html_e( 'Show RepX on my site', 'storylane-repx' ); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="storylane-repx-script"><?php esc_html_e( 'RepX script', 'storylane-repx' ); ?></label></th>
					<td>
						<textarea id="storylane-repx-script" name="<?php echo esc_attr( $name ); ?>[script]" rows="3" class="large-text code" placeholder="<?php echo esc_attr( storylane_repx_script_tag( 'yourrepxid' ) ); ?>"><?php echo esc_textarea( storylane_repx_script_tag( $settings['id'] ) ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Paste the RepX script from your Storylane account.', 'storylane-repx' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * Add a Settings link on the Plugins screen.
 *
 * @param string[] $links Existing action links.
 * @return string[]
 */
function storylane_repx_action_links( $links ) {
	$url = admin_url( 'options-general.php?page=storylane-repx' );
	array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'storylane-repx' ) . '</a>' );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'storylane_repx_action_links' );

/**
 * Enqueue the RepX script on the front end.
 */
function storylane_repx_enqueue() {
	if ( is_admin() || is_feed() ) {
		return;
	}
	$settings = storylane_repx_get_settings();
	if ( empty( $settings['enabled'] ) || '' === $settings['id'] ) {
		return;
	}
	wp_enqueue_script(
		STORYLANE_REPX_HANDLE,
		STORYLANE_REPX_SCRIPT_URL,
		array(),
		null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- URL must stay exactly as given.
		array(
			'in_footer' => true,
			'strategy'  => 'async',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'storylane_repx_enqueue' );

/**
 * Add data attributes to the RepX script tag.
 *
 * @param array $attributes Script tag attributes.
 * @return array
 */
function storylane_repx_script_attributes( $attributes ) {
	if ( isset( $attributes['id'] ) && STORYLANE_REPX_HANDLE . '-js' === $attributes['id'] ) {
		$settings                 = storylane_repx_get_settings();
		$attributes['data-id']     = $settings['id'];
		$attributes['data-source'] = 'wordpress';
	}
	return $attributes;
}
add_filter( 'wp_script_attributes', 'storylane_repx_script_attributes' );
