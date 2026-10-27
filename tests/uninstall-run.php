<?php
// Test 1 (activation, no notices) and Test 4 (uninstall removes option). Report: /out/uninstall.txt
require '/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/misc.php';
$o = '';
// Copy (not mount) so the real delete flow cannot touch the source files.
$src = '/plugin-src'; $dst = WP_PLUGIN_DIR . '/storylane-repx';
mkdir( $dst );
foreach ( scandir( $src ) as $f ) { if ( is_file( "$src/$f" ) ) { copy( "$src/$f", "$dst/$f" ); } }
$o .= 'WP_DEBUG=' . var_export( WP_DEBUG, true ) . ' WP ' . get_bloginfo( 'version' ) . ' PHP ' . PHP_VERSION . "\n";
ob_start();
$r = activate_plugin( 'storylane-repx/storylane-repx.php' );
$buf = ob_get_clean();
$o .= 'activate_plugin: ' . ( is_wp_error( $r ) ? $r->get_error_message() : 'ok' ) . "\n";
$o .= 'output during activation: [' . $buf . "]\n";
$o .= 'is_plugin_active: ' . var_export( is_plugin_active( 'storylane-repx/storylane-repx.php' ), true ) . "\n";
$o .= 'last error: ' . var_export( error_get_last(), true ) . "\n";
update_option( 'storylane_repx_settings', array( 'id' => 'mbtx79fn0yum', 'location' => 'footer', 'enabled' => 1 ) );
$o .= 'option before uninstall: ' . wp_json_encode( get_option( 'storylane_repx_settings' ) ) . "\n";
deactivate_plugins( 'storylane-repx/storylane-repx.php' );
$d = delete_plugins( array( 'storylane-repx/storylane-repx.php' ) );
$o .= 'delete_plugins: ' . var_export( $d, true ) . "\n";
wp_cache_flush();
$o .= 'option after uninstall: ' . var_export( get_option( 'storylane_repx_settings', 'OPTION_GONE' ), true ) . "\n";
file_put_contents( '/out/uninstall.txt', $o );
