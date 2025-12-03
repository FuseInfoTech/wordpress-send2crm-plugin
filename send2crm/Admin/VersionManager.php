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
define('MINIMUM_VERSION', '1.21.0');
define('UPLOAD_FOLDERNAME', '/send2crm-releases/');
define('SEND2CRM_HASH_FILENAME', 'send2crm.sri-hash.sha384');
define('SEND2CRM_JS_FILENAME', 'send2crm.min.js');
define('CDN_PREFIX', 'https://cdn.jsdelivr.net/gh/'); //TODO create a constants.php for shared constants ones required for a specific class should use live in the class and use the contanst keyword instead
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
            add_action('wp_ajax_download_send2crm_release', array($this, 'ajax_download_release'));
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

        $upload_dir = wp_upload_dir();
        wp_localize_script($versionManagerJSId, 'send2crmReleases', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('send2crm_releases_nonce'),
            'cdn_prefix' => CDN_PREFIX . $this->githubUsername . '/' . $this->githubRepo,
            'local_prefix' => $upload_dir['baseurl'] . UPLOAD_FOLDERNAME
        ));


    }

     /**
     * AJAX handler for fetching releases
     */
    public function ajax_fetch_releases() {
        //TODO change github to send2crm
        check_ajax_referer('send2crm_releases_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $result = $this->fetch_releases();
        wp_send_json($result);
    }

     /**
     * AJAX handler for downloading releases
     */
    public function ajax_download_release() {
        check_ajax_referer('send2crm_releases_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $tag_name = isset($_POST['tag_name']) ? sanitize_text_field($_POST['tag_name']) : '';
        
        if (empty($tag_name)) {
            wp_send_json_error('Missing tag name');
        }
        
        $result = $this->download_release_files($tag_name);
        wp_send_json($result);
    }

        /**
     * Fetch releases from GitHub API
     */
    public function fetch_releases() {
        $api_url = "https://api.github.com/repos/{$this->githubUsername}/{$this->githubRepo}/releases";
        
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

    /**
     * Download specific files from a Send2CRM release
     */
    public function download_release_files($tag_name) {
        // Files to download
        $files_to_download = array(
            SEND2CRM_JS_FILENAME,
        );
        
        // Create a downloads directory in wp-content/uploads
        $upload_dir = wp_upload_dir();
        $download_dir = $upload_dir['basedir'] . UPLOAD_FOLDERNAME . $tag_name;
        
        if (!file_exists($download_dir)) {
            wp_mkdir_p($download_dir);
        }
        
        $results = array();
        $all_success = true;
        
        foreach ($files_to_download as $filename) {
            // Construct raw file URL
            $file_url = "https://raw.githubusercontent.com/{$this->githubUsername}/{$this->githubRepo}/{$tag_name}/{$filename}";

            $file_path = $download_dir . '/' . $filename;
            
            // Check if file already exists
            if (file_exists($file_path)) {
                $results[$filename] = array(
                    'success' => true,
                    'message' => 'File already exists',
                    'file_path' => $file_path,
                    'file_url' => $upload_dir['baseurl'] . UPLOAD_FOLDERNAME . $tag_name . '/' . $filename,
                    'skipped' => true
                );
                continue;
            }
            
            // Download the file
            $response = wp_remote_get($file_url, array(
                'timeout' => 60,
                'headers' => array(
                    'User-Agent' => 'WordPress-Plugin'
                )
            ));
            
            if (is_wp_error($response)) {
                $results[$filename] = array(
                    'success' => false,
                    'message' => 'Download failed: ' . $response->get_error_message()
                );
                $all_success = false;
                continue;
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            
            if ($response_code !== 200) {
                $results[$filename] = array(
                    'success' => false,
                    'message' => 'File not found (HTTP ' . $response_code . ')'
                );
                $all_success = false;
                continue;
            }
            
            // Save file content
            $file_content = wp_remote_retrieve_body($response);
            $saved = file_put_contents($file_path, $file_content);
            
            if ($saved === false) {
                $results[$filename] = array(
                    'success' => false,
                    'message' => 'Failed to save file'
                );
                $all_success = false;
                continue;
            }

            $results[$filename] = array(
                'success' => true,
                'message' => 'Downloaded successfully',
                'file_path' => $file_path,
                'file_url' => $upload_dir['baseurl'] . UPLOAD_FOLDERNAME . $tag_name . '/' . $filename,
                'file_size' => size_format(filesize($file_path))
            );
        }

        return array(
            'success' => $all_success, 
            'message' => $all_success ? 'All files downloaded successfully' : 'Some files failed to download',
            'files' => $results,
            'download_dir' => $download_dir,
            'upload_url' => $upload_dir['baseurl'] . UPLOAD_FOLDERNAME . $tag_name  . '/',
            'version' => $tag_name
        );
    }

}