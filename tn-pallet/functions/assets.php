<?php
/**
 * Asset registration and enqueueing.
 *
 * @package TNPallet
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', 'tnp_enqueue_palette_css');
add_action('admin_enqueue_scripts', 'tnp_enqueue_admin_assets');
add_action('enqueue_block_editor_assets', 'tnp_enqueue_palette_css');

function tnp_enqueue_palette_css(): void
{
    tnp_maybe_generate_missing_css();

    $info = tnp_get_css_file_info();

    if (!$info['exists']) {
        return;
    }

    wp_enqueue_style(
        'tnp-palette',
        $info['url'],
        array(),
        (string) $info['modified']
    );
}

function tnp_enqueue_admin_assets(string $hook_suffix): void
{
    tnp_enqueue_palette_css();

    if ('appearance_page_' . TNP_MENU_SLUG !== $hook_suffix) {
        return;
    }

    wp_enqueue_style('wp-color-picker');
    wp_enqueue_style('dashicons');
    wp_enqueue_style(
        'tnp-admin',
        TNP_PLUGIN_URL . 'styles/tn-pallet.css',
        array('wp-color-picker', 'dashicons'),
        TNP_VERSION
    );
    wp_enqueue_script(
        'tnp-admin',
        TNP_PLUGIN_URL . 'scripts/tn-pallet.js',
        array('jquery', 'wp-color-picker'),
        TNP_VERSION,
        true
    );
}
