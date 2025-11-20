<?php

declare(strict_types=1);

namespace Send2CRM\Admin;

// If this file is called directly, abort.
if (!defined('ABSPATH')) exit;

class Settings {
        /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     */
    private string $pluginSlug;

        /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    $pluginSlug       The name of this plugin.
     */
    public function __construct(string $pluginSlug)
    {
        error_log('Initializing Settings'); //TODO Remove Debug statements
        $this->pluginSlug = $pluginSlug;
    }
}