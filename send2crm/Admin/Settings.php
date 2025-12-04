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
     * @param   $isAdmin    Whether the current request is for an administrative interface page.
     */
    public function initializeSettings(): void {
        error_log('Creating Send2CRM Settings');
 
        // Register the setting
        //TODO make the settings use an array to avoid pollution the wp_options table with many settings
        register_setting($this->get_option_group_name('required'), 'send2crm_api_key'); //TODO Sanitize and Validate settings by adding validation callback (3rd parameter)
        register_setting($this->get_option_group_name('required'), 'send2crm_api_domain'); 
        register_setting($this->get_option_group_name('version_manager'), 'send2crm_js_version');
        register_setting($this->get_option_group_name('version_manager'), 'send2crm_js_hash');
        register_setting($this->get_option_group_name('version_manager'), 'send2crm_use_cdn');

        // Add the settings section
        add_settings_section(
            $this->get_section_name('required'),
            'Required Settings',
            array($this,'send2crm_settings_section'),
            $this->get_page_name('required')
        );

        add_settings_section( 
            $this->get_section_name('version_manager'), 
            'Send2CRM Versions',
            array($this, 'renderVersionManagerSection'), 
            $this->get_page_name('version_manager')
        );

        // Add the api key setting field
        add_settings_field(
            'send2crm_api_key',
            'Send2CRM API Key',
            array($this,'send2crm_api_key_callback'),
            $this->get_page_name('required'),
            $this->get_section_name('required')
        );

        // Add the api domain settings field
        add_settings_field(
            'send2crm_api_domain',
            'Send2CRM API Domain',
            array($this,'send2crm_api_domain_callback'),
            $this->get_page_name('required'),
            $this->get_section_name('required')
        );

        // Add the js version settings field
        add_settings_field(
            'send2crm_js_version',
            'Send2CRM JS Version',
            array($this,'send2crm_js_version_callback'),
            $this->get_page_name('version_manager'),
            $this->get_section_name('version_manager') 
        );

        add_settings_field(
            'send2crm_js_hash',
            'Send2CRM JS Hash',
            array($this,'send2crm_js_hash_callback'),
            $this->get_page_name('version_manager'),
            $this->get_section_name('version_manager')  
        );

        add_settings_field(
            'send2crm_use_cdn',
            'Use CDN?',
            array($this,'send2crm_use_cdn_callback'),
            $this->get_page_name('version_manager'),
            $this->get_section_name('version_manager')
        );
    }

    private function get_page_name(string $key) {
        return "{$this->pluginSlug}-{$key}-page";
    }

    private function get_option_group_name(string $key) {
        return "{$this->pluginSlug}-{$key}-option-group";
    }

    private function get_section_name(string $key) {
        return "{$this->pluginSlug}-{$key}-section";
    }

    private function get_option_name(string $key) {
        return "{$this->pluginSlug}-{$key}-option";
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
            add_settings_error($this->pluginSlug, $this->pluginSlug . '-message', __('Settings saved.'), 'success');
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
                <a href="?page=<?php echo $this->menuSlug; ?>&tab=version_manager" class="nav-tab <?php echo $activeTab === 'version_manager' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Version Manager', $this->pluginSlug); ?></a>
            </h2>
            <form method="post" action="options.php"> 
                <?php
                    // Output security fields

                    if ($activeTab === 'required_settings') {
                        settings_fields($this->get_option_group_name('required')); 
                        //Wrapper to preseve formatting
                        //echo '<table class="form-table">';
                        // Output sections and fields 
                        //do_settings_fields( 'send2crm', 'send2crm_settings_section' );
                        do_settings_sections( $this->get_page_name('required') );
                        //echo '</table>';
                    } else if ($activeTab === 'version_manager') {
                        settings_fields($this->get_option_group_name('version_manager'));
                        //echo '<table class="form-table">';
                        do_settings_sections( $this->get_page_name('version_manager') );
                        //do_settings_fields( 'send2crm', 'version_manager_section' );
                        //echo '</table>';

                        //$this->renderVersionManagerSection();
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
    public function send2crm_api_key_callback(): void {
        error_log('Send2CRM API Key');
        // Get the current saved value 
        $value = get_option('send2crm_api_key'); 
        // Output the input field 
        echo "<input type='text' name='send2crm_api_key' value='$value' required>";
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
        $value = get_option('send2crm_api_domain');
        // Output the input field 
        echo "<input type='text' name='send2crm_api_domain' value='$value' required>";
        echo "<p class='description'>Enter the domain where the Send2CRM service is hosted, in the case of the Salesforce package this will be the public site configured for Send2CRM endpoints.</p>";
    }

    public function send2crm_js_version_callback() {
        error_log('Send2CRM JS Version');
        // Get the current saved value 
        $value = get_option('send2crm_js_version');
        // Output the input field 
        echo "<input class='regular-text' type='text' id='send2crm_js_version' name='send2crm_js_version' value='$value'>"; //TODO Change this back to read only
        echo "<p class='description'>The selected version of the Send2CRM JavaScript file.</p>  Click Fetch Releases and select a version to update this field.";
    }

    public function send2crm_js_hash_callback() {
        error_log('Send2CRM JS Hash');

        // Get the current saved value 
        $value = get_option('send2crm_js_hash');
        // Output the input field 
        echo "<input class='regular-text' type='text' id='send2crm_js_hash' name='send2crm_js_hash' value='$value'>"; //TODO Change this back to read only
        echo "<p class='description'>The hash of the Send2CRM JavaScript file.</p>  Click Fetch Releases and select a version to update this field.";
    }

    public function send2crm_use_cdn_callback() {
        error_log('Send2CRM Use CDN');
        // Get the current saved value 
        $value = get_option('send2crm_use_cdn');
        // Output the input field 
        echo "<input class='regular-text' type='text' id='send2crm_use_cdn' name='send2crm_use_cdn' value='$value'>"; //TODO Change this back to read only
        echo "<p class='description'>Sets whether we fetch the Send2CRM JavaScript file from a CDN or fetch a local copy and use that.</p>  Click Fetch Releases and select a version to update this field.";
    }

    /**
     * Retrieves a specific setting from the database.
     *
     * @since   1.0.0
     * @param   string  $key    The name of the setting to retrieve.
     */
    public function getSetting(string $key, string $default = ''): mixed {
        error_log('Get Setting: ' . $key); //TODO Remove debug Statements
        $value = get_option($key) ?? $default;
        if (empty($value)) {
            $value = $default;
        }
        error_log('Value returned: ' . $value);
        return $value;
    }

    public function updateSetting(string $key, string $value): void {
        error_log('Update Setting: ' . $key . ' with value: ' . $value); //TODO Remove debug Statements
        //escape the value before updating
        update_option($key, esc_attr($value));
    }

    public function renderVersionManagerSection(): void {
        error_log('Render Version Manager Section');
        ?>
        <div class="wrap">      
            <button id="fetch-releases" style="margin-top: 20px;" class="button button-primary ">Fetch Releases</button>
            <div id="releases-container" style="margin-top: 15px;"></div>
        </div>
        <?php
    }
}