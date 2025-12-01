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
    private array $sections;
    private array $groups;


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
        $this->sections = array();
        $this->groups = array();
        
        $this->create_fields();
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
        foreach ($this->groups as $groupName => $groupDetails) {
            $registerSettingParameters = array(
                //type and description ignored unless 'show_in_rest' => true so technically you can submit anything to options.php and wordpress will accept it but I've included it for clarity. 
                'type' => 'array', 
                'description' => '',
                'show_in_rest' => false,    
                'sanitize_callback' => $groupDetails['callback'],
            );

            register_setting($groupName, $groupDetails['option_name'], $registerSettingParameters);
        }   



        foreach ($this->sections as $sectionName => $sectionDetails) {
            error_log('Add Setting Section: ' . $sectionName . ' - ' . $sectionDetails['label']); //TODO Remove Debug statements
            add_settings_section(
                $sectionName,
                $sectionDetails['label'],
                $sectionDetails['callback'],
                $sectionDetails['page']
            );
        }

        foreach ($this->fields as $fieldName => $fieldDetails) {
            error_log('Add Setting Field: ' . $fieldName . ' - ' . serialize($fieldDetails)); //TODO Remove Debug statements
            add_settings_field(
                $fieldName,
                $fieldDetails['label'],
                $fieldDetails['callback'],
                $fieldDetails['page'],
                $fieldDetails['section']
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

            <?php
            
                $activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'default_tab'; ?>

                <h2 class="nav-tab-wrapper">
                    <?php foreach ($this->groups as $groupName => $groupDetails) { ?> 
                        <a href="?page=<?php echo $this->menuSlug; ?>&tab=<?php echo $groupDetails['tab_name']; ?>" class="nav-tab <?php echo $activeTab === $groupDetails['tab_name'] ? 'nav-tab-active' : ''; ?>"><?php esc_html_e($groupDetails['tab_title'], $this->pluginSlug); ?></a>
                    <?php } ?> 
                </h2>
                <form method="post" action="options.php"> 
                    <?php
                        foreach ($this->groups as $groupName => $groupDetails) { 
                            if ($activeTab === $groupDetails['tab_name']) {
                                // Output security fields 
                                settings_fields($groupName); 
                                // Output sections and fields 
                                    do_settings_sections( $groupDetails['tab_name'] );
                            }
                        }
                        // Output save button 
                        submit_button(); 
                    ?> 
                </form> 
            
        </div> 
        <?php 
    }



    /**
     * Callback for displaying the API key setting.
     * 
     * @since   1.0.0
     */
    public function send2crm_api_key_callback() {
        error_log('Send2CRM API Key');
        // Get the current saved value 
        $value = $this->getSetting('send2crm_api_key',$this->fields['send2crm_api_domain']['option_group']); 
        $settingName = $this->getSettingName('send2crm_api_key',$this->fields['send2crm_api_key']['option_group']);
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
        $value = $this->getSetting('send2crm_api_domain', $this->fields['send2crm_api_domain']['option_group']);
        $settingName = $this->getSettingName('send2crm_api_domain', $this->fields['send2crm_api_domain']['option_group']);
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
        //$fieldId = 'send2crm_js_location'; TODO Refactor this callback so there is a single callback for all fields
        $fieldDetails = $this->fields['send2crm_js_location'];
        error_log($fieldDetails['label']);
        // Get the current saved value 
        $value = $this->getSetting('send2crm_js_location', $fieldDetails['option_group']);
        $settingName = $this->getSettingName('send2crm_js_location', $fieldDetails['option_group']);
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
    public function getSetting(string $key, string | null $groupName = null, string $default = ''): string {
        error_log('Get Setting: ' . $key);
        if (is_null($groupName)) {
            $groupName = $this->fields[$key]['option_group'];
        }
        $array = get_option($this->groups[$groupName]['option_name'], array()); //TODO fix null values
        error_log('Value returned: ' . serialize($array));
        $value = $array[$key] ?? $default;
        error_log('returning: ' . $value );
        return $value;
    }

    public function getSettingName(string $key, string $groupName): string {
        $settingName = "{$this->groups[$groupName]['option_name']}[{$key}]";
        error_log('Get Setting Name: ' . $settingName);
        return $settingName;    
    }



    public function add_field(
        string $fieldName,
        string $fieldLabel, 
        array $fieldRenderCallback, 
        string $sectionKey = 'settings', 
        string $pageName = 'default_tab',
        string $groupName = 'settings'): void 
    {
        $this->fields[$fieldName] = array(
            'label' => $fieldLabel,
            'callback' => $fieldRenderCallback,
            'page' => $pageName,
            'section' => $this->get_section_name($sectionKey),
            'option_group' => $this->get_option_group_name($groupName),
        );
    }

    public function create_fields(): void {
        $this->add_field('send2crm_api_key', 'Send2CRM API Key', array($this, 'send2crm_api_key_callback'));
        $this->add_field('send2crm_api_domain', 'Send2CRM API Domain', array($this, 'send2crm_api_domain_callback'));
        $this->add_field('send2crm_js_location', 'Send2CRM JS Location', array($this, 'send2crm_js_location_callback'));
    }

    private function get_section_name(string $key) {
        return "{$this->pluginSlug}_{$key}_section";
    }
    private function get_page_name(string $key) {
        return "{$this->pluginSlug}_{$key}_page";
    }

    private function get_option_group_name(string $key) {
        return "{$this->pluginSlug}_{$key}_option_group";
    }

    private function get_option_name(string $key) {
        return "{$this->pluginSlug}_{$key}_option";
    }

    /**
     * Adds a section to the settings page.
     * 
     * @since   1.0.0
     * @param   string  $key            The name of the section.
     * @param   string  $sectionLabel   The label of the section.
     * @param   array   $sectionRenderCallback  The callback function for rendering the section.
     * @param   string  $pageName       The name of the page to add the section to. Defaults to the name of the menu slug.
     */
    public function add_section(string $key , string $sectionLabel, array $sectionRenderCallback, string $pageName = 'default_tab'): void {
        $this->sections[$this->get_section_name($key)] = array( 
            'label' => $sectionLabel,
            'callback' => $sectionRenderCallback,
            'page' => $pageName,
        );
    }

    public function add_group(string $key, array $sanitizeAndValidateCallback, string $tabName = 'default_tab', string $tab_title = 'Plugin Settings'): void {
        $groupName = $this->get_option_group_name($key);
        $this->groups[$groupName] = array(
            'option_name' => $this->get_option_name($key),
            'callback' => $sanitizeAndValidateCallback,
            'tab_name' => $tabName,
            'tab_title' => $tab_title
        );
    }



    

    

}