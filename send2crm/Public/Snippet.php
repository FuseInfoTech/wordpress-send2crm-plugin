<?php

declare(strict_types=1);

namespace Send2CRM\Public;

use Send2CRM\Admin\Settings;

// If this file is called directly, abort.
if (!defined('ABSPATH')) exit;

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

    public function __construct(Settings $settings) {
        error_log('Initializing Public facing Send2CRM Plugin'); //TODO Remove Debug statements
        $this->settings = $settings;
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
        add_action('wp_head', array($this,'send2crm_insert_snippet'));
        
    }

    /**
     * Callback for inserting the Send2CRM snippet in the header section of the public facing site.
     * 
     * @since   1.0.0
     */
    public function send2crm_insert_snippet() {
        error_log('Inserting Send2CRM Snippet');
        $jsLocation = $this->settings->getSetting('send2crm_js_location'); // get_option('send2crm_js_location');
        $apiKey = $this->settings->getSetting('send2crm_api_key');
        $apiDomain = $this->settings->getSetting('send2crm_api_domain');

        if (empty($jsLocation) || empty($apiKey) || empty($apiDomain)) {
            error_log('Send2CRM is activated but not correctly configured. Please use `/wp-admin/admin.php?page=send2crm` to add required settings.');
            return;
        }
        echo "<script>(function(s,e,n,d2,cr,m){n[e]=n[e]||{};m=document.createElement('script');m.onload=function(){n[e].init(d2,cr);};m.src=s;document.head.appendChild(m);})('$jsLocation', 'send2crm', window, '$apiDomain', '$apiKey');</script>";
    }

}