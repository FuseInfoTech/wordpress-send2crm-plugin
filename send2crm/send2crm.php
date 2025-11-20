<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 * 
 * @package   Plugin_Name
 * @author    FuseIT  support@fuseit.com
 * @copyright 2025 Fuse Information Technologies Ltd
 * @license   GPL v2 or later
 * @link      https://fuseit.com
 * 
 * Plugin Name: Send2CRM
 * Plugin URI:      @TODO
 * Description:     @TODO
 * Version: 0.0.1
 * Author: FuseIT
 * Author URI: https://fuseit.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires PHP:    8.3
 */

// In strict mode, only a variable of exact type of the type declaration will be accepted.
declare(strict_types=1);

namespace Send2CRM;

// If this file is called directly, abort.
if ( !defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

define( 'NAME', 'Send2CRM' );
define( 'PLUGIN_ROOT', plugin_dir_path( __FILE__ ) );
define( 'PLUGIN_ABSOLUTE', __FILE__ );

//TODO Add uninstall.php for removing plugin
public class Send2CRM {

    public function __construct() {

        error_log('Initializing Send2CRM Plugin');
        // Hook into admin_init 
        add_action('admin_init', array($this,'send2crm_settings_init'));
        // Hook into admin_menu to add our page 
        add_action('admin_menu', array($this,'send2crm_settings_menu'));
        //Hook Send2CRM snippet as script tag in header of public site only and not admin pages


        add_action('wp_head', array($this,'send2crm_insert_snippet'));

    }

    public function send2crm_settings_init() {
        error_log('Creating Send2CRM Settings');
 
        // Register the setting
        //TODO make the settings use an array to avoid pollution the wp_options table with many settings
        register_setting('send2crm_settings', 'send2crm_api_key');
        register_setting('send2crm_settings', 'send2crm_api_domain'); 
        register_setting('send2crm_settings', 'send2crm_js_location');

        // Add the settings section
        add_settings_section(
            'send2crm_settings_section',
            'Required Settings',
            array($this,'send2crm_settings_section'),
            'send2crm'
        );

        // Add the api key setting field
        add_settings_field(
            'send2crm_api_key',
            'Send2CRM API Key',
            array($this,'send2crm_api_key_callback'),
            'send2crm',
            'send2crm_settings_section'
        );

                // Add the api domain settings field
        add_settings_field(
            'send2crm_api_domain',
            'Send2CRM API Domain',
            array($this,'send2crm_api_domain_callback'),
            'send2crm',
            'send2crm_settings_section'
        );

        // Add the js location settings field
        add_settings_field(
            'send2crm_js_location',
            'Send2CRM JS Location',
            array($this,'send2crm_js_location_callback'),
            'send2crm',
            'send2crm_settings_section'
        );
    }

    public function send2crm_settings_menu() {
        
        error_log('Adding Send2CRM Menu');
        // Add a new menu page 
        add_options_page( 'Send2CRM Settings', // Page title 
        'Send2CRM', // Menu title 
        'manage_options', // Capability required 
        'send2crm', // Menu slug 
        array($this,'send2crm_settings_page'), // Callback function 
        99 // Position 
        );
    }

    public function send2crm_settings_page() { 
        error_log('Displaying Setting Page from Callback');
        ?> 
        <div class="wrap"> 
            <h1>My Plugin Settings</h1> 
            <form method="post" action="options.php"> 
                <?php 
                    // Output security fields 
                    settings_fields('send2crm_settings'); 
                    // Output sections and fields 
                    do_settings_sections('send2crm'); 
                    // Output save button 
                    submit_button(); 
                ?> 
            </form> 
        </div> 
        <?php 
    }

    public function send2crm_settings_section() {
        error_log('Send2CRM Settings Section');
        echo '<p>The following settings are required for Send2CRM to function. The Send2CRM snippet will not be included until they are added.</p>';
    }

    public function send2crm_api_key_callback() {
        error_log('Send2CRM API Key');
        // Get the current saved value 
        $value = get_option('send2crm_api_key'); 
        // Output the input field 
        echo "<input type='text' name='send2crm_api_key' value='$value'>";
        echo "<p class='description'>Enter the shared API key configured for your service in Salesforce.</p>";
    }

    public function send2crm_api_domain_callback() {
        error_log('Send2CRM API Domain');
        // Get the current saved value 
        $value = get_option('send2crm_api_domain');
        // Output the input field 
        echo "<input type='text' name='send2crm_api_domain' value='$value'>";
        echo "<p class='description'>Enter the domain where the Send2CRM service is hosted, in the case of the Salesforce package this will be the public site configured for Send2CRM endpoints.</p>";
    }

    public function send2crm_js_location_callback() {
        error_log('Send2CRM JS Location');
        // Get the current saved value 
        $value = get_option('send2crm_js_location');
        if (empty($value)) {
            $value = "https://cdn.jsdelivr.net/gh/FuseInfoTech/send2crmjs/send2crm.min.js";
        }
        // Output the input field 
        echo "<input type='text' name='send2crm_js_location' value='$value'>";
        echo "<p class='description'>Enter the location of the Send2CRM JavaScript file.</p>  Default is <i>'https://cdn.jsdelivr.net/gh/FuseInfoTech/send2crmjs/send2crm.min.js'</i>";
    }

    public function send2crm_insert_snippet() {
        if (current_user_can('manage_options')) {
            error_log('Not inserting Send2CRM Snippet in admin page');
            return;
        }
        error_log('Inserting Send2CRM Snippet');
        $jsLocation = get_option('send2crm_js_location');
        $apiKey = get_option('send2crm_api_key');
        $apiDomain = get_option('send2crm_api_domain');

        if (empty($jsLocation) || empty($apiKey) || empty($apiDomain)) {
            error_log('Send2CRM is activated but not correctly configured. Please use `/wp-admin/admin.php?page=send2crm` to add required settings.');
            return;
        }
        echo "<script>(function(s,e,n,d2,cr,m){n[e]=n[e]||{};m=document.createElement('script');m.onload=function(){n[e].init(d2,cr);};m.src=s;document.head.appendChild(m);})('$jsLocation', 'send2crm', window, '$apiDomain', '$apiKey');</script>";
    }

}

//Start the plugin
new Send2CRM();