<?php
include "/wordpress/wp-load.php";
$expected_slug = 'send2crm';
$expected_plugins = array (
    0 => 'plugin-check',
    1 => 'playground-review-helper',
);
$installed_plugins = array_diff(
    array_map( "basename", glob( WP_PLUGIN_DIR . "/*", GLOB_ONLYDIR ) ),
    $expected_plugins
);
if ( 1 === count( $installed_plugins) ) {
    $plugin_dir = reset( $installed_plugins );
    if ( $plugin_dir !== $expected_slug ) {
        if ( rename( WP_PLUGIN_DIR . "/" . $plugin_dir, WP_PLUGIN_DIR . "/" . $expected_slug ) ) {
            $active_plugins = get_option( "active_plugins" );
            foreach ( $active_plugins as &$active_plugin ) {
                if ( 0 === strpos( $active_plugin, $plugin_dir ) ) {
                    $active_plugin = $expected_slug . substr( $active_plugin, strlen( $plugin_dir ) );
                }
            }
            update_option( "active_plugins", $active_plugins );
        }
    }
}
    