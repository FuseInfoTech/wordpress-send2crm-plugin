<?php

declare(strict_types=1);

namespace Send2CRM\Admin;

#region Includes
use Send2CRM\Admin\Settings;
#endregion  

// If this file is called directly, abort.
if (!defined('ABSPATH')) exit;

#region Constants
define('VERSION_MANAGER_FILENAME', 'js/version-manager.js'); //TODO move this to a constant either in the namespace or in the class.
define('GITHUB_USERNAME', 'FuseInfoTech');
define('GITHUB_REPO', 'send2crmjs');
define('MINIMUM_VERSION', '1.21.0');
define('UPLOAD_FOLDERNAME', '/send2crm-releases/');
define('SEND2CRM_HASH_FILENAME', 'send2crm.sri-hash.sha384');
define('SEND2CRM_JS_FILENAME', 'send2crm.min.js');
define('CDN_URL', 'https://cdn.jsdelivr.net'); //TODO create a constants.php for shared constants ones required for a specific class should use live in the class and use the contanst keyword instead
define('SEND2CRM_CDN', CDN_URL .'/gh/'. GITHUB_USERNAME . '/' . GITHUB_REPO);
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

    private array $releases;

public function __construct(Settings $settings, string $version) {
        error_log('Starting Version Manager'); //TODO Remove Debug statements
        $this->settings = $settings;
        $this->version = $version;
        $this->githubRepo = GITHUB_REPO;
        $this->githubUsername = GITHUB_USERNAME;
        $this->minimum_version = MINIMUM_VERSION;

        $this->initialize_settings();
    }

    public function initialize_settings() {

        $versionTabName = 'default_tab';

        
        //Create section for cookies settings

        $versionSectionName = $this->settings->add_section( //TODO add custom callback for version section
            'version', 
            'Version Configuration', 
            'Select your Send2CRM.js version a local copy or the Content Delivery Network version will be used for your site.'
        );

        $this->settings->add_field(
            'js_version',
            'Version', 
            array($this, 'render_version_input'), 
            "Select which version of Send2CRM.js to use. If 'Use CDN?' is not checked, a local copy of the javascript will be fetched from the CDN. Select a version to update this field.", 
            $versionSectionName
        );

        $this->settings->add_field(
            'js_hash',
            'File Verification Code', 
            array($this, 'render_hash_input'), 
            "A unique code to confirm the expected Send2CRM.js file loads on your site. Choose a version above to update this field automatically.", 
            $versionSectionName, 
        );

        $this->settings->add_field(
            'use_cdn',
            'Use CDN?', 
            array($this, 'render_cdn_input'), 
            "If checked, public facing pages will use JsDeliver CDN for Send2CRM.js. Otherwise, fetch a local copy of Send2CRM.js and reference that.", 
            $versionSectionName
        );
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
            add_filter('pre_update_option_send2crm_settings_option', array($this, 'filter_version_settings'),10,3);
        }
    }

    #region Callbacks
    public function renderVersionManagerSection(): void {
        error_log('Render Version Manager Section');
        ?>
        <div class="wrap">      
            <button id="fetch-releases" style="margin-top: 20px;" class="button button-primary ">Fetch Releases</button>
            <div id="releases-container" style="margin-top: 15px;"></div>
        </div>
        <?php
    }
    public function render_version_input($arguments) : void {
        $fieldId = $arguments['id'];
        $fieldDetails = $this->settings->get_field($fieldId);
        // Get the current saved value 
        $optionGroup = $fieldDetails['option_group'];
        $value = $this->settings->getSetting($fieldId,$optionGroup); 
        $settingName = $this->settings->getSettingName($fieldId,$optionGroup);
        $description = $fieldDetails['description'];
        // Render the input field 

/*         if (empty($releases)) {
            $this->fetch_releases();
        } */
        echo "<select id='$fieldId' name='$settingName' data-current-version='$value'>";
        echo "<option value='' selected>DEBUG:No releases found. Click the Refresh button to fetch releases.</option>";
        echo "</select>";
        echo "<button id='fetch-releases' class='button button-primary'><span id='fetch-icon' style='vertical-align: sub;' class='dashicons dashicons-update'></span></button>";
        if (empty($description)) {
            return;
        }
        echo "<p class='description'>$description</p>";
    }

    public function render_hash_input(array $arguments): void {
        $fieldId = $arguments['id'];
        error_log($fieldId);
        $fieldDetails = $this->settings->get_field($fieldId);
        // Get the current saved value 
        $optionGroup = $fieldDetails['option_group'];
        $value = $this->settings->getSetting($fieldId); 
        $settingName = $this->settings->getSettingName($fieldId,$optionGroup);
        $description = $fieldDetails['description'];
        // Render the input field 
        echo "<input class='regular-text' placeholder='Please select a version and save changes to populate hash.' readonly type='text' id='$fieldId' name='$settingName' value='$value'>";
        if (empty($description)) {
            return;
        }
        echo "<p class='description'>$description</p>";
    }

    public function render_cdn_input(array $arguments): void {
        $fieldId = $arguments['id'];
        error_log($fieldId);
        $fieldDetails = $this->settings->get_field($fieldId);
        // Get the current saved value 
        $optionGroup = $fieldDetails['option_group'];
        $value = $this->settings->getSetting($fieldId,$optionGroup); 
        $settingName = $this->settings->getSettingName($fieldId,$optionGroup);
        $description = $fieldDetails['description'];
        // Render the input field 
        $checked = checked($value, 1, false);
        echo "<input type='checkbox' id='$fieldId' name='$settingName' value='1' $checked>";
        if (empty($description)) {
            return;
        }
        echo "<p class='description'>$description</p>";
    }

    public function filter_version_settings(array $newValue = [], array | string $currentValue  = "", $optionName  = "") : array {
        if ( empty($optionName) || empty($newValue) || $optionName != 'send2crm_settings_option') {
            return $newValue;
        }
        $currentVersion = '';
        $currentUseCDN = false;
        if (empty($currentValue) === false) {
            $currentVersion = array_key_exists('js_version', $currentValue) ? $currentValue['js_version'] : '';
            $currentUseCDN = array_key_exists('use_cdn', $currentValue) ? ($currentValue['use_cdn'] === '1' ? true : false) : false;
        }
        error_log('Updating Send2CRM Version'); //TODO Remove Debug statements
        $newVersion = array_key_exists('js_version', $newValue) ? $newValue['js_version'] : $currentVersion;
        $newUseCDN = array_key_exists('use_cdn', $newValue) ? ($newValue['use_cdn'] === '1' ? true : false) : false;
        $updateHash = false;
        $downloadJS = false;
        $removeJS = false;
        if ($currentVersion !== $newVersion) {
            error_log("Updating Send2CRM Version from {$currentVersion} to {$newVersion}"); //TODO Remove Debug statements
            $updateHash = true;
            if ($newUseCDN === false && $this->release_file_exists($newVersion) === false) { //TODO download release on save changes if use CDN is disabled and vesion hasn't been downloaded
                $downloadJS = true;
            }
        } else if ($newUseCDN !== $currentUseCDN) {
            if ($newUseCDN && $this->release_file_exists($newVersion)) { 
                $removeJS = true;
            } else if ($newUseCDN === false && $this->release_file_exists($newVersion) === false) { //TODO download release on save changes if use CDN is disabled and vesion hasn't been downloaded
                $downloadJS = true;
            }
        }

        if ($updateHash) {
            error_log("Updating Send2CRM CDN from {$currentUseCDN} to {$useCDN}");
            $newHash = $this->getHash(SEND2CRM_CDN . "@{$newVersion}/");
            if (empty($newHash)) {
                add_settings_error( 'js_version', esc_attr( 'settings_updated' ), "Unable to update to {$newVersion}", 'error' );
                $newValue['js_version'] = $currentVersion;
                $newValue['use_cdn'] = $currentUseCDN ? '1' : '';
                return $newValue;
            }
            $newValue['js_hash'] = $newHash; //TODO Check the hash is valid before saving?
        }

        if ($downloadJS && $this->check_integrity()) { //TODO implement integrity check fully
            $results = $this->download_release_files($newVersion);
            if ($results['success'] === false) {
                $newValue['js_version'] = $currentVersion;
                $newValue['use_cdn'] = $currentUseCDN ? '1' : '';
                add_settings_error( 'js_version', esc_attr( 'settings_updated' ), "Unable to update to {$newVersion}", 'error' );       
                return $newValue;
            }
            add_settings_error( 'js_version', esc_attr( 'settings_updated' ), $newVersion . ' downloaded successfully!', 'updated' );
        }



        if ($removeJS) {
            //TODO Remove JS
            $success = $this->remove_release_files($currentVersion);
            if ($success === false) {
                add_settings_error( 'js_version', esc_attr( 'settings_updated' ), "Unable to remove local files for {$currentVersion}", 'error' );
            }
        }

        return $newValue;
    }

    public function check_integrity() {
        return true;
    }

    public function remove_release_files($version) {
        $upload_dir = wp_upload_dir();
        $success = false;
        if (file_exists($upload_dir['basedir'] . UPLOAD_FOLDERNAME . $version . '/' . SEND2CRM_JS_FILENAME)) {
            $success = unlink($upload_dir['basedir'] . UPLOAD_FOLDERNAME . $version . '/' . SEND2CRM_JS_FILENAME);
        }
        return $success;
    }



    public function release_file_exists($version): bool {
        $upload_dir = wp_upload_dir();
        return file_exists($upload_dir['basedir'] . UPLOAD_FOLDERNAME . $version . '/' . SEND2CRM_JS_FILENAME);
    }

    /**
     * Get the hash of the Send2CRM JS file at the provided location.
     * This hash should be provided with each release of Send2CRM and is
     * used for performing Subresource Integrity checks.
     * 
     * @since 1.0.0
     */
    public function insertVersionManagerJs() {
        error_log('Inserting Version Manager JS'); //TODO Remove Debug statements
        
        $versionManagerJSUrl = plugin_dir_url( __FILE__ ) . VERSION_MANAGER_FILENAME;
        $versionManagerJSId = "{$this->settings->pluginSlug}-version-manager-js";
    
        if (wp_register_script( $versionManagerJSId, $versionManagerJSUrl, array('jquery'), $this->version, false ) === false) 
        {
            error_log('Snippet could not be registered - Send2CRM will not be activated.');
            return;
        }
        
        wp_enqueue_script(
            $versionManagerJSId,
            $versionManagerJSUrl,
            array('jquery'),
            $this->version,
            false
        );

        $upload_dir = wp_upload_dir();
        //TODO update this script and object to descripe its function that that only fetches releases
        wp_localize_script($versionManagerJSId, 'send2crmReleases', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('send2crm_releases_nonce'),
            'version_element_id' => "js_version",
        ));
    }

    #endregion

    public function getHash(string $location): string {
        error_log('Get hash from '. $location . SEND2CRM_HASH_FILENAME); //TODO Add checks for bad paths to prevent critical erros
        $hash = file_get_contents($location . SEND2CRM_HASH_FILENAME);
        if (!$hash) {
            return '';
        }
        return $hash;
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
                $filtered[$release['tag_name']] = $release;
            }
        }
        
        return $filtered;
    }

    /**
     * Download specific files from a Send2CRM release
     */
    public function download_release_files(string $tag_name): array {
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