<?php

declare(strict_types=1);

namespace Send2CRM\Admin;

// If this file is called directly, abort.
if (!defined('ABSPATH')) exit;

/**
 * Send2CRM Class that contains and manages plugnin settings and 
 * Additional settings for the Send2CRM API. 
 * 
 * @since      1.0.0
 *
 * @package    Send2CRM
 * @subpackage Send2CRM/Admin
 */

class Settings {
        /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     */
    public string $pluginSlug;

    /**
     * The slug name for the Settings menu.
     * Should be unique for this menu page and only include
     * lowercase alphanumeric, dashes, and underscores characters to be compatible with sanitize_key().
     *
     * @since    1.0.0
     */
    private string $menuSlug;



    /**
     * The Name to use for the Settings Page, Menu, Title
     *
     * @since    1.0.0
     */
    private string $menuName;

    private string $optionGroup;
    private string $optionName;


    private array $fields;
    private array $callbacks;


    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    $pluginSlug       The name of this plugin.
     */
    public function __construct(string $pluginSlug, string $menuName)
    {
        error_log('Init Send2CRM Settings'); //TODO Remove Debug statements
        $this->pluginSlug = $pluginSlug;
        $this->menuSlug = $pluginSlug;
        $this->menuName = $menuName;
        //TODO Currently we are just using the plugin slug as the option name and group but we'll move to allowing separate groups soon
        $this->optionGroup = $pluginSlug;
        $this->optionName = $pluginSlug;
        $this->fields = array();
        $this->callbacks = array();

    }

    /**
     * Register all the hooks of this class.
     *
     * @since    1.0.0
     * @param   $isAdmin    Whether the current request is for an administrative interface page.
     */
    public function initializeHooks(bool $isAdmin): void
    {
        error_log('Add Settings Hooks'); //TODO Remove Debug statements
        if ($isAdmin) {
            error_log('Initializing Settings Hooks for Admin Page');
            // Hook into admin_init 
            add_action('admin_init', array($this,'initializeSettings'));
            // Hook into admin_menu to add our page 
            add_action('admin_menu', array($this,'setupSettingsMenu'));
        }
    }


    
    /**
     * Add the Sections, Fields and register settings for the plugin.
     *
     * @since    1.0.0
     */
    public function initializeSettings(): void {
        error_log('Creating Send2CRM Settings');
 
        // Register the setting
        //TODO make the settings use an array to avoid pollution the wp_options table with many settings
        $registerSettingParameters = array(
            //type and description ignored unless 'show_in_rest' => true so technically you can submit anything to options.php and wordpress will accept it but I've included it for clarity. 
            'type' => 'array', 
            'description' => '',
            'show_in_rest' => false,    
            'sanitize_callback' => array($this,'sanitize_and_validate_settings')
        );

        register_setting($this->optionGroup, $this->optionName, $registerSettingParameters);

        // Add the settings section
        add_settings_section(
            'send2crm_settings_section',
            'Required Settings',
            array($this,'send2crm_settings_section'),
            'send2crm'
        );

        $this->create_fields();

        foreach ($this->fields as $fieldName => $fieldLabel) {
            error_log('Add Setting Field: ' . $fieldName . ' - ' . $fieldLabel);
            add_settings_field(
                $fieldName,
                $fieldLabel,
                $this->callbacks[$fieldName],
                'send2crm',
                'send2crm_settings_section'
            );
        }
    }

    /**
     * Add Send2CRM to the Wordpress Settings menu.
     *
     * @since    1.0.0
     */
    public function setupSettingsMenu() 
    {
        error_log("Adding {$this->menuName} Menu");
        // Add a new menu page 
        add_options_page( "{$this->menuName} Settings", // Page title 
            $this->menuName, // Menu title 
            'manage_options', // Capability required 
            $this->menuSlug, // Menu slug 
            array($this,'renderSettingsPageContent'), // Callback function 
            99 // Position 
        );
    }

    /**
     * Renders the Settings page to display for the Settings menu defined above.
     *
     * @since   1.0.0
     * @param   activeTab       The name of the active tab.
     */
    public function renderSettingsPageContent(string $activeTab = ''): void
    {
        // Check user capabilities
        if (!current_user_can('manage_options'))
        {
            return;
        }

        // Add error/update messages
        // check if the user have submitted the settings. Wordpress will add the "settings-updated" $_GET parameter to the url
        if (isset($_GET['settings-updated']))
        {
            // Add settings saved message with the class of "updated"
            add_settings_error($this->pluginSlug, $this->pluginSlug . '-message', 'Settings saved.', 'success');
        }

        // Show error/update messages
        //settings_errors($this->pluginSlug);


        error_log('Displaying Setting Page from Callback'); //TODO Remove Debug statements
        ?>
        <div class="wrap"> 
            <h1><?php esc_html_e("{$this->menuName} Settings", $this->pluginSlug); ?></h1> 
            <?php $activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'required_settings'; ?>
            <h2 class="nav-tab-wrapper">
                <a href="?page=<?php echo $this->menuSlug; ?>&tab=required_settings" class="nav-tab <?php echo $activeTab === 'required_settings' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Required Settings', $this->pluginSlug); ?></a>
            </h2>
            <form method="post" action="options.php"> 
                <?php 
                    if ($activeTab === 'required_settings') {
                        // Output security fields 
                        settings_fields($this->optionGroup); 
                        // Output sections and fields 
                        do_settings_sections('send2crm'); 
                    }
                    // Output save button 
                    submit_button(); 
                ?> 
            </form> 
        </div> 
        <?php 
    }

    /**
     * Callback for displaying the required Settings section.
     * 
     * @since   1.0.0
     */
    public function send2crm_settings_section(): void {
        error_log('Send2CRM Settings Section');
        echo '<p>The following settings are required for Send2CRM to function. The Send2CRM snippet will not be included until they are added.</p>';
    }

    /**
     * Callback for displaying the API key setting.
     * 
     * @since   1.0.0
     */
    public function send2crm_api_key_callback() {
        error_log('Send2CRM API Key');
        // Get the current saved value 
        $value = $this->getSetting('send2crm_api_key'); 
        $settingName = $this->getSettingName('send2crm_api_key');
        // Output the input field 
        echo "<input type='text' id='send2crm_api_key'}' name=$settingName value='$value'>";
        echo "<p class='description'>Enter the shared API key configured for your service in Salesforce.</p>";
    }

    /**
     * Callback for displaying the API domain setting.
     * 
     * @since   1.0.0
     */
    public function send2crm_api_domain_callback() {
        error_log('Send2CRM API Domain');
        // Get the current saved value 
        $value = $this->getSetting('send2crm_api_domain');
        $settingName = $this->getSettingName('send2crm_api_domain');
        // Output the input field 
        echo "<input type='text' id='send2crm_api_domain' name='$settingName' value='$value'>";
        echo "<p class='description'>Enter the domain where the Send2CRM service is hosted, in the case of the Salesforce package this will be the public site configured for Send2CRM endpoints.</p>";
    }

    /**
     * Callback for displaying the JavaScript location setting.
     * 
     * @since   1.0.0
     */
    public function send2crm_js_location_callback() {
        error_log('Send2CRM JS Location');
        // Get the current saved value 
        $value = $this->getSetting('send2crm_js_location');
        $settingName = $this->getSettingName('send2crm_js_location');
        // Output the input field 
        echo "<input type='text' id='send2crm_js_location' name='$settingName' value='$value'>";
        echo "<p class='description'>Enter the location of the Send2CRM JavaScript file.</p>";
    }

    /**
     * Retrieves a specific setting from the database.
     *
     * @since   1.0.0
     * @param   string  $key    The name of the setting to retrieve.
     * @return  string  The value of the setting if found, otherwise an empty string.
     */
    public function getSetting(string $key, string $default = ''): string {
        error_log('Get Setting: ' . $key);
        $array = get_option($this->optionName, array());
        error_log('Value returned: ' . serialize($array));
        $value = $array[$key] ?? $default;
        error_log('returning: ' . $value );
        return $value;
    }

    public function getSettingName(string $key): string {
        $settingName = "{$this->optionName}[{$key}]";
        error_log('Get Setting Name: ' . $settingName);
        return $settingName;
    }

    public function sanitize_and_validate_settings(array | null $settings = array()) : array {
        error_log('Sanitize and Validate Settings :' . serialize($settings)); //TODO Remove Debug statements
        //TODO get the current settings and use those as a starting point to stop clearing settings when they aren't included in the form
        $output = array();

        foreach ($settings as $key => $value) {
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

    public function add_field(string $fieldName, string $fieldLabel, array $fieldrenderfunction) {
        $this->fields[$fieldName] = $fieldLabel;
        $this->callbacks[$fieldName] = $fieldrenderfunction;
    }

    public function create_fields() {
        $this->add_field('send2crm_api_key', 'Send2CRM API Key', array($this, 'send2crm_api_key_callback'));
        $this->add_field('send2crm_api_domain', 'Send2CRM API Domain', array($this, 'send2crm_api_domain_callback'));
        $this->add_field('send2crm_js_location', 'Send2CRM JS Location', array($this, 'send2crm_js_location_callback'));
    }

}