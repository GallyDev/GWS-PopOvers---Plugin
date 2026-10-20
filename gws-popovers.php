<?php
/**
 * Plugin Name: GWS PopOvers
 * Description: Gutenberg Popovers mit nativer Popover API, Exit Intent, Timer, Seitenzuordnung und Cookies.
 * Version: 1.0.1
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Author: GWS
 * Text Domain: gws-popovers
 */
if (!defined('ABSPATH')) { exit; }
define('GWS_PO_VERSION', '1.0.1');
define('GWS_PO_DIR', plugin_dir_path(__FILE__));
define('GWS_PO_URL', plugin_dir_url(__FILE__));
require_once GWS_PO_DIR . 'includes/config.php';
require_once GWS_PO_DIR . 'includes/frontend.php';
register_activation_hook(__FILE__, function () {
    gws_po_register_post_type();
    flush_rewrite_rules();
});
register_deactivation_hook(__FILE__, 'flush_rewrite_rules');
