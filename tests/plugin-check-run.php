<?php
register_shutdown_function( function () { $e = error_get_last(); if ( $e ) { file_put_contents( '/out/err.txt', print_r( $e, true ) ); } } );
// Runs the official Plugin Check checks against storylane-repx inside Playground
// and writes a plain-text report to /out/plugin-check.txt.
require '/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

use WordPress\Plugin_Check\Checker\Abstract_Check_Runner;
use WordPress\Plugin_Check\Checker\Default_Check_Repository;
use WordPress\Plugin_Check\Checker\Check_Repository;

$mode = getenv( 'PC_MODE' ) ?: 'static';
$out  = '';

$runner = new class() extends Abstract_Check_Runner {
	public static function is_plugin_check() { return true; }
	protected function get_plugin_param() { return 'storylane-repx/storylane-repx.php'; }
	protected function get_check_slugs_param() { return array(); }
	protected function get_check_exclude_slugs_param() { return array(); }
	protected function get_include_experimental_param() { return false; }
	protected function get_categories_param() { return array(); }
	protected function get_slug_param() { return 'storylane-repx'; }
	protected function get_mode_param() { return 'new'; }
	protected function allow_runtime_checks(): bool { return false; }
};

$repo   = new Default_Check_Repository();
$static = array_keys( $repo->get_checks( Check_Repository::TYPE_STATIC )->to_map() );
if ( ! empty( $GLOBALS['PC_SLUGS'] ) ) { $static = $GLOBALS['PC_SLUGS']; }
$runner->set_check_slugs( $static );
$runner->set_plugin( 'storylane-repx/storylane-repx.php' );
$runner->set_slug( 'storylane-repx' );
$runner->set_mode( 'new' );
$runner->set_experimental_flag( false );

$cleanup = $runner->prepare();
$result  = $runner->run();
$cleanup();

$out .= 'Static checks run: ' . implode( ', ', $static ) . "\n\n";
$out .= 'Errors: ' . $result->get_error_count() . '  Warnings: ' . $result->get_warning_count() . "\n\n";
foreach ( array( 'ERROR' => $result->get_errors(), 'WARNING' => $result->get_warnings() ) as $label => $files ) {
	foreach ( $files as $file => $lines ) {
		foreach ( $lines as $line => $cols ) {
			foreach ( $cols as $col => $msgs ) {
				foreach ( $msgs as $m ) {
					$out .= sprintf( "%s %s:%s:%s [%s] %s\n", $label, $file, $line, $col, $m['code'], wp_strip_all_tags( $m['message'] ) );
				}
			}
		}
	}
}
file_put_contents( '/out/plugin-check' . ( $GLOBALS['PC_SUFFIX'] ?? '' ) . '.txt', $out );
