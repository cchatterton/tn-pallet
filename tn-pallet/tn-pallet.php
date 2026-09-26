<?php
/**
 * Plugin Name: TN Pallet
 * Description: Manage a named colour palette and generated utility CSS from WordPress admin.
 * Version: 0.1.14
 * Requires at least: 7.0
 * Requires PHP: 7.4
 * Update URI: https://github.com/cchatterton/tn-pallet
 * Author: Techn
 * Author URI: https://techn.com.au
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Techn Controller API: 1
 * Text Domain: tn-pallet
 */

if (!defined('ABSPATH')) {
    exit;
}

define('TNP_VERSION', '0.1.14');
define('TNP_PLUGIN_FILE', __FILE__);
define('TNP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TNP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TNP_OPTION_NAME', 'tnp_colour_palette');
define('TNP_CUSTOM_EDITOR_COLOURS_OPTION', 'tnp_allow_custom_editor_colours');
define('TNP_MENU_SLUG', 'tn-pallet');
define('TNP_GITHUB_REPO_URL', 'https://github.com/cchatterton/tn-pallet');

require_once TNP_PLUGIN_DIR . 'functions/helpers.php';
require_once TNP_PLUGIN_DIR . 'functions/setup.php';
require_once TNP_PLUGIN_DIR . 'functions/assets.php';
require_once TNP_PLUGIN_DIR . 'functions/admin.php';
require_once TNP_PLUGIN_DIR . 'functions/editor.php';

register_activation_hook(TNP_PLUGIN_FILE, 'tnp_activate_plugin');

require_once __DIR__ . '/functions/controller-client.php';
tnuc_client_register(__FILE__, 'tn-pallet');
