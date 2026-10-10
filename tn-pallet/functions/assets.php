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
        tnp_normalise_public_asset_url($info['url']),
        array(),
        (string) $info['modified']
    );
}

function tnp_normalise_public_asset_url(string $url): string
{
    $home_scheme = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_SCHEME));
    $forwarded_proto = isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
        ? strtolower((string) wp_unslash($_SERVER['HTTP_X_FORWARDED_PROTO']))
        : '';

    if ('https' === $home_scheme || is_ssl() || in_array('https', array_map('trim', explode(',', $forwarded_proto)), true)) {
        return set_url_scheme($url, 'https');
    }

    return 'http' === $home_scheme ? set_url_scheme($url, 'http') : $url;
}

function tnp_enqueue_admin_assets(string $hook_suffix): void
{
    tnp_enqueue_palette_css();

    if ('appearance_page_' . TNP_MENU_SLUG !== $hook_suffix) {
        return;
    }

    if (tnp_ai_available()) {
        wp_enqueue_media();
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
    wp_localize_script('tnp-admin', 'TNP', array(
        'document' => tnp_get_document(),
        'revision' => tnp_document_revision(),
        'previous' => get_option('tnp_palette_previous_json', ''),
        'restUrl' => rest_url('tn-pallet/v1/'),
        'nonce' => wp_create_nonce('wp_rest'),
        'strings' => tnp_admin_strings(),
    ));
}

function tnp_admin_strings(): array
{
    return array(
        'section' => __('Section name', 'tn-pallet'),
        'removeSection' => __('Remove section', 'tn-pallet'),
        'removeColour' => __('Remove colour', 'tn-pallet'),
        'addColour' => __('Add colour', 'tn-pallet'),
        'colour' => __('Colour', 'tn-pallet'),
        'unsaved' => __('Unsaved changes', 'tn-pallet'),
        'saved' => __('Saved', 'tn-pallet'),
        'saving' => __('Saving…', 'tn-pallet'),
        'interpreting' => __('Interpreting image and saving…', 'tn-pallet'),
        'success' => __('Palette saved and CSS regenerated.', 'tn-pallet'),
        'failed' => __('The request failed. Check your connection or reload, then try again.', 'tn-pallet'),
        'invalidName' => __('Use a unique section name starting with a letter.', 'tn-pallet'),
        'removeConfirm' => __('Remove this section and its colours? This takes effect when you save.', 'tn-pallet'),
        'lastSection' => __('Keep at least one section and one colour.', 'tn-pallet'),
        'limit' => __('Use up to 50 sections and 300 colours.', 'tn-pallet'),
        'invalidColour' => __('Enter a six-digit hex colour, for example #002840.', 'tn-pallet'),
        'copied' => __('JSON copied.', 'tn-pallet'),
        'copyFallback' => __('Select the JSON and copy it with your keyboard.', 'tn-pallet'),
        'jsonPending' => __('Save JSON first, or reload to discard the JSON changes, before editing the palette.', 'tn-pallet'),
        'saveFirst' => __('Save your palette or JSON changes before interpreting an image.', 'tn-pallet'),
        'jsonConfirm' => __('Replace the saved palette with this JSON? Colours omitted from it will be removed. The previous saved JSON will be kept for recovery.', 'tn-pallet'),
        'previousLoaded' => __('Previous JSON loaded. Review it, then Save JSON to restore.', 'tn-pallet'),
        'chooseImage' => __('Choose a website mockup', 'tn-pallet'),
        'useImage' => __('Use image', 'tn-pallet'),
    );
}
