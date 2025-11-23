<?php
#region Plugin Details
/**
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 * 
 * @package   Send2CRM Wordpress Plugin
 * @author    FuseIT  support@fuseit.com
 * @copyright 2025 Fuse Information Technologies Ltd
 * @license   GPL v2 or later
 * @link      https://fuseit.com
 * 
 * Plugin Name: Send2CRM
 * Plugin URI:      @TODO
 * Description:     @TODO
 * Version: 1.0.0
 * Author: FuseIT
 * Author URI: https://fuseit.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires PHP:    8.3
 */
#endregion
// In strict mode, only a variable of exact type of the type declaration will be accepted.
declare(strict_types=1);
namespace Send2CRM;

use Send2CRM\Admin\Settings;
// If this file is called directly, abort.
if (!defined('ABSPATH')) exit;

// Autoloader
require_once plugin_dir_path(__FILE__) . 'Autoloader.php';


#region Constants
/**
 * Current plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('SEND2CRM_VERSION', '1.0.0');

// The string used to uniquely identify this plugin.
define('SEND2CRM_SLUG', 'send2crm');

// The name to for the Settings Menu, Page and Title
define('SEND2CRM_MENU_NAME', 'Send2CRM');
#endregion
class Send2CRM {
    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     */
    protected string $slug;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     */
    protected string $version;

    /**
     * The name to for the Settings Menu, Page and Title
     */
    protected string $menuName;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->version = SEND2CRM_VERSION;
        $this->slug = SEND2CRM_SLUG;
        $this->menuName = SEND2CRM_MENU_NAME;
        error_log('Initializing Send2CRM Plugin'); //TODO Remove Debug statements

        $this->defineHooks();

    }

        /**
     * Create the objects and register all the hooks of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function defineHooks(): void
    {
        $isAdmin = is_admin();

        //Register settings,but the hook initialization should only run on Admin area only.
        $settings = new Settings($this->slug, $this->menuName);

        if ($isAdmin)
        {
            $settings->initializeHooks($isAdmin);
        } else {
            //Hook Send2CRM snippet as script tag in header of public site only and not admin pages
            add_action('wp_head', array($this,'send2crm_insert_snippet'));
        }

    }

    public function send2crm_insert_snippet() {
        error_log('Inserting Send2CRM Snippet');
        $jsLocation = get_option('send2crm_js_location');
        $apiKey = get_option('send2crm_api_key');
        $apiDomain = get_option('send2crm_api_domain');

        if (empty($jsLocation) || empty($apiKey) || empty($apiDomain)) {
            error_log('Send2CRM is activated but not correctly configured. Please use `/wp-admin/admin.php?page=send2crm` to add required settings.');
            return;
        }
        echo "<script>(function(s,e,n,d2,cr,m){n[e]=n[e]||{};m=document.createElement('script');m.onload=function(){n[e].init(d2,cr);};m.src=s;document.head.appendChild(m);})('$jsLocation', 'send2crm', window, '$apiDomain', '$apiKey');</script>";
    }

}

//Start the plugin
new Send2CRM();













