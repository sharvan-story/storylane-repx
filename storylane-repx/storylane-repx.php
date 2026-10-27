<?php
/**
 * Plugin Name:       Storylane RepX
 * Description:       Adds the Storylane RepX AI sales agent to your website with one embed ID.
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
 * Extract a RepX ID from a full embed snippet or a bare ID.
 *
 * @param string $input Raw user input.
 * @return string Valid lowercase ID, or an empty string if none is valid.
 */
function storylane_repx_extract_id( $input ) {
	$input = trim( (string) $input );
	if ( preg_match( '/data-id\s*=\s*(["\'])(.*?)\1/i', $input, $matches ) ) {
		$candidate = $matches[2];
	} else {
		$candidate = $input;
	}
	$candidate = strtolower( trim( $candidate ) );
	return preg_match( '/^[a-z0-9]{6,64}$/', $candidate ) ? $candidate : '';
}

/**
 * Get saved settings merged with defaults.
 *
 * @return array{id:string,location:string,enabled:int}
 */
function storylane_repx_get_settings() {
	$saved = get_option( STORYLANE_REPX_OPTION, array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	return wp_parse_args(
		$saved,
		array(
			'id'       => '',
			'location' => 'footer',
			'enabled'  => 0,
		)
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
	// Raw input is only used to find an ID; what gets stored is validated against ^[a-z0-9]{6,64}$.
	// The form sends 'embed'. When the option is first created, WordPress runs this callback a
	// second time on the already-sanitized array, which has 'id' instead, so accept that too.
	if ( isset( $input['embed'] ) ) {
		$raw = trim( (string) $input['embed'] );
	} elseif ( isset( $input['id'] ) ) {
		$raw = trim( (string) $input['id'] );
	} else {
		$raw = '';
	}

	$id = '';
	if ( '' !== trim( $raw ) ) {
		$id = storylane_repx_extract_id( $raw );
		if ( '' === $id ) {
			if ( ! $error_added ) {
				add_settings_error(
					'storylane_repx_messages',
					'storylane_repx_invalid_id',
					esc_html__( 'No valid RepX ID found. Paste the full embed code or just the ID (6 to 64 letters and numbers). Your previous settings were kept.', 'storylane-repx' ),
					'error'
				);
				$error_added = true;
			}
			return $old;
		}
	}

	$location = ( isset( $input['location'] ) && 'header' === $input['location'] ) ? 'header' : 'footer';

	return array(
		'id'       => $id,
		'location' => $location,
		'enabled'  => empty( $input['enabled'] ) ? 0 : 1,
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
				'id'       => '',
				'location' => 'footer',
				'enabled'  => 0,
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
		<p>
			<?php esc_html_e( 'Where to find your embed code: open your RepX settings in Storylane, go to the install or embed section, and copy the script tag. You can paste the whole tag or only the ID from data-id.', 'storylane-repx' ); ?>
		</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'storylane_repx' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="storylane-repx-embed"><?php esc_html_e( 'RepX embed code or ID', 'storylane-repx' ); ?></label></th>
					<td>
						<textarea id="storylane-repx-embed" name="<?php echo esc_attr( $name ); ?>[embed]" rows="3" class="large-text code" placeholder="<?php echo esc_attr( 'mbtx79fn0yum' ); ?>"><?php echo esc_textarea( $settings['id'] ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Only the ID is saved.', 'storylane-repx' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Location', 'storylane-repx' ); ?></th>
					<td>
						<fieldset>
							<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[location]" value="footer" <?php checked( $settings['location'], 'footer' ); ?> /> <?php esc_html_e( 'Footer (recommended)', 'storylane-repx' ); ?></label><br />
							<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[location]" value="header" <?php checked( $settings['location'], 'header' ); ?> /> <?php esc_html_e( 'Header', 'storylane-repx' ); ?></label>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable RepX', 'storylane-repx' ); ?></th>
					<td>
						<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[enabled]" value="1" <?php checked( (int) $settings['enabled'], 1 ); ?> /> <?php esc_html_e( 'Show RepX on my site', 'storylane-repx' ); ?></label>
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
	if ( empty( $settings['enabled'] ) || '' === storylane_repx_extract_id( $settings['id'] ) ) {
		return;
	}
	wp_enqueue_script(
		STORYLANE_REPX_HANDLE,
		STORYLANE_REPX_SCRIPT_URL,
		array(),
		null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- URL must stay exactly as given.
		array(
			'in_footer' => 'header' !== $settings['location'],
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
		$attributes['data-id']     = storylane_repx_extract_id( $settings['id'] );
		$attributes['data-source'] = 'wordpress';
	}
	return $attributes;
}
add_filter( 'wp_script_attributes', 'storylane_repx_script_attributes' );
