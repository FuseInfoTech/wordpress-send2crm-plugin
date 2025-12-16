<?php

/**
 * Fired when the plugin is uninstalled by clicking the delete plugin button.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * @since      1.0.0
 *
 * @package    Send2CRM
 */

declare(strict_types=1);

namespace Send2CRM;

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN'))
{
    exit;
}

define('SEND2CRM_UPLOAD_FOLDERNAME', '/send2crm-releases/');
define('SEND2CRM_OPTION_PREFIX', 'send2crm_');

// Permission check
if (!current_user_can('activate_plugins'))
{
    wp_die('You don\'t have proper authorization to delete a plugin!');
}

send2crm_delete_options();
send2crm_delete_releases();

/**
 * Delete all wp_options records that start with 'send2crm_'.
 *
 * @since    1.0.0
 */
function send2crm_delete_options(): void
{
    foreach ( wp_load_alloptions() as $option => $value ) {
        if ( is_string( $option ) && str_starts_with( $option, SEND2CRM_OPTION_PREFIX)) {
            delete_option( $option );
        }
    }
}

function send2crm_delete_releases() : void 
{
    $upload_dir = wp_upload_dir();
    $releases_dir = $upload_dir['basedir'] . SEND2CRM_UPLOAD_FOLDERNAME;
    $success = remove_folder($releases_dir);
    
}
/**
 * Delete a directory and all of its contents using WordPress Filesystem API.
 *
 * @since    1.0.0
 * 
 * @param    string    $dir    The plugin directory or upload subdirectory path.
 * @return   bool      True on success, false on failure.
 */
function remove_folder( $dir ) : bool {
    // Safety check: Ensure we have a valid directory path
    if ( empty( $dir ) || ! is_string( $dir ) ) {
        return false;
    }

    // Normalize the path
    $dir = untrailingslashit( $dir );
    
    // CRITICAL SAFETY CHECKS - Prevent accidental deletion of WordPress core directories
    $wp_upload_dir = wp_upload_dir();
    $upload_basedir = untrailingslashit( $wp_upload_dir['basedir'] );
    
    $protected_paths = array(
        ABSPATH,                           // WordPress root
        WP_CONTENT_DIR,                    // wp-content directory
        WP_PLUGIN_DIR,                     // plugins directory
        get_theme_root(),                  // themes directory
        $upload_basedir,                   // uploads BASE directory (root)
        ABSPATH . 'wp-admin',              // wp-admin directory
        ABSPATH . 'wp-includes',           // wp-includes directory
    );

    // Check if trying to delete a protected directory
    foreach ( $protected_paths as $protected ) {
        $protected = untrailingslashit( $protected );
        if ( $dir === $protected ) {
            return false;
        }
    }

    // Verify the path is within WP_PLUGIN_DIR OR a subdirectory of uploads
    $plugin_dir = untrailingslashit( WP_PLUGIN_DIR );
    $is_in_plugin_dir = ( strpos( $dir, $plugin_dir ) === 0 );
    $is_in_uploads_subdir = ( strpos( $dir, $upload_basedir ) === 0 && $dir !== $upload_basedir );
    
    if ( ! $is_in_plugin_dir && ! $is_in_uploads_subdir ) {
        return false;
    }

    // Check if directory exists
    if ( ! is_dir( $dir ) ) {
        return false;
    }

    // Initialize WordPress filesystem
    global $wp_filesystem;
    
    if ( ! function_exists( 'WP_Filesystem' ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    
    if ( ! WP_Filesystem() ) {
        return false;
    }

    // Use WordPress Filesystem API to remove directory recursively
    return $wp_filesystem->rmdir( $dir, true );
}
