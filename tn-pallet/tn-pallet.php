<?php
/**
 * Plugin Name: TN Pallet
 * Plugin URI: https://github.com/cchatterton/tn-pallet/releases/latest
 * Description: Manage a named colour palette and generated utility CSS from WordPress admin.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * Update URI: https://github.com/cchatterton/tn-pallet
 * Author: Techn
 * Author URI: https://techn.com.au
 * Text Domain: tn-pallet
 */

if (!defined('ABSPATH')) {
    exit;
}

define('TNP_VERSION', '0.1.0');
define('TNP_PLUGIN_FILE', __FILE__);
define('TNP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TNP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TNP_OPTION_NAME', 'alphasys_colour_palette');
define('TNP_MENU_SLUG', 'alphasys-palette');
define('TNP_GITHUB_REPO_URL', 'https://github.com/cchatterton/tn-pallet');

require_once TNP_PLUGIN_DIR . 'functions/helpers.php';
require_once TNP_PLUGIN_DIR . 'functions/setup.php';
require_once TNP_PLUGIN_DIR . 'functions/assets.php';
require_once TNP_PLUGIN_DIR . 'functions/admin.php';
require_once TNP_PLUGIN_DIR . 'includes/class-tn-pallet-github-updater.php';

register_activation_hook(TNP_PLUGIN_FILE, 'tnp_activate_plugin');

add_action('plugins_loaded', 'tnp_load_github_updater');

function tnp_load_github_updater(): void
{
    if (class_exists('TNP_GitHub_Updater')) {
        $updater = new TNP_GitHub_Updater();
        $updater->register();
    }
}
