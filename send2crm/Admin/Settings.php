<?php

declare(strict_types=1);

namespace Send2CRM\Admin;

// If this file is called directly, abort.
if (!defined('ABSPATH')) exit;
#region Constants
DEFINE('DEFAULT_GROUPING_NAME', 'settings');
DEFINE('DOCS_URL', 'https://fuseit.atlassian.net/wiki/spaces/send2crm/pages/2196471809/JavaScript+client');
#endregion

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

    /**
     * The array of settings fields used for generating Setting API fields for the Settings page.
     * 
     * @since    1.0.0
     */
    private array $fields;

    /**
     * The array of settings sections used for generating Setting API sections for the Settings page.
     *   
     * @since    1.0.0
     */
    private array $sections;

    /**
     * The array of option groups used for generating Setting API groups for the Settings page.
     *   
     * @since    1.0.0
     */
    private array $groups;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    $pluginSlug       The name of this plugin.
     * @param    $menuName         The name to use for the Settings Page, Menu, Title
     */
    public function __construct(string $pluginSlug, string $menuName)
    {
        error_log('Init Send2CRM Settings'); //TODO Remove Debug statements
        $this->pluginSlug = $pluginSlug;
        $this->menuSlug = $pluginSlug;
        //TODO Check if we still need $menuName
        $this->menuName = $menuName;
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


    #region Callbacks
    /**
     * Add the Sections, Fields and register settings for the plugin.
     *
     * @since    1.0.0
     * @param   $isAdmin    Whether the current request is for an administrative interface page.
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
                $sectionDetails['page'],
            );
        }

        foreach ($this->fields as $fieldName => $fieldDetails) {
            error_log('Add Setting Field: ' . $fieldName . ' - ' . serialize($fieldDetails)); //TODO Remove Debug statements
            
            $callbackArgs = array(
                'id' => $fieldName,
                'label_for' => $fieldName,
            );
            add_settings_field(
                $fieldName,
                $fieldDetails['label'],
                $fieldDetails['callback'],
                $fieldDetails['page'],
                $fieldDetails['section'],
                $callbackArgs
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
        error_log('Displaying Setting Page from Callback'); //TODO Remove Debug statements
        ?>
        <div class="wrap"> 
            <h1><?php esc_html_e("{$this->menuName} Settings", $this->pluginSlug); ?></h1> 
            <p>Additional Settings should be left empty unless you require changes from the default settings. For more information on Send2CRM configuration please visit <a target="_blank" href=<?php echo DOCS_URL; ?>>Javascript Client Documentation</a>.</p> 
            <?php $activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'default_tab'; ?>
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
     * Renders a section.
     * 
     * @since   1.0.0
     *  
     *  @param   array   $arguments  The arguments passed to the callback function.
     */
    public function default_render_section(array $arguments): void {
        $sectionId = $arguments['id'];
        $sectionDetails = $this->sections[$sectionId];
        if (empty($sectionDetails)) {
            return; 
        }
        $description = $sectionDetails['description'];
        if (empty($description)) {
            return;
        }
        echo "<p>$description</p>";
    }
    #endregion

    #region Public Functions
    /**
     * Returns the setting field array with metadata of the Setting API field.
     *
     * @since   1.0.0    
     * @param   string  $key    The name of the field to retrieve.    
     * @return  array  The field details if found, otherwise an empty array.
     */
    public function get_field(string $key) {
        if (isset($this->fields[$key])) {
            return $this->fields[$key];
        }
        return array();
    }

    /**
     * Returns the setting section array with metadata of the Setting API section.
     *
     * @since   1.0.0    
     * @param   string  $key    The name of the section to retrieve.    
     * @return  array  The section details if found, otherwise an empty array.
     */
    public function get_section(string $key) {
        if (isset($this->sections[$key])) {
            return $this->sections[$key];
        }
        return array();
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

        public function update_setting(string $key, string $value, string | null $groupName = null ): void {
 //TODO Remove debug Statements
        //escape the value before updating
        if (is_null($groupName)) {
            $fieldDetails = $this->fields[$key] ?? null;
            if (is_null($fieldDetails)) {
                error_log( "Field {$key} not found." );
                return;
            }
            $groupName = $fieldDetails['option_group'] ?? $this->get_option_group_name('settings');
        }

        $optionName = $this->groups[$groupName]['option_name'];
        $array = get_option($optionName, array());
        $array[$key] = $value;
        error_log("Update Setting: {$optionName}[{$key}] with value: {$value}");
        update_option($optionName, $array);
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

    #endregion
    #region Private Functions

    /**
     * Retrieves the section name for a specific setting.  
     * 
     * @since   1.0.0
     * @param   string  $key The name of the setting.
     * @return  string  The section name for the setting.
     */
    private function get_section_name(string $key) : string {
        if (empty($key)) {
            return "{$this->pluginSlug}_settings_section";
        }
        //If the key is already a section name , don't modify it
        if (str_starts_with( $key, $this->pluginSlug ) && str_ends_with( $key, "_section" )  ) {
            return $key;
        }
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
        if (empty($key)) {
            return "{$this->pluginSlug}_settings_page";
        }
        //If the key is already a page name, don't modify it
        if (str_starts_with( $key, $this->pluginSlug ) && str_ends_with( $key, "_page")) {
            return $key;
        }
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
        if (empty($key)) {
            return "{$this->pluginSlug}_settings_option_group";
        }
        //If the key is already an option group name, don't modify it
        if (str_starts_with( $key, $this->pluginSlug ) && str_ends_with( $key, "_option_group")) {
            return $key;
        }
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
        if (empty($key)) {
            return "{$this->pluginSlug}_settings_option";
        }
        //If the key is already an option name, don't modify it
        if (str_starts_with( $key, $this->pluginSlug ) && str_ends_with( $key, "_option")) {
            return $key;
        }
        return "{$this->pluginSlug}_{$key}_option";
    }

    //TODO Separate Public and private functions into correct regions
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
        string $pageName = 'default_tab', //TODO Should this really be called pageName or tabName. Is there value in having a page that isn't a tab since the render treates a tab as a page but it is still called a page.
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
        error_log('Field added: ' . serialize($this->fields[$fieldName])); //TODO Remove debug Statements
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
    public function add_section(
        string $key,
        string $sectionLabel, 
        string $description = '', 
        string $pageName = 'default_tab',
        array | null  $sectionRenderCallback = null, 
    ): string {
        if (is_null($sectionRenderCallback)) {
            $sectionRenderCallback = array($this, 'default_render_section');
        }
        $sectionName = $this->get_section_name($key);
        $this->sections[$sectionName] = array( 
            'label' => $sectionLabel,
            'callback' => $sectionRenderCallback,
            'description' => $description,
            'page' => $pageName,
        );
        return $sectionName;
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
    public function add_group(string $key, array $sanitizeAndValidateCallback, string $tabName = 'default_tab', string $tab_title = 'Plugin Settings'): string {
        $groupName = $this->get_option_group_name($key);
        $this->groups[$groupName] = array(
            'option_name' => $this->get_option_name($key),
            'callback' => $sanitizeAndValidateCallback,
            'tab_name' => $tabName,
            'tab_title' => $tab_title
        );
        return $groupName;
    }
}