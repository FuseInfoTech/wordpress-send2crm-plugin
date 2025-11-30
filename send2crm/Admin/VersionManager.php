<?php

declare(strict_types=1);

namespace Send2CRM\Admin;

#region Includes
use Send2CRM\Admin\Settings;
#endregion  

// If this file is called directly, abort.
if (!defined('ABSPATH')) exit;

#region Constants
define('VERSION_MANAGER_FILENAME', 'js/version-manager.js');
define('GITHUB_USERNAME', 'FuseInfoTech');
define('GITHUB_REPO', 'send2crmjs');
define('MINIMUM_VERSION', '1.0.0');
#endregion

/**
 * Manages fetching, display and installing of Send2CRM versions used for the plugin
 *
 * @since      1.0.0
 */
class VersionManager {

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

    private string $githubRepo;

    private string $githubUsername;

    private string $minimum_version;

public function __construct(Settings $settings, string $version) {
        error_log('Starting Version Manager'); //TODO Remove Debug statements
        $this->settings = $settings;
        $this->version = $version;
        $this->githubRepo = GITHUB_REPO;
        $this->githubUsername = GITHUB_USERNAME;
        $this->minimum_version = MINIMUM_VERSION;
    }

    /**
     * Register all the hooks of this class.
     *
     * @since    1.0.0
     * @param  bool    $isAdmin    Whether the current request is for an administrative interface page.
     */
    public function initializeHooks(bool $isAdmin): void {
        if ($isAdmin) {
            error_log('Initializing Version Manager Hooks for Admin Page');
            //Hook on admin page to add javascript
            add_action('admin_enqueue_scripts', array($this,'insertVersionManagerJs'));
            //Hook on ajax call to retrieve send2crm releases
            add_action('wp_ajax_fetch_send2crm_releases', array($this, 'ajax_fetch_releases'));
        }
    }

    public function insertVersionManagerJs() {
        error_log('Inserting Version Manager JS'); //TODO Remove Debug statements
        
        $versionManagerJSUrl = plugin_dir_url( __FILE__ ) . VERSION_MANAGER_FILENAME;
        $versionManagerJSId = "{$this->settings->pluginSlug}-version-manager-js";
    
/*         if (wp_register_script( $versionManagerJSId, $versionManagerJSUrl, array('jquery'), $this->version, false ) === false)
        {
            error_log('Snippet could not be registered - Send2CRM will not be activated.');
            return;
        } */
        
        wp_enqueue_script(
            $versionManagerJSId,
            $versionManagerJSUrl,
            array('jquery'),
            $this->version,
            false
        );

        wp_localize_script($versionManagerJSId, 'githubReleases', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('github_releases_nonce')
        ));


    }

     /**
     * AJAX handler for fetching releases
     */
    public function ajax_fetch_releases() {
        //TODO change github to send2crm
        check_ajax_referer('github_releases_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $result = $this->fetch_releases();
        wp_send_json($result);
    }

        /**
     * Fetch releases from GitHub API
     */
    public function fetch_releases() {
        $api_url = sprintf(
            'https://api.github.com/repos/%s/%s/releases',
            $this->githubUsername,
            $this->githubRepo
        );
        
        $response = wp_remote_get($api_url, array(
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress-Plugin'
            ),
            'timeout' => 15
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $releases = json_decode($body, true);
        
        if (!is_array($releases)) {
            return array(
                'success' => false,
                'message' => 'Invalid response from GitHub API'
            );
        }
        
        // Filter releases by minimum version
        $filtered_releases = $this->filter_by_minimum_version($releases);
        
        return array(
            'success' => true,
            'releases' => $filtered_releases
        );
    }

        /**
     * Filter releases by minimum version
     */
    private function filter_by_minimum_version($releases) {
        if (empty($this->minimum_version)) {
            return $releases;
        }
        
        $filtered = array();
        
        foreach ($releases as $release) {
            $version = ltrim($release['tag_name'], 'v');
            
            if (version_compare($version, $this->minimum_version, '>=')) {
                $filtered[] = $release;
            }
        }
        
        return $filtered;
    }

}