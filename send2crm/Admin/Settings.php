<?php

declare(strict_types=1);

namespace Send2CRM\Admin;

// If this file is called directly, abort.
if (!defined('ABSPATH')) exit;

class Settings {
        /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     */
    private string $pluginSlug;

    /**
     * The slug name for the Settings menu.
     * Should be unique for this menu page and only include
     * lowercase alphanumeric, dashes, and underscores characters to be compatible with sanitize_key().
     *
     * @since    1.0.0
     */
    private string $menuSlug;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    $pluginSlug       The name of this plugin.
     */
    public function __construct(string $pluginSlug)
    {
        error_log('Initializing Settings'); //TODO Remove Debug statements
        $this->pluginSlug = $pluginSlug;
        $this->menuSlug = $pluginSlug;
    }

        /**
     * Register all the hooks of this class.
     *
     * @since    1.0.0
     * @param   $isAdmin    Whether the current request is for an administrative interface page.
     */
    public function initializeHooks(bool $isAdmin): void
    {
        error_log('Settings Hooks'); //TODO Remove Debug statements
        if ($isAdmin) {
            error_log('Initializing Settings Hooks for Admin Page');
            // Hook into admin_init 
            add_action('admin_init', array($this,'send2crm_settings_init'));
            // Hook into admin_menu to add our page 
            add_action('admin_menu', array($this,'send2crm_settings_menu'));
        }
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
}