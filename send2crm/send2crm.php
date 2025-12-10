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
 * Plugin URI:      http://wordpress.org/plugins/send2crm/
 * Description:     Send2CRM is the official WordPress plugin for the Send2CRM service by FuseIT to seamlessly connect all your websites with your CRM.
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
use Send2CRM\Public\Snippet;

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
     * 
     * @since    1.0.0
     */
    protected string $menuName;

    /**
     * A reference to the plugin settings class so that is is accesible.
     *
     * @since    1.0.0
     */
    public Settings $settings;


    /**
     * A reference to the public facing class that inserts the Send2CRM snippet into the page.
     * 
     * @since    1.0.0
     */
    public Snippet $snippet;

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
        //Register settings,but the hook initialization should only run on Admin area only.
        $this->settings = new Settings($this->slug, $this->menuName);
        $this->snippet = new Snippet($this->settings, $this->version);

        error_log('Initializing Send2CRM Plugin'); //TODO Remove Debug statements

        $isAdmin = is_admin();

        if ($isAdmin)
        {
            $this->settings->initializeHooks($isAdmin);
        } else {

            $this->snippet->initializeHooks($isAdmin);
        }

    }


}

//Start the plugin
new Send2CRM();













