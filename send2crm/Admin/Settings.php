<?php

declare(strict_types=1);

namespace Send2CRM\Admin;

// If this file is called directly, abort.
if (!defined('ABSPATH')) exit;
#region Constants
define('DEFAULT_GROUPING_NAME', 'settings');
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
        $this->pluginSlug = $pluginSlug;
        $this->menuSlug = $pluginSlug;
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
        if ($isAdmin) {
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
     * 
     */
    public function initializeSettings(): void {
        // Register the setting
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
            add_settings_section(
                $sectionName,
                $sectionDetails['label'],
                $sectionDetails['callback'],
                $sectionDetails['page'],
            );
        }

        foreach ($this->fields as $fieldName => $fieldDetails) {
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
        ?>

        <div class="wrap"> 
        <h1><?php echo esc_html("{$this->menuName} Settings"); ?></h1> 

        <?php 
        // Determine active tab with nonce verification
        $activeTab = array_key_first($this->groups) ? $this->groups[array_key_first($this->groups)]['tab_name'] : 'default_tab';
        
        if (isset($_GET['tab']) && isset($_GET['_wpnonce'])) {
            $tab = sanitize_text_field(wp_unslash($_GET['tab']));
            $nonce = sanitize_text_field(wp_unslash($_GET['_wpnonce']));
            
            if (wp_verify_nonce($nonce, 'switch_tab_' . $tab)) {
                // Whitelist allowed tabs
                $allowed_tabs = array_column($this->groups, 'tab_name');
                if (in_array($tab, $allowed_tabs, true)) {
                    $activeTab = $tab;
                }
            }
        }
        ?>
        
        <h2 class="nav-tab-wrapper">
            <?php foreach ($this->groups as $groupName => $groupDetails) { 
                $tab_url = add_query_arg(
                    array(
                        'page' => $this->menuSlug,
                        'tab' => $groupDetails['tab_name'],
                        '_wpnonce' => wp_create_nonce('switch_tab_' . $groupDetails['tab_name'])
                    ),
                    admin_url('admin.php')
                );
                ?>
                <a href="<?php echo esc_url($tab_url); ?>" 
                   class="nav-tab <?php echo $activeTab === $groupDetails['tab_name'] ? 'nav-tab-active' : ''; ?>">
                    <?php echo esc_html($groupDetails['tab_title']); ?>
                </a>
            <?php } ?> 
        </h2>

        <form method="post" action="options.php"> 
            <?php
                foreach ($this->groups as $groupName => $groupDetails) { 
                    if ($activeTab === $groupDetails['tab_name']) {
                        // Output security fields 
                        settings_fields($groupName); 
                        // Output sections and fields 
                        do_settings_sections($groupDetails['tab_name']);
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
        echo "<p>".wp_kses_post($description)."</p>";
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
        if (is_null($groupName)) {
            $fieldDetails = $this->fields[$key] ?? null;
            if (is_null($fieldDetails)) {
                return $default;
            }
            $groupName = $fieldDetails['option_group'] ?? $this->get_option_group_name('settings');
        }    
        $array = get_option($this->groups[$groupName]['option_name'], array()); //TODO fix null values
        $value = $array[$key] ?? $default;
        return $value;
    }

    /**
     *  Updates the value of a settings based on the key and group name if provided.
     * 
     * @since   1.0.0
     * @param   string  $key        The name of the setting to update.
     * @param   string  $value      The new value of the setting.
     * @param   string  $groupName  The name of the option group used to find the setting.
     */
    public function update_setting(string $key, string $value, string | null $groupName = null ): void { //TODO add success or fail as return type
        //escape the value before updating
        if (is_null($groupName)) {
            $fieldDetails = $this->fields[$key] ?? null;
            if (is_null($fieldDetails)) {
                return;
            }
            $groupName = $fieldDetails['option_group'] ?? $this->get_option_group_name('settings');
        }

        $optionName = $this->groups[$groupName]['option_name'];
        $array = get_option($optionName, array());
        $array[$key] = $value;
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
    private function get_option_name(string $key) :string {
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
     * @param   string  $description    The description of the field. Text here will be displayed below the field in the settings menu. 
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