<?php

declare(strict_types=1);

namespace Send2CRM\Public;

use Send2CRM\Admin\Settings;
use Send2CRM\Admin\VersionManager;
// If this file is called directly, abort.
if (!defined('ABSPATH')) exit;

#region Constants
define('JS_FOLDERNAME', 'js/');
define('SNIPPET_FILENAME', JS_FOLDERNAME . 'sri-snippet.js'); //TODO Fix this so it is either called a path or actually references a filename
//define('SEND2CRM_HASH_FILENAME', 'send2crm.sri-hash.sha384');
//define('SEND2CRM_JS_FILENAME', 'send2crm.min.js');
//define('GITHUB_USERNAME', 'FuseInfoTech');
//define('GITHUB_REPO', 'send2crmjs');
//define('CDN_URL', 'https://cdn.jsdelivr.net');
//define('SEND2CRM_CDN', CDN_URL .'/gh/'. GITHUB_USERNAME . '/' . GITHUB_REPO);
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

        $this->settings->add_section(
            'settings', 
            'Required Settings', 
            'The following settings are required for Send2CRM to function. The Send2CRM snippet will not be included until they are added.'
        );
        $this->settings->add_field(
            'send2crm_api_key',
            'Send2CRM API Key',
            array($this, 'render_text_input'),
            'Enter the shared API key configured for your service in Salesforce.'
        );

        $this->settings->add_field(
            'send2crm_api_domain',
            'Send2CRM API Domain',
            array($this, 'render_text_input'),
            'Enter the domain where the Send2CRM service is hosted, in the case of the Salesforce package this will be the public site configured for Send2CRM endpoints.'
        );

        //Create additional settings groups and sections
        $customizeTabName = 'custom_tab';
        $customizeGroupName = 'customize';
        $this->settings->add_group(
            $customizeGroupName, 
            array($this,'sanitize_and_validate_settings'), 
            $customizeTabName, 
            'Additional Settings'
        );

        //Create section for cookies settings
        $this->settings->add_section(
            'cookies', 
            'Cookies', 
            'Settings related to browser cookies and their behavior.', 
            $customizeTabName
        );

        //personalizationCookie
        $this->settings->add_field(
            'personalization_cookie',
            'Personalization Cookie', 
            array($this, 'render_text_input'), 
            "Store Visitor Segment values into a 'send2crm' cookie for website back end access.", 
            'cookies', 
            $customizeTabName, 
            $customizeGroupName
        );

        //utmCookie
        $this->settings->add_field(
            'utm_cookie',
            'UTM Cookie', 
            array($this, 'render_text_input'), 
            "Automatically process UTM parameters into a 'send2crmUTM' cookie.", 
            'cookies', 
            $customizeTabName, 
            $customizeGroupName
        );

        //idCookieDomain
        $this->settings->add_field(
            'id_cookie_domain',
            'Visitor ID Cookie Domain',
            array($this, 'render_text_input'),
            'If set, Send2CRM will generate a cookie using this domain and use to store and retrieve the visitor identifier. Useful for syncing visitors across subdomains.',
            'cookies',
            $customizeTabName,
            $customizeGroupName
        );


        //Create section for form settings
        $this->settings->add_section(
            'form', 
            'Form', 
            'Settings related to the behavior of forms that submit data to Send2CRM.', 
            $customizeTabName
        );
        //formSelector
        $this->settings->add_field(
            'form_selector',
            'Form Selector', 
            array($this, 'render_text_input'), 
            "Selector for passing to JavaScript querySelector() method, to get forms that should be automatically processed. Must reference <form> elements.", 
            'form', 
            $customizeTabName, 
            $customizeGroupName
        );

        //maxFileSize
        $this->settings->add_field(
            'max_file_size',
            'Maximum File Size', 
            array($this, 'render_text_input'), 
            "The maximum number of bytes total allowed for file uploads in a single form submission.", 
            'form', 
            $customizeTabName, 
            $customizeGroupName
        );

        //formIdAttributes
        $this->settings->add_field(
            'form_id_attributes',
            'Form Identifier Attribute(s)', 
            array($this, 'render_text_input'), 
            "The attributes of the <form> element that should be used as the form's identifier.", 
            'form', 
            $customizeTabName, 
            $customizeGroupName
        );

        //formFailMessage
        $this->settings->add_field(
            'form_fail_message',
            'Form Failure Message',
            array($this, 'render_text_input'),
            'Validation message text set when a form submission fails. Empty to disable.',
            'form',
            $customizeTabName,
            $customizeGroupName

        );

        //formMinTime
        $this->settings->add_field(
            'form_min_time',
            "Form Minimum Time",
            array($this, 'render_text_input'),
            'Number of seconds before allowing form submission. Only applies to auto-attached forms, 0 to disable.',
            'form',
            $customizeTabName,
            $customizeGroupName
        );

        //formRateCount
        $this->settings->add_field(
            'form_rate_count',
            "Form Submission Limit",
            array($this, 'render_text_input'),
            'Number of form submissions allowed per visitor. 0 to disable limits.',
            'form',
            $customizeTabName,
            $customizeGroupName
        );

        //formRateTime
        $this->settings->add_field(
            'form_rate_time',
            "Form Additional Submission Time",
            array($this, 'render_text_input'),
            'One additional form submission will be allowed per N seconds. 0 to disable.',
            'form',
            $customizeTabName,
            $customizeGroupName
        );

        //formListenOnButton
        $this->settings->add_field(
            'form_listen_on_button',
            'Form Button Handler',
            array($this, 'render_text_input'),
            'For websites with existing <form> submit handlers, attach Send2CRM to the submit button.',
            'form',
            $customizeTabName,
            $customizeGroupName
        );
       
        //Create section for general settings
        $this->settings->add_section(
            'service', 
            'Send2CRM Service',
            'General Settings Related to the operation of the Send2CRM Service',
            $customizeTabName
        );

        //sessionTimeout
        $this->settings->add_field(
            'session_timeout',
            'Session Timeout', 
            array($this, 'render_text_input'), 
            'Number of minutes inactivity before a visitor session automatically expires.', 
            'service', 
            $customizeTabName, 
            $customizeGroupName
        );

        //syncFrequency
        $this->settings->add_field(
            'sync_frequency',
            'Visitor Update Frequency', 
            array($this, 'render_text_input'), 
            'Minimum number of minutes between visitor sends to Salesforce. Only applies when activity has changed.', 
            'service', 
            $customizeTabName, 
            $customizeGroupName
        );

        //syncFrequencySecondary
        $this->settings->add_field(
            'sync_frequency_secondary',
            'Visitor Update Secondary Frequency', 
            array($this, 'render_text_input'), 
            'Number of minutes between service updates for identified (and active) visitors, to secondary services where the visitor is unknown.', 
            'service', 
            $customizeTabName, 
            $customizeGroupName
        );

        //ignoreBehavior
        $this->settings->add_field(
            'ignore_behavior',
            'Visitor Ignore Behavior',
            array($this, 'render_text_input'),
            'Client action when the service informs that a visitor should be ignored.',
            'service',
            $customizeTabName,
            $customizeGroupName
        );

        //maxStorage
        $this->settings->add_field(
            'max_storage',
            'Maximum Browser Storage', 
            array($this, 'render_text_input'), 
            'The maximum number of bytes to store in local browser before auto-removing old session data.', 
            'service', 
            $customizeTabName, 
            $customizeGroupName
        );

        //disableAutoEvents since send2crm.js v1.21
        $this->settings->add_field(
            'disable_auto_events',
            'Disable Auto Events', 
            array($this, 'render_text_input'), 
            'Events such as page views will not be automatically recorded on the visitor session.', 
            'service', 
            $customizeTabName, 
            $customizeGroupName
        );

        //originHost since send2crm.js v1.21
        $this->settings->add_field(
            'origin_host',
            'Origin Host', 
            array($this, 'render_text_input'), 
            'Used when operating server-side, to specify the HTTP Origin header. Ignored within browser environment.', 
            'service', 
            $customizeTabName, 
            $customizeGroupName
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


    #region Settings API Callbacks
    /**
     * Called by options.php to sanitize and validate settings before saving them to the database.
     * Currently validation is not implemented as all fields are treated as text at the moment
     *  
     * @since   1.0.0
     * @param   array|null  $settings  An array of settings to sanitize and validate.
     * @return  array   An array of sanitized and validated settings.
     */ 
    public function sanitize_and_validate_settings(array | null $settings) : array {
        error_log('Sanitize and Validate Settings :' . serialize($settings)); //TODO Remove Debug statements
        //TODO get the current settings and use those as a starting point to stop clearing settings when they aren't included in the form
        $input = $settings ?? array();
        $sanitizedOutput = array();

        foreach ($input as $key => $value) {
            $sanitizedOutput[$key] = sanitize_text_field($value);
            //TODO Add validation based oin the field type. Do we also need to do this on the front end to provide a better user expereience?
        }
        return $sanitizedOutput;
    }


    /**
     * Callback for displaying the text input field.
     * 
     * @since   1.0.0
     * @param   string  $fieldId        The ID of the field.
     * @param   string  $description    The description of the field. If provided the description will be displayed below the form input.
     */
    public function render_text_input(array $arguments): void {
        $fieldId = $arguments['id'];
        error_log($fieldId);
        $fieldDetails = $this->settings->get_field($fieldId);
        // Get the current saved value 
        $optionGroup = $fieldDetails['option_group'];
        $value = $this->settings->getSetting($fieldId,$optionGroup); 
        $settingName = $this->settings->getSettingName($fieldId,$optionGroup);
        $description = $fieldDetails['description'];
        // Render the input field 
        echo "<input type='text' id='$fieldId' name='$settingName' value='$value'>";
        if (empty($description)) {
            return;
        }
        echo "<p class='description'>$description</p>";
    }
    #endregion

    #region Public Functions


    /**
     * Callback for inserting the Send2CRM snippet in the header section of the public facing site.
     * 
     * @since   1.0.0
     */
    public function insertSnippet() {
        error_log('Inserting Send2CRM Snippet');

        $apiKey = $this->settings->getSetting('send2crm_api_key');
        $apiDomain = $this->settings->getSetting('send2crm_api_domain');
        $jsVersion = $this->settings->getSetting('send2crm_js_version');
        $jsHash = $this->settings->getSetting('send2crm_js_hash');
        $useCDN = $this->settings->getSetting('send2crm_use_cdn') ?? false;

        $jsPath = $useCDN ? SEND2CRM_CDN . "@{$jsVersion}/" : $upload_dir['baseurl'] . UPLOAD_FOLDERNAME . "/{$jsVersion}/";

        if (empty($apiKey) 
            || empty($apiDomain)
            || empty($jsVersion)) 
        {
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
            'js_location' => $jsPath . SEND2CRM_JS_FILENAME . "?ver={$jsVersion}",
            'hash' => $jsHash
        ));
        wp_enqueue_script($snippetId, $snippetUrl, array(), $this->version, false);
        error_log('Snippet enqueued at' . $snippetUrl);
        wp_add_inline_script( $snippetId, "const snippetData = {$snippetJson};", 'before');
    }

    /**
     * Callback for adding Javascript with additional settings for the Send2CRM Service.
     * 
     * @since   1.0.0
     */
    public function applyAdditionalSettings() {
        error_log('Apply Additional Settings');

        $settingJsUrl =  plugin_dir_url( __FILE__ ) . ADDITIONAL_SETTINGS_FILENAME;
        $settingJsId = "{$this->settings->pluginSlug}-settings";
        if (wp_register_script( $settingJsId, $settingJsUrl, array(), $this->version, false ) === false)
        {
            error_log('Additional Settings Javascript could not be registered - No Additional Settings will be applied.');
            return;
        }

        $settingsArray = array();
        $this->addSettingIfNotEmpty($settingsArray,'debug','debug_enabled',FILTER_VALIDATE_BOOLEAN);
        $this->addSettingIfNotEmpty($settingsArray,'logPrefix','log_prefix');
        $this->addSettingIfNotEmpty($settingsArray,'personalizationCookie','personalization_cookie',FILTER_VALIDATE_BOOLEAN);
        $this->addSettingIfNotEmpty($settingsArray,'sessionTimeout','session_timeout',FILTER_VALIDATE_INT);
        $this->addSettingIfNotEmpty($settingsArray,'syncFrequency','sync_frequency',FILTER_VALIDATE_INT);
        $this->addSettingIfNotEmpty($settingsArray,'syncFrequencySecondary','sync_frequency_secondary',FILTER_VALIDATE_INT);
        $this->addSettingIfNotEmpty($settingsArray,'formSelector','form_selector');
        $this->addSettingIfNotEmpty($settingsArray,'maxFileSize','max_file_size',FILTER_VALIDATE_INT);
        $this->addSettingIfNotEmpty($settingsArray,'formFailMessage','form_fail_message');
        $this->addSettingIfNotEmpty($settingsArray,'formIdAttributes','form_id_attributes');
        $this->addSettingIfNotEmpty($settingsArray,'formMinTime','form_min_time',FILTER_VALIDATE_INT);
        $this->addSettingIfNotEmpty($settingsArray,'formRateCount','form_rate_count',FILTER_VALIDATE_INT);
        $this->addSettingIfNotEmpty($settingsArray,'formRateTime','form_rate_time',FILTER_VALIDATE_INT);
        $this->addSettingIfNotEmpty($settingsArray,'formListenOnButton','form_listen_on_button',FILTER_VALIDATE_BOOLEAN);
        $this->addSettingIfNotEmpty($settingsArray,'maxStorage','max_storage',FILTER_VALIDATE_INT);
        $this->addSettingIfNotEmpty($settingsArray,'utmCookie','utm_cookie',FILTER_VALIDATE_BOOLEAN);
        $this->addSettingIfNotEmpty($settingsArray,'idCookieDomain','id_cookie_domain');
        $this->addSettingIfNotEmpty($settingsArray,'ignoreBehavior','ignore_behavior');
        $this->addSettingIfNotEmpty($settingsArray,'disableAutoEvents','disable_auto_events');
        $this->addSettingIfNotEmpty($settingsArray,'originHost','origin_host');
        $this->addSettingIfNotEmpty($settingsArray,'ipLookup','ip_lookup');
        $this->addSettingIfNotEmpty($settingsArray,'ipFields','ip_fields');
        $this->addSettingIfNotEmpty($settingsArray,'syncOrigins','sync_origins');

        $servicePathsArray = array();
        $this->addSettingIfNotEmpty($servicePathsArray,'formPath','form_path');
        $this->addSettingIfNotEmpty($servicePathsArray,'visitorPath','visitor_path');

        wp_enqueue_script($settingJsId, $settingJsUrl, array(), $this->version, false);
        error_log('Additional Settings Javascript enqueued at' . $settingJsUrl);
        $settingsJson = json_encode($settingsArray);
        $servicePathsJson = json_encode($servicePathsArray);
        $passArraysToJs = "const servicePaths = {$servicePathsJson};const additionalSettings = {$settingsJson};";
        wp_add_inline_script( $settingJsId, $passArraysToJs, 'before');
    }

    private function addSettingIfNotEmpty(array &$settings, string $key, string $fieldId,  $filter = null) {
        $value = $this->settings->getSetting($fieldId);
        if ($value !== array() && empty($value) === false) {
            if (isset($filter)) {
                $value = filter_var($value, $filter);
            }
            $settings[$key] = $value;
        }
    }

    #endregion
    
}