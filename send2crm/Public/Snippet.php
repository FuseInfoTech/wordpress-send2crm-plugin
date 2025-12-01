<?php

declare(strict_types=1);

namespace Send2CRM\Public;

use Send2CRM\Admin\Settings;

// If this file is called directly, abort.
if (!defined('ABSPATH')) exit;

#region Constants
define('SNIPPET_FILENAME', 'js/standard-snippet.js');
#endregion
/**
 * The frontend functionality of the plugin.
 *
 * Defines the public facing hooks for inserting the Send2CRM Javascript snippet.
 *
 * @since      1.0.0
 *
 * @package    Send2CRM
 * @subpackage Send2CRM/Public
 */
class Snippet {

    /** 
     * Instance holding Send2CRM settings so that they can be accessed when hooks are executed
     * 
     * @since 1.0.0
     */
    private Settings $settings;


    /** 
     * Plugin version for registering and enqueuing the send2crm javascript snippet.
     * 
     * @since 1.0.0
     */
    private string $version;


    public function __construct(Settings $settings, string $version) {
        error_log('Initializing Public facing Send2CRM Plugin'); //TODO Remove Debug statements
        $this->settings = $settings;
        $this->version = $version;
        
        $this->settings->add_group('settings', array($this,'sanitize_and_validate_settings'));
        $this->settings->add_section('settings', 'Required Settings', array($this, 'send2crm_settings_section'));
    
    }

    #region Settings API Callbacks
    public function sanitize_and_validate_settings(array | null $settings) : array {
        error_log('Sanitize and Validate Settings :' . serialize($settings)); //TODO Remove Debug statements
        //TODO get the current settings and use those as a starting point to stop clearing settings when they aren't included in the form
        $input = $settings ?? array();
        $output = array();

        foreach ($input as $key => $value) {
            $sanitizedOutput = sanitize_text_field($value);
            $output[$key] = $this->validate_setting($key,$sanitizedOutput); //TODO Should we do validation on the front end to provide a better user expereience?
        }
        return $output;
    }

    public function validate_setting(string $key, string $value): string {
        error_log('Validate Setting: ' . $value); //TODO Remove Debug statements
        //check if text input is valid otherwise return the current option value
        if (is_numeric($value)) {
            add_settings_error($key, $this->pluginSlug . '-message', 'Setting should not be a number. Please enter a valid value.', 'error');
            return $this->getSetting($key);
        }
        return $value;
    }

    /**
     * Register all the hooks of this class.
     *
     * @since    1.0.0
     * @param   $isAdmin    Whether the current request is for an administrative interface page.
    */

    /**
     * Callback for displaying the required Settings section.
     * 
     * @since   1.0.0
     */
    public function send2crm_settings_section(): void {
        error_log('Send2CRM Settings Section');
        echo '<p>The following settings are required for Send2CRM to function. The Send2CRM snippet will not be included until they are added.</p>';

    }
    #endregion

    #region Public Functions

    public function initializeHooks(bool $isAdmin): void
    {
        if ($isAdmin) {
            error_log('Skipping Snippet Hooks for Admin Page'); //TODO Remove Debug statements;
            return;
        }
        error_log('Add Snippet Action Hook'); //TODO Remove Debug statements  
        //Hook Send2CRM snippet as script tag in header of public site only and not admin pages
        add_action('wp_enqueue_scripts', array($this,'insertSnippet'));
        
    }



    /**
     * Callback for inserting the Send2CRM snippet in the header section of the public facing site.
     * 
     * @since   1.0.0
     */
    public function insertSnippet() {
        error_log('Inserting Send2CRM Snippet');
        $jsLocation = $this->settings->getSetting('send2crm_js_location');
        $apiKey = $this->settings->getSetting('send2crm_api_key');
        $apiDomain = $this->settings->getSetting('send2crm_api_domain');

        if (empty($jsLocation) || empty($apiKey) || empty($apiDomain)) {
            error_log('Send2CRM is activated but not correctly configured. Please use `/wp-admin/admin.php?page=send2crm` to add required settings.');
            return;
        }
        $snippetUrl =  plugin_dir_url( __FILE__ ) . SNIPPET_FILENAME;
        $snippetId = "{$this->settings->pluginSlug}-snippet";
        
        if (wp_register_script( $snippetId, $snippetUrl, array(), $this->version, false ) === false)
        {
            error_log('Snippet could not be registered - Send2CRM will not be activated.');
            return;
        } 

        $snippetData = array(
            'api_key' => $apiKey,
            'api_domain' => $apiDomain,
            'js_location' => $jsLocation . "?ver={$this->version}"
        );
        wp_enqueue_script($snippetId, $snippetUrl, array(), $this->version, false);
        error_log('Snippet enqueued at' . $snippetUrl);
        wp_localize_script( $snippetId, 'snippetData', $snippetData); 
    }

    #endregion

}