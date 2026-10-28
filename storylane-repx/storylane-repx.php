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
define( 'STORYLANE_REPX_ABOUT_URL', 'https://www.storylane.io/repx' );
define( 'STORYLANE_REPX_SIGNUP_URL', 'https://app.storylane.io/signup' );
define( 'STORYLANE_REPX_APP_URL', 'https://app.storylane.io/repx-dashboard/settings/embed' );

/**
 * Extract the RepX ID from the RepX script tag.
 *
 * Only the full script is accepted: it must load js.storylane.io/js/v1/repx.js and carry a data-id,
 * either as an attribute or as ?data-id= in the script address.
 *
 * @param string $input Raw user input.
 * @return string Valid lowercase ID, or an empty string if the input is not a RepX script.
 */
function storylane_repx_extract_id( $input ) {
	$input = trim( (string) $input );
	if ( false === stripos( $input, 'js.storylane.io/js/v1/repx.js' ) ) {
		return '';
	}
	// Either form: data-id="..." as an attribute, or repx.js?data-id=... in the script address.
	if ( preg_match( '/\sdata-id\s*=\s*(["\'])(.*?)\1/i', $input, $matches ) ) {
		return storylane_repx_validate_id( $matches[2] );
	}
	if ( preg_match( '/repx\.js\?(?:[^"\'\s>]*&(?:amp;)?)?data-id=([^&"\'\s>]*)/i', $input, $matches ) ) {
		return storylane_repx_validate_id( $matches[1] );
	}
	return '';
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
		$action = isset( $input['action'] ) ? (string) $input['action'] : 'save';
		if ( '' === $id || 'off' === $action ) {
			$enabled = 0;
		} elseif ( 'on' === $action || '' === $old['id'] ) {
			// Turn on RepX, or first setup: saving a script turns RepX on.
			$enabled = 1;
		} else {
			// Save changes keeps the current on or off state.
			$enabled = $old['enabled'];
		}
		return array(
			'id'      => $id,
			'enabled' => $enabled,
		);
	}

	if ( isset( $input['id'] ) ) {
		// When the option is first created, WordPress runs this callback a second time on the
		// already-sanitized array, which holds 'id' instead of 'script'.
		$id = storylane_repx_validate_id( $input['id'] );
		return array(
			'id'      => $id,
			'enabled' => ( '' !== $id && ! empty( $input['enabled'] ) ) ? 1 : 0,
		);
	}

	return array(
		'id'      => '',
		'enabled' => 0,
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
 * Load the settings page styles, only on the RepX settings page.
 *
 * @param string $hook Current admin page hook.
 */
function storylane_repx_admin_assets( $hook ) {
	if ( 'settings_page_storylane-repx' !== $hook ) {
		return;
	}
	wp_enqueue_style(
		'storylane-repx-admin',
		plugins_url( 'assets/admin.css', __FILE__ ),
		array(),
		STORYLANE_REPX_VERSION
	);
}
add_action( 'admin_enqueue_scripts', 'storylane_repx_admin_assets' );

/**
 * Small "opens in a new tab" icon.
 *
 * @return string SVG markup.
 */
function storylane_repx_external_icon() {
	return '<svg class="slx-ext" width="16" height="16" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M6 3H3.5A1.5 1.5 0 0 0 2 4.5v8A1.5 1.5 0 0 0 3.5 14h8a1.5 1.5 0 0 0 1.5-1.5V10M9 2h5v5M14 2 7.5 8.5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
}

/**
 * Render the settings page.
 */
function storylane_repx_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$settings = storylane_repx_get_settings();
	$name     = STORYLANE_REPX_OPTION;
	$has_id   = '' !== $settings['id'];
	$live     = $has_id && $settings['enabled'];
	$new_tab  = '<span class="screen-reader-text">' . esc_html__( '(opens in a new tab)', 'storylane-repx' ) . '</span>';
	?>
	<div class="wrap slx">
		<header class="slx-header">
			<h1 class="slx-title">
				<img src="<?php echo esc_url( plugins_url( 'assets/repx-logo.svg', __FILE__ ) ); ?>" alt="" width="66" height="32" />
				<span class="screen-reader-text"><?php esc_html_e( 'Storylane RepX', 'storylane-repx' ); ?></span>
			</h1>
			<p class="slx-lede">
				<?php
				printf(
					/* translators: %s: link to the RepX product page. */
					esc_html__( '%s is an AI sales agent from Storylane. It answers buyer questions, qualifies leads and books demos on your site.', 'storylane-repx' ),
					'<a href="' . esc_url( STORYLANE_REPX_ABOUT_URL ) . '" target="_blank" rel="noopener noreferrer">RepX' . $new_tab . '</a>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts above.
				);
				?>
			</p>
		</header>
		<hr class="wp-header-end" />

		<form method="post" action="options.php" class="slx-form">
			<?php settings_fields( 'storylane_repx' ); ?>
			<div class="slx-card">
			<ol class="slx-steps">
				<li class="slx-step">
					<span class="slx-step-num" aria-hidden="true">01</span>
					<div class="slx-step-body">
						<h2 class="slx-step-title"><?php esc_html_e( 'Create your RepX agent', 'storylane-repx' ); ?></h2>
						<p class="slx-step-text"><?php esc_html_e( 'Sign up for RepX and set up your agent in Storylane.', 'storylane-repx' ); ?></p>
						<p class="slx-actions">
							<a class="slx-btn slx-btn-outline" href="<?php echo esc_url( STORYLANE_REPX_SIGNUP_URL ); ?>" target="_blank" rel="noopener noreferrer">
								<?php esc_html_e( 'Sign up for RepX', 'storylane-repx' ); ?>
								<?php echo storylane_repx_external_icon() . $new_tab; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup. ?>
							</a>
						</p>
					</div>
				</li>
				<li class="slx-step">
					<span class="slx-step-num" aria-hidden="true">02</span>
					<div class="slx-step-body">
						<h2 class="slx-step-title"><?php esc_html_e( 'Add RepX to your site', 'storylane-repx' ); ?></h2>
						<p class="slx-step-text">
							<?php esc_html_e( 'Copy your RepX script from Storylane and paste it below.', 'storylane-repx' ); ?>
							<a class="slx-link" href="<?php echo esc_url( STORYLANE_REPX_APP_URL ); ?>" target="_blank" rel="noopener noreferrer">
								<?php esc_html_e( 'Get your script', 'storylane-repx' ); ?>
								<?php echo storylane_repx_external_icon() . $new_tab; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup. ?>
							</a>
						</p>
						<label class="slx-label" for="storylane-repx-script"><?php esc_html_e( 'RepX script', 'storylane-repx' ); ?></label>
						<textarea id="storylane-repx-script" class="slx-input" name="<?php echo esc_attr( $name ); ?>[script]" rows="3" spellcheck="false" placeholder="<?php echo esc_attr( storylane_repx_script_tag( 'yourrepxid' ) ); ?>"><?php echo esc_textarea( storylane_repx_script_tag( $settings['id'] ) ); ?></textarea>
					</div>
				</li>
			</ol>
				<div class="slx-actionbar">
					<div class="slx-status-row">
						<span class="slx-status <?php echo esc_attr( $live ? 'is-live' : ( $has_id ? 'is-off' : 'is-empty' ) ); ?>" id="storylane-repx-status">
							<span class="slx-dot" aria-hidden="true"></span>
							<?php
							if ( $live ) {
								esc_html_e( 'RepX is live on this site', 'storylane-repx' );
							} elseif ( $has_id ) {
								esc_html_e( 'RepX is off', 'storylane-repx' );
							} else {
								esc_html_e( 'RepX is not set up yet', 'storylane-repx' );
							}
							?>
						</span>
						<?php if ( $live ) : ?>
							<span class="slx-sep" aria-hidden="true">·</span>
							<button type="submit" class="slx-link-btn" name="<?php echo esc_attr( $name ); ?>[action]" value="off"><?php esc_html_e( 'Turn off', 'storylane-repx' ); ?><span class="screen-reader-text"> <?php esc_html_e( 'RepX', 'storylane-repx' ); ?></span></button>
						<?php elseif ( $has_id ) : ?>
							<span class="slx-sep" aria-hidden="true">·</span>
							<button type="submit" class="slx-link-btn" name="<?php echo esc_attr( $name ); ?>[action]" value="on"><?php esc_html_e( 'Turn on', 'storylane-repx' ); ?><span class="screen-reader-text"> <?php esc_html_e( 'RepX', 'storylane-repx' ); ?></span></button>
						<?php endif; ?>
					</div>
					<button type="submit" class="slx-btn slx-btn-primary" id="submit" name="<?php echo esc_attr( $name ); ?>[action]" value="save"><?php esc_html_e( 'Save changes', 'storylane-repx' ); ?></button>
				</div>
			</div>
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
