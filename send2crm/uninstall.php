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


// Permission check
if (!current_user_can('activate_plugins'))
{
    wp_die('You don\'t have proper authorization to delete a plugin!');
}

send2crm_delete_options();

/**
 * Delete all wp_options records that start with 'send2crm_'.
 *
 * @since    1.0.0
 */
function send2crm_delete_options(): void
{
    foreach ( wp_load_alloptions() as $option => $value ) {
        if ( strpos( $option, 'send2crm_' ) === 0 ) {
            delete_option( $option );
        }
    }

}
