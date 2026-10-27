<?php
/**
 * Remove plugin data on uninstall.
 *
 * @package StorylaneRepX
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'storylane_repx_settings' );
