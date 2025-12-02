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

    public function get_field(string $key) {
        return $this->fields[$key];
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
            $fieldDetails = $this->fields[$key] ?? null;
            if (is_null($fieldDetails)) {
                error_log( "Field {$key} not found returning '{$default}'" );
                return $default;
            }
            $groupName = $fieldDetails['option_group'] ?? $this->get_option_group_name('settings');
        }    
        $array = get_option($this->groups[$groupName]['option_name'], array()); //TODO fix null values
        error_log('Value returned: ' . serialize($array));
        $value = $array[$key] ?? $default;
        error_log('returning: ' . $value );
        return $value;
    }

    /**
     * Retrieves the name of a specific setting from the database.
     * 
     * @since   1.0.0
     * @param   string  $key        The name of the setting to retrieve.
     * @param   string  $groupName  The name of the option group to retrieve the setting from.
     * @return  string  The name of the setting, in the form of option_name[key].
     */
    public function getSettingName(string $key, string $groupName): string {
        $settingName = "{$this->groups[$groupName]['option_name']}[{$key}]";
        error_log('Get Setting Name: ' . $settingName);
        return $settingName;    
    }





    /**
     * Retrieves the section name for a specific setting.  
     * 
     * @since   1.0.0
     * @param   string  $key The name of the setting.
     * @return  string  The section name for the setting.
     */
    private function get_section_name(string $key) {
        return "{$this->pluginSlug}_{$key}_section";
    }

    /**
     * Retrieves the page name for a specific setting.
     * 
     * @since   1.0.0
     * @param   string  $key The name of the setting.
     * @return  string  The page name for the setting.
     */
    private function get_page_name(string $key) {
        return "{$this->pluginSlug}_{$key}_page";
    }

    /**
     * Retrieves the option group name for a specific setting.
     * 
     * @since   1.0.0
     * @param   string  $key    The name of the setting.
     * @return  string  The option group name for the setting.
     */
    private function get_option_group_name(string $key) {
        return "{$this->pluginSlug}_{$key}_option_group";
    }


    /**
     * Retrieves the option name for a specific setting.
     * 
     * @since   1.0.0
     * @param   string  $key    The name of the setting.
     * @return  string  The option name for the setting.
     */
    private function get_option_name(string $key) {
        return "{$this->pluginSlug}_{$key}_option";
    }

    /**
     * Adds a field to the settings page.
     * 
     * @since   1.0.0
     * @param   string  $fieldName      The name of the field.
     * @param   string  $fieldLabel     The label of the field.
     * @param   array   $fieldRenderCallback   The callback function for rendering the field.
     * @param   string  $sectionKey     The name of the section to add the field to.
     * @param   string  $pageName       The name of the page to add the field to.
     * @param   string  $groupName      The name of the option group to add the field to.
     */
    public function add_field(
        string $fieldName,
        string $fieldLabel, 
        array $fieldRenderCallback,
        string $description = '', 
        string $sectionKey = 'settings', 
        string $pageName = 'default_tab',
        string $groupName = 'settings',
    ): void 
    {
        $this->fields[$fieldName] = array(
            'label' => $fieldLabel,
            'callback' => $fieldRenderCallback,
            'page' => $pageName,
            'section' => $this->get_section_name($sectionKey),
            'option_group' => $this->get_option_group_name($groupName),
            'description' => $description
        );
        error_log('Field added: ' . serialize($this->fields[$fieldName]));
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

    /**
     * Adds a group to the settings page.
     *  
     * @since   1.0.0
     * @param   string  $key            The name of the group.
     * @param   array   $sanitizeAndValidateCallback  The callback function for sanitizing and validating the group.
     * @param   string  $tabName        The name of the tab to add the group to. Defaults to the name of the menu slug.
     * @param   string  $tab_title      The title of the tab to add the group to. Defaults to 'Plugin Settings'.
     */
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