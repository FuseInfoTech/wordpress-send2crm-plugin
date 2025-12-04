<?php

declare(strict_types=1);

namespace Send2CRM\Public;

use Send2CRM\Admin\Settings;
use Send2CRM\Admin\VersionManager;
// If this file is called directly, abort.
if (!defined('ABSPATH')) exit;

#region Constants
define('JS_FOLDERNAME', 'js/');
define('SNIPPET_FILENAME', JS_FOLDERNAME . 'sri-snippet.js'); //TODO Fix this so it is either called a path or actually references a filename
define('SEND2CRM_HASH_FILENAME', 'send2crm.sri-hash.sha384');
define('SEND2CRM_JS_FILENAME', 'send2crm.min.js');
define('GITHUB_USERNAME', 'FuseInfoTech');
define('GITHUB_REPO', 'send2crmjs');
define('CDN_URL', 'https://cdn.jsdelivr.net');
define('SEND2CRM_CDN', CDN_URL .'/gh/'. GITHUB_USERNAME . '/' . GITHUB_REPO);
#endregion
/**
 * The frontend functionality of the plugin.
 *
 * Defines the public facing hooks for inserting the Send2CRM Javascript snippet.
 *
 * @since      1.0.0
 *
 * @package    Send2CRM
 * @subpackage Send2CRM/Public
 */
class Snippet {

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


    public function __construct(Settings $settings, string $version) {
        error_log('Initializing Public facing Send2CRM Plugin'); //TODO Remove Debug statements
        $this->settings = $settings;
        $this->version = $version;
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
            error_log('Skipping Snippet Hooks for Admin Page');
            return;
        }
        error_log('Add Snippet Action Hook'); //TODO Remove Debug statements  
        //Hook Send2CRM snippet as script tag in header of public site only and not admin pages
        add_action('wp_enqueue_scripts', array($this,'insertSnippet'));
        
    }

    /**
     * Callback for inserting the Send2CRM snippet in the header section of the public facing site.
     * 
     * @since   1.0.0
     */
    public function insertSnippet() {
        error_log('Inserting Send2CRM Snippet');

        $apiKey = $this->settings->getSetting('send2crm_api_key');
        $apiDomain = $this->settings->getSetting('send2crm_api_domain');
        $jsVersion = $this->settings->getSetting('send2crm_js_version');
        $jsHash = $this->settings->getSetting('send2crm_js_hash');
        $useCDN = $this->settings->getSetting('send2crm_use_cdn') ?? false;

        $jsPath = $useCDN ? SEND2CRM_CDN . "@{$jsVersion}/" : $upload_dir['baseurl'] . UPLOAD_FOLDERNAME . "/{$jsVersion}/";

        if (empty($apiKey) 
            || empty($apiDomain)
            || empty($jsVersion)) 
        {
            error_log('Send2CRM is activated but not correctly configured. Please use `/wp-admin/admin.php?page=send2crm` to add required settings.');
            return;
        }
        $snippetUrl =  plugin_dir_url( __FILE__ ) . SNIPPET_FILENAME;
        $snippetId = "{$this->settings->pluginSlug}-snippet";
        
        if (wp_register_script( $snippetId, $snippetUrl, array(), $this->version, false ) === false)
        {
            error_log('Snippet could not be registered - Send2CRM will not be activated.');
            return;
        } 

        $snippetData = array(
            'api_key' => $apiKey,
            'api_domain' => $apiDomain,
            'js_location' => $jsPath . SEND2CRM_JS_FILENAME . "?ver={$jsVersion}",
            'hash' => $jsHash
        );
        wp_enqueue_script($snippetId, $snippetUrl, array(), $this->version, false);
        error_log('Snippet enqueued at' . $snippetUrl);
        wp_localize_script( $snippetId, 'snippetData', $snippetData); 
    }

    /**
     * Get the hash of the Send2CRM JS file at the provided location.
     * This hash should be provided with each release of Send2CRM and is
     * used for performing Subresource Integrity checks.
     * 
     * @since 1.0.0
     */
    public function getHash(string $location): string {
        error_log('Get hash from '. dirname($location)  . SEND2CRM_HASH_FILENAME);
        $hash = file_get_contents(dirname($location)  . SEND2CRM_HASH_FILENAME);
        return $hash;
    }

    
}