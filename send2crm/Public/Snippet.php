<?php

declare(strict_types=1);

namespace Send2CRM\Public;

use Send2CRM\Admin\Settings;
use Send2CRM\Admin\VersionManager;
// If this file is called directly, abort.
if (!defined('ABSPATH')) exit;

#region Constants
define('SEND2CRM_JS_FOLDERNAME', 'js/');
define('SEND2CRM_SNIPPET_FILENAME', SEND2CRM_JS_FOLDERNAME . 'send2crm-setup.js');
define('SEND2CRM_ADDITIONAL_SETTINGS_URL', 'https://fuseit.atlassian.net/wiki/x/E4Dogg');
define('SEND2CRM_CLIENT_CONFIG_URL','https://fuseit.atlassian.net/wiki/x/FYBagw');
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

    #region Constructor
    public function __construct(Settings $settings, string $version) {
        $this->settings = $settings;
        $this->version = $version;
        $clientConfigUrl = SEND2CRM_CLIENT_CONFIG_URL;
        $additionalSettingsUrl = SEND2CRM_ADDITIONAL_SETTINGS_URL;
        //Create the required settings as the default settings group, section.
        $this->settings->add_group('settings', array($this,'sanitize_and_validate_settings'),'default_tab', 'Setup');

        $this->settings->add_section(
            'settings', 
            'Required Configuration', 
            'API credentials needed for Send2CRM to communicate with Salesforce.',
        );
        $this->settings->add_field(
            'api_key',
            'Send2CRM API Key<span style="color: #d63638;">*</span>',
            array($this, 'render_required_text_input'),
            "The shared API key configured for your service in Salesforce. This identifies and authenticates requests from your WordPress site to your CRM.<br/><a href='{$clientConfigUrl}' target='_blank'>Where do I find this?</a>",
            type: 'uuid',
        );
        $this->settings->add_field(
            'api_domain',
            'Send2CRM API Domain<span style="color: #d63638;">*</span>',
            array($this, 'render_required_text_input'),
            "The domain where your Send2CRM service is hosted. This is either your public site configured in Salesforce or the scaling service or proxy end point.<br/><strong>Format:</strong> yourdomain.force.com (without https://)<br/><a href='{$clientConfigUrl}' target='_blank'>What should I enter?</a>",
            type: 'domain',
        );

        //Create additional settings groups and sections
        $customizeTabName = 'custom_tab';
        $customizeGroupName = 'customize';
        $this->settings->add_group(
            $customizeGroupName, 
            array($this,'sanitize_and_validate_settings'), 
            $customizeTabName, 
            'Additional Settings',
        );

        //Create section to hold the additional settings documentation text
        $this->settings->add_section(
            'documentation',
            'Documentation',
            "Configure the JavaScript library to connect your WordPress site with Salesforce CRM. <a href='{$additionalSettingsUrl}' target='_blank'>View Documentation →</a>",
            $customizeTabName,
        );

        //Create section for cookies settings
        $this->settings->add_section(
            'cookies', 
            'Cookies', 
            'Settings related to browser cookies and their behavior.', 
            $customizeTabName,
        );

        //personalizationCookie
        $this->settings->add_field(
            'personalization_cookie',
            'Personalization Cookie', 
            array($this, 'render_text_input'), 
            "Store Visitor Segment values into a 'send2crm' cookie for website back end access.", 
            'cookies', 
            $customizeTabName, 
            $customizeGroupName,
            type: 'checkbox',
        );

        //utmCookie
        $this->settings->add_field(
            'utm_cookie',
            'UTM Cookie', 
            array($this, 'render_text_input'), 
            "Automatically process UTM parameters into a 'send2crmUTM' cookie.", 
            'cookies', 
            $customizeTabName, 
            $customizeGroupName,
            type: 'checkbox',
        );

        //idCookieDomain
        $this->settings->add_field(
            'id_cookie_domain',
            'Visitor ID Cookie Domain',
            array($this, 'render_text_input'),
            'If set, Send2CRM will generate a cookie using this domain and use to store and retrieve the visitor identifier. Useful for syncing visitors across subdomains.',
            'cookies',
            $customizeTabName,
            $customizeGroupName,
            type: 'domain',
        );


        //Create section for form settings
        $this->settings->add_section(
            'form', 
            'Form', 
            'Settings related to the behavior of forms that submit data to Send2CRM.', 
            $customizeTabName,
        );
        //formSelector
        $this->settings->add_field(
            'form_selector',
            'Form Selector', 
            array($this, 'render_text_input'), 
            "Selector for passing to JavaScript querySelector() method, to get forms that should be automatically processed. Must reference <form> elements.", 
            'form', 
            $customizeTabName, 
            $customizeGroupName,
        );

        //maxFileSize
        $this->settings->add_field(
            'max_file_size',
            'Maximum File Size', 
            array($this, 'render_text_input'), 
            "The maximum number of bytes total allowed for file uploads in a single form submission.", 
            'form', 
            $customizeTabName, 
            $customizeGroupName,
            type: 'number',
        );

        //formIdAttributes
        $this->settings->add_field(
            'form_id_attributes',
            'Form Identifier Attribute(s)', 
            array($this, 'render_text_input'), 
            "The attributes of the <form> element that should be used as the form's identifier.", 
            'form', 
            $customizeTabName, 
            $customizeGroupName,
            type: 'csv',
        );

        //formFailMessage
        $this->settings->add_field(
            'form_fail_message',
            'Form Failure Message',
            array($this, 'render_text_input'),
            'Validation message text set when a form submission fails. Empty to disable.',
            'form',
            $customizeTabName,
            $customizeGroupName,
            type: 'textarea',
        );

        //formMinTime
        $this->settings->add_field(
            'form_min_time',
            "Form Minimum Time",
            array($this, 'render_text_input'),
            'Number of seconds before allowing form submission. Only applies to auto-attached forms, 0 to disable.',
            'form',
            $customizeTabName,
            $customizeGroupName,
            type: 'number',
        );

        //formRateCount
        $this->settings->add_field(
            'form_rate_count',
            "Form Submission Limit",
            array($this, 'render_text_input'),
            'Number of form submissions allowed per visitor. 0 to disable limits.',
            'form',
            $customizeTabName,
            $customizeGroupName,
            type: 'number',
        );

        //formRateTime
        $this->settings->add_field(
            'form_rate_time',
            "Form Additional Submission Time",
            array($this, 'render_text_input'),
            'One additional form submission will be allowed per N seconds. 0 to disable.',
            'form',
            $customizeTabName,
            $customizeGroupName,
            type: 'number',
        );

        //formListenOnButton
        $this->settings->add_field(
            'form_listen_on_button',
            'Form Button Handler',
            array($this, 'render_text_input'),
            'For websites with existing <form> submit handlers, attach Send2CRM to the submit button.',
            'form',
            $customizeTabName,
            $customizeGroupName,
            type: 'checkbox',
        );
       
        //Create section for general settings
        $this->settings->add_section(
            'service', 
            'Send2CRM Service',
            'General Settings Related to the operation of the Send2CRM Service',
            $customizeTabName,
        );

        //sessionTimeout
        $this->settings->add_field(
            'session_timeout',
            'Session Timeout', 
            array($this, 'render_text_input'), 
            'Number of minutes inactivity before a visitor session automatically expires.', 
            'service', 
            $customizeTabName, 
            $customizeGroupName,
            type: 'number',
        );

        //syncFrequency
        $this->settings->add_field(
            'sync_frequency',
            'Visitor Update Frequency', 
            array($this, 'render_text_input'), 
            'Minimum number of minutes between visitor sends to Salesforce. Only applies when activity has changed.', 
            'service', 
            $customizeTabName, 
            $customizeGroupName,
            type: 'number',
        );

        //syncFrequencySecondary
        $this->settings->add_field(
            'sync_frequency_secondary',
            'Visitor Update Secondary Frequency', 
            array($this, 'render_text_input'), 
            'Number of minutes between service updates for identified (and active) visitors, to secondary services where the visitor is unknown.', 
            'service', 
            $customizeTabName, 
            $customizeGroupName,
            type: 'number',
        );

        //ignoreBehavior
        $this->settings->add_field(
            'ignore_behavior',
            'Visitor Ignore Behavior',
            array($this, 'render_text_input'),
            'Client action when the service informs that a visitor should be ignored.',
            'service',
            $customizeTabName,
            $customizeGroupName,
        );

        //maxStorage
        $this->settings->add_field(
            'max_storage',
            'Maximum Browser Storage', 
            array($this, 'render_text_input'), 
            'The maximum number of bytes to store in local browser before auto-removing old session data.', 
            'service', 
            $customizeTabName, 
            $customizeGroupName,
            type: 'number',
        );

        //disableAutoEvents since send2crm.js v1.21
        $this->settings->add_field(
            'disable_auto_events',
            'Disable Auto Events', 
            array($this, 'render_text_input'), 
            'Events such as page views will not be automatically recorded on the visitor session.', 
            'service', 
            $customizeTabName, 
            $customizeGroupName,
            type: 'checkbox',
        );

        //originHost since send2crm.js v1.21
        $this->settings->add_field(
            'origin_host',
            'Origin Host', 
            array($this, 'render_text_input'), 
            'Used when operating server-side, to specify the HTTP Origin header. Ignored within browser environment.', 
            'service', 
            $customizeTabName, 
            $customizeGroupName,
            type: 'url',
        );

        //Create section for logging settings such as debug messages
        $this->settings->add_section(
            'logging', 
            'Detailed Logging', 
            'Settings for controlling logging message output for Send2CRM JavaScript.', 
            $customizeTabName
        );

        $this->settings->add_field(
            'debug_enabled', 
            'Debug',
            array($this, 'render_text_input'), 
            'Sends additional troubleshooting information to the browser console.', 
            'logging', 
            $customizeTabName, 
            $customizeGroupName
        );

        $this->settings->add_field(
            'log_prefix',
            'Log Prefix', 
            array($this, 'render_text_input'), 
            'Text prefix for all console log messages.', 
            'logging', 
            $customizeTabName, 
            $customizeGroupName
        );

        // Create section for advanced settings
        $this->settings->add_section(
            'advanced', 
            'Advanced', 
            'Settings related to advanced configuration of Send2CRM.', 
            $customizeTabName
        );

        //ipLookup
        $this->settings->add_field(
            'ip_lookup',
            'IP Lookup Service URL',
            array($this, 'render_text_input'),
            'The URL of an external IP address lookup service. This service is queried when new sessions are created, and fields from the response are saved to the ipInfo property of the session. Must return JSON. Set the ipLookup setting to a falsey value (e.g. empty string) to disable IP lookup completely.',
            'advanced',
            $customizeTabName,
            $customizeGroupName
        );

        //ipFields
        $this->settings->add_field(
            'ip_fields',
            'IP Fields',
            array($this, 'render_text_input'),
            'An array of strings indicating the field names of the IP lookup response to store.',
            'advanced',
            $customizeTabName,
            $customizeGroupName
        );

        //syncOrigins
        $this->settings->add_field(
            'sync_origins',
            'Sync Origins',
            array($this, 'render_text_input'),
            'Enables cross-domain sync for all included origins. A list of urls that begin with “https://”',
            'advanced',
            $customizeTabName,
            $customizeGroupName
        );

        //formServicePath
        $this->settings->add_field(
            'form_path',
            'Form Service Path',
            array($this, 'render_text_input'),
            'The relative path of the form submition endpoint for the Send2CRM API.',
            'advanced',
            $customizeTabName,
            $customizeGroupName
        );

        //visitorServicePath
        $this->settings->add_field(
            'visitor_path',
            'Visitor Service Path',
            array($this, 'render_text_input'),
            'The relative path of the visitor data update endpoint for the Send2CRM API.',
            'advanced',
            $customizeTabName,
            $customizeGroupName
        );
    }

    #endregion

    #region Callbacks
    /**
     * Called by options.php to sanitize and validate settings before saving them to the database.
     * Currently validation is not implemented as all fields are treated as text at the moment
     *  
     * @since   1.0.0
     * @param   array|null  $settings  An array of settings to sanitize and validate.
     * @return  array   An array of sanitized and validated settings.
     */ 
    public function sanitize_and_validate_settings(array | null $settings) : array {
        $input = $settings ?? array();
        $sanitized_output = array();

        foreach ($input as $key => $value) {

            
            $field_type = $this->settings->get_field($key)['type'] ?? 'text';
            //TODO Add validation here and possibly also on the front end so users don't submit invalid data?
            $sanitized_output[$key] = $this->sanitize_by_type($value, $field_type);

        }
        return $sanitized_output;
    }


    /**
     * Callback for displaying the text input field on the settings page.
     * 
     * @since   1.0.0
     * @param   array  $arguments The arguments passed to the callback by the settings API hook.
     */
    public function render_text_input(array $arguments): void {
        $fieldId = $arguments['id'];
        $fieldDetails = $this->settings->get_field($fieldId);
        // Get the current saved value 
        $optionGroup = $fieldDetails['option_group'];
        $value = $this->settings->get_setting($fieldId, $optionGroup); 
        $settingName = $this->settings->get_setting_name($fieldId, $optionGroup);
        $description = $fieldDetails['description'];
        // Render the input field 
        echo "<input type='text' id='" . esc_attr($fieldId) . "' name='" . esc_attr($settingName) . "' value='" . esc_attr($value) . "'>";
        if (empty($description)) {
            return;
        }
        echo "<p class='description'>" . wp_kses_post($description) ."</p>";
    }

    /**
     * Callback for displaying the text input field that a user it required to enter on the settings page.
     * 
     * @since   1.0.0
     * @param   array  $arguments The arguments passed to the callback by the settings API hook.
     */
    public function render_required_text_input(array $arguments): void {
        $fieldId = $arguments['id'];
        $fieldDetails = $this->settings->get_field($fieldId);
        // Get the current saved value 
        $optionGroup = $fieldDetails['option_group'];
        $value = $this->settings->get_setting($fieldId, $optionGroup); 
        $settingName = $this->settings->get_setting_name($fieldId, $optionGroup);
        $description = $fieldDetails['description'];
        // Render the input field 
        echo "<input required type='text' id='" . esc_attr($fieldId) . "' name='" . esc_attr($settingName) . "' value='" . esc_attr($value) . "'>";
        if (empty($description)) {
            return;
        }
        echo "<p class='description'>" . wp_kses_post($description) ."</p>";
    }
    #endregion

    #region Public Functions
    /**
     * Register all the hooks of this class.
     *
     * @since    1.0.0
     * @param   $isAdmin    Whether the current request is for an administrative interface page.
    */
    public function initialize_hooks(bool $isAdmin): void
    {
        if ($isAdmin) {
            return;
        }
        //Hook Send2CRM snippet as script tag in header of public site only and not admin pages
        add_action('wp_enqueue_scripts', array($this, 'insert_snippet'));
    }

    /**
     * Callback for inserting the Send2CRM snippet in the header section of the public facing site.
     * 
     * @since   1.0.0
     */
    public function insert_snippet() {
        $apiKey = $this->settings->get_setting('api_key');
        $apiDomain = $this->settings->get_setting('api_domain');
        $jsVersion = $this->settings->get_setting('js_version'); //TODO tidy this up so it is not directly calling the field by te key
        $jsHash = $this->settings->get_setting('js_hash');
        $useCDN = $this->settings->get_setting('use_cdn') ?? false;

        $upload_dir = wp_upload_dir();
        
        $jsPath = $useCDN ? SEND2CRM_CDN . "@{$jsVersion}/" : $upload_dir['baseurl'] . SEND2CRM_UPLOAD_FOLDERNAME . "{$jsVersion}/";

        if (empty($apiKey) 
            || empty($apiDomain)
            || empty($jsVersion)) 
        {
            return;
        }
        $snippetUrl =  plugin_dir_url( __FILE__ ) . SEND2CRM_SNIPPET_FILENAME;
        $snippetPath = plugin_dir_path( __FILE__ ) . SEND2CRM_SNIPPET_FILENAME;
        $snippetVersion = file_exists($snippetPath) ? filemtime($snippetPath) : $this->version;
        $snippetId = "{$this->settings->pluginSlug}-snippet";

        
        if (wp_register_script( $snippetId, $snippetUrl, array(), $snippetVersion, false ) === false)
        {
            add_settings_error( $snippetId, esc_attr('settings-updated'), 'Snippet could not be registered - Send2CRM will not be activated.' , 'error' );
            return;
        } 
        
        $snippet_data = array(
            'api_key' => $apiKey,
            'api_domain' => $apiDomain,
            'js_location' => $jsPath . SEND2CRM_JS_FILENAME . "?ver={$jsVersion}",
            'hash' => $jsHash
        );
        wp_enqueue_script($snippetId, $snippetUrl, array(), $this->version, false);
        wp_add_inline_script(
            $snippetId,
            sprintf(
                'const snippetData = %s;',
                wp_json_encode($snippet_data),
            ),
            'before',
        );
        $this->apply_additional_settings($snippetId);
    }

    /**
     * Adds Javascript with additional settings for the Send2CRM Service.
     * 
     * @since   1.0.0
     * @param   string  $javascriptId   The ID of the Javascript snippet
     */
    public function apply_additional_settings(string $javascriptId) : void {
        $settingsArray = array();
        $this->add_setting_if_not_empty($settingsArray, 'debug', 'debug_enabled', FILTER_VALIDATE_BOOLEAN);
        $this->add_setting_if_not_empty($settingsArray, 'logPrefix', 'log_prefix');
        $this->add_setting_if_not_empty($settingsArray, 'personalizationCookie', 'personalization_cookie', FILTER_VALIDATE_BOOLEAN);
        $this->add_setting_if_not_empty($settingsArray, 'sessionTimeout', 'session_timeout', FILTER_VALIDATE_INT);
        $this->add_setting_if_not_empty($settingsArray, 'syncFrequency', 'sync_frequency', FILTER_VALIDATE_INT);
        $this->add_setting_if_not_empty($settingsArray, 'syncFrequencySecondary', 'sync_frequency_secondary', FILTER_VALIDATE_INT);
        $this->add_setting_if_not_empty($settingsArray, 'formSelector', 'form_selector');
        $this->add_setting_if_not_empty($settingsArray, 'maxFileSize', 'max_file_size', FILTER_VALIDATE_INT);
        $this->add_setting_if_not_empty($settingsArray, 'formFailMessage', 'form_fail_message');
        $this->add_setting_if_not_empty($settingsArray, 'formIdAttributes', 'form_id_attributes');
        $this->add_setting_if_not_empty($settingsArray, 'formMinTime', 'form_min_time', FILTER_VALIDATE_INT);
        $this->add_setting_if_not_empty($settingsArray, 'formRateCount', 'form_rate_count', FILTER_VALIDATE_INT);
        $this->add_setting_if_not_empty($settingsArray, 'formRateTime', 'form_rate_time', FILTER_VALIDATE_INT);
        $this->add_setting_if_not_empty($settingsArray, 'formListenOnButton', 'form_listen_on_button', FILTER_VALIDATE_BOOLEAN);
        $this->add_setting_if_not_empty($settingsArray, 'maxStorage', 'max_storage', FILTER_VALIDATE_INT);
        $this->add_setting_if_not_empty($settingsArray, 'utmCookie', 'utm_cookie', FILTER_VALIDATE_BOOLEAN);
        $this->add_setting_if_not_empty($settingsArray, 'idCookieDomain', 'id_cookie_domain');
        $this->add_setting_if_not_empty($settingsArray, 'ignoreBehavior', 'ignore_behavior');
        $this->add_setting_if_not_empty($settingsArray, 'disableAutoEvents', 'disable_auto_events');
        $this->add_setting_if_not_empty($settingsArray, 'originHost', 'origin_host');
        $this->add_setting_if_not_empty($settingsArray, 'ipLookup', 'ip_lookup');
        $this->add_setting_if_not_empty($settingsArray, 'ipFields', 'ip_fields');
        $this->add_setting_if_not_empty($settingsArray, 'syncOrigins', 'sync_origins');

        $servicePathsArray = array();
        $this->add_setting_if_not_empty($servicePathsArray, 'formPath', 'form_path');
        $this->add_setting_if_not_empty($servicePathsArray, 'visitorPath', 'visitor_path');

        wp_add_inline_script(
            $javascriptId,
            sprintf(
                'const servicePaths = %s;const additionalSettings = %s;',
                wp_json_encode($servicePathsArray),
                wp_json_encode($settingsArray),
            ),
            'before',);
    }
    #endregion

    #region Private Functions

    /**
     * Executes WordPress Sanitize functions based on the provided type.
     * 
     * @since   1.0.0
     * 
     * @param   mixed   $value  The value to sanitize.
     * @param   string  $type   The type of the value to sanitize. This is one of the validation filter constants from 'https://www.php.net/manual/en/filter.constants.php'.
     * @return  mixed   The sanitized value.
     * 
     */
    private function sanitize_by_type(mixed $value, string $type) : mixed {
        //TODO look at customised sanitizers such as domain, UUID, csv (eg for formIDattributes) etc.
        return match ($type) {
            'url'       => sanitize_url($value),
            'email'     => sanitize_email($value),
            'checkbox'  => rest_sanitize_boolean($value),
            'number'    => absint($value),
            'textarea'  => sanitize_textarea_field($value),
            'array'     => is_array($value) ? array_map('sanitize_text_field', $value) : array(),
            default     => sanitize_text_field($value),
        };
    }

    /**
     * Adds a setting to the settings array if it is not empty using the provided field data and input filter.
     * 
     * @since   1.0.0
     * @param   array   &$settings  The settings array to add the setting to.
     * @param   string  $key        The key to add the setting to.
     * @param   string  $fieldId    The ID of the field to get the setting from.
     * @param   string  $filter     The input filter to apply to the setting value. This is on of the validation filter constants from 'https://www.php.net/manual/en/filter.constants.php'. For example 'FILTER_VALIDATE_BOOLEAN' applies a boolean filter to the setting. 
     */
    private function add_setting_if_not_empty(array &$settings, string $key, string $fieldId,  $filter = null) {
        $value = $this->settings->get_setting($fieldId);
        if ($value !== array() && empty($value) === false) {
            if (isset($filter)) {
                $value = filter_var($value, $filter);
            }
            $settings[$key] = $value;
        }
    }

    #endregion
    
}