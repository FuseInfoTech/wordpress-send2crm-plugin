<?php

declare(strict_types=1);

namespace Send2CRM\Public;

use Send2CRM\Admin\Settings;

// If this file is called directly, abort.
if (!defined('ABSPATH')) exit;

#region Constants
define('SNIPPET_FILENAME', 'js/standard-snippet.js');
define('ADDITIONAL_SETTINGS_FILENAME', 'js/additional-settings.js');
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
        

        //Create the required settings as the default settings group, section.
        $this->settings->add_group('settings', array($this,'sanitize_and_validate_settings'));
        $this->settings->add_section('settings', 'Salesforce Access', array($this, 'send2crm_settings_section'));
        $this->settings->add_field('send2crm_api_key', 'Send2CRM API Key', array($this, 'send2crm_api_key_callback'));
        $this->settings->add_field('send2crm_api_domain', 'Send2CRM API Domain', array($this, 'send2crm_api_domain_callback'));
        $this->settings->add_field('send2crm_js_location', 'Send2CRM JS Location', array($this, 'send2crm_js_location_callback'));

        //Create additional settings groups and sections
        $customizeTabName = 'custom_tab';
        $customizeGroupName = 'customize';
        $this->settings->add_group($customizeGroupName, array($this,'sanitize_and_validate_settings'), $customizeTabName, 'Customize');

        //Create section for logging settings such as debug messages
        $this->settings->add_section('logging', 'Detailed Logging', array($this, 'logging_section'), $customizeTabName,);
        $this->settings->add_field('debug_enabled', 'Enable Detailed Log Messages', array($this, 'debug_enabled_callback'), 'logging', $customizeTabName, $customizeGroupName);

        $this->settings->add_section('advanced', 'Advanced', array($this, 'send2crm_settings_section'), $customizeTabName,);
        
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
     * Callback for displaying the text input field.
     * 
     * @since   1.0.0
     * @param   string  $fieldId        The ID of the field.
     * @param   string  $description    The description of the field. If provided the description will be displayed below the form input.
     */
    private function render_text_input(string $fieldId, string $description = ''): void {
        error_log($fieldId);
        // Get the current saved value 
        $optionGroup = $this->settings->get_field($fieldId)['option_group'];
        $value = $this->settings->getSetting($fieldId,$optionGroup); 
        $settingName = $this->settings->getSettingName($fieldId,$optionGroup);
        // Render the input field 
        echo "<input type='text' id=$fieldId' name=$settingName value='$value'>";
        if ($description != '') {
            echo "<p class='description'>$description</p>";
        }
    }

    /**
     * Callback for displaying the API key setting.
     * 
     * @since   1.0.0
     */
    public function send2crm_api_key_callback(): void {
        $fieldId = 'send2crm_api_key';
        $this->render_text_input($fieldId, 'Enter the shared API key configured for your service in Salesforce.');
    }

    /**
     * Callback for displaying the API domain setting.
     * 
     * @since   1.0.0
     */
    public function send2crm_api_domain_callback(): void {
        $fieldId = 'send2crm_api_domain';
        $this->render_text_input($fieldId, 'Enter the domain where the Send2CRM service is hosted, in the case of the Salesforce package this will be the public site configured for Send2CRM endpoints.');
    }

    /**
     * Callback for displaying the JavaScript location setting.
     * 
     * @since   1.0.0
     */
    public function send2crm_js_location_callback(): void {
        $fieldId = 'send2crm_js_location';
        $this->render_text_input($fieldId, 'Enter the location of the Send2CRM JavaScript file.');
    }

    public function debug_enabled_callback(): void {
        $fieldId = 'debug_enabled';
        $this->render_text_input($fieldId, 'If true, then Send2CRM will output detailed messages to the browser console.');
    }


    public function render_section(string $description): void {
        echo "<p>$description </p>";
    }
    /**
     * Callback for displaying the required Settings section.
     * 
     * @since   1.0.0
     */
    public function send2crm_settings_section(): void {
        error_log('Send2CRM Settings Section');
        $this->render_section('The following settings are required for Send2CRM to function. The Send2CRM snippet will not be included until they are added.');
    }

    public function logging_section(): void {
        //Describe the Send2CRM settings for controlling logs and debug
        $this->render_section('Settings for controlling logging message output for Send2CRM JavaScript.');
    }


    #endregion

    #region Public Functions
    /**
     * Register all the hooks of this class.
     *
     * @since    1.0.0
     * @param   $isAdmin    Whether the current request is for an administrative interface page.
    */
    public function initializeHooks(bool $isAdmin): void
    {
        if ($isAdmin) {
            error_log('Skipping Snippet Hooks for Admin Page'); //TODO Remove Debug statements;
            return;
        }
        error_log('Add Snippet Action Hook'); //TODO Remove Debug statements  
        //Hook Send2CRM snippet as script tag in header of public site only and not admin pages
        add_action('wp_enqueue_scripts', array($this,'insertSnippet'));
        add_action('wp_enqueue_scripts',array($this,'applyAdditionalSettings'));
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
        

        $snippetJson = json_encode(array(
            'api_key' => $apiKey,
            'api_domain' => $apiDomain,
            'js_location' => $jsLocation . "?ver={$this->version}"
        ));
        wp_enqueue_script($snippetId, $snippetUrl, array(), $this->version, false);
        error_log('Snippet enqueued at' . $snippetUrl);
        //wp_localize_script( $snippetId, 'snippetData', $snippetData); 
        wp_add_inline_script( $snippetId, "const snippetData = {$snippetJson};", 'before');
    }

    public function applyAdditionalSettings() {
        error_log('Apply Additional Settings');
        $debugEnabled = $this->settings->getSetting('debug_enabled');

        if (empty($debugEnabled)) {
            error_log('No Additional Settings found, skipping.');
            return;
        }
        $settingJsUrl =  plugin_dir_url( __FILE__ ) . ADDITIONAL_SETTINGS_FILENAME;
        $settingJsId = "{$this->settings->pluginSlug}-settings";
        if (wp_register_script( $settingJsId, $settingJsUrl, array(), $this->version, false ) === false)
        {
            error_log('Additional Settings Javascript could not be registered - No Additional Settings will be applied.');
            return;
        }

        $settingsJson = json_encode(array(
            'debug' => filter_var($debugEnabled, FILTER_VALIDATE_BOOLEAN),
        ));

        

        wp_enqueue_script($settingJsId, $settingJsUrl, array(), $this->version, false);
        error_log('Additional Settings Javascript enqueued at' . $settingJsUrl);
        
        wp_add_inline_script( $settingJsId, "const additionalSettings = {$settingsJson};", 'before');
    }

    #endregion

}