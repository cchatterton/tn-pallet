<?php
/**
 * Block editor palette integration.
 *
 * @package TNPallet
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('after_setup_theme', 'tnp_register_editor_colour_palette', 100);
add_filter('wp_theme_json_data_theme', 'tnp_filter_theme_json_palette', 100);

function tnp_register_editor_colour_palette(): void
{
    $editor_palette = tnp_get_editor_colour_palette();

    if (empty($editor_palette)) {
        return;
    }

    remove_theme_support('editor-color-palette');
    remove_theme_support('disable-custom-colors');

    add_theme_support('editor-color-palette', $editor_palette);

    if (!tnp_allow_custom_editor_colours()) {
        add_theme_support('disable-custom-colors');
    }
}

function tnp_filter_theme_json_palette($theme_json)
{
    if (!method_exists($theme_json, 'update_with')) {
        return $theme_json;
    }

    $editor_palette = tnp_get_editor_colour_palette();

    if (empty($editor_palette)) {
        return $theme_json;
    }

    $theme_json->update_with(
        array(
            'version' => 3,
            'settings' => array(
                'color' => array(
                    'palette' => $editor_palette,
                    'custom' => tnp_allow_custom_editor_colours(),
                ),
            ),
        )
    );

    return $theme_json;
}

function tnp_get_editor_colour_palette(): array
{
    $editor_palette = array();

    foreach (tnp_get_palette() as $colour) {
        $slug = tnp_sanitize_palette_name((string) ($colour['name'] ?? ''));
        $hex = strtolower((string) ($colour['hex'] ?? ''));

        if ('' === $slug || !preg_match('/^#[0-9a-f]{6}$/', $hex)) {
            continue;
        }

        $editor_palette[] = array(
            'name' => tnp_format_palette_label($slug),
            'slug' => $slug,
            'color' => $hex,
        );
    }

    return $editor_palette;
}

function tnp_format_palette_label(string $name): string
{
    $name = tnp_sanitize_palette_name($name);
    $name = str_replace('-', ' ', $name);

    return ucwords($name);
}

function tnp_allow_custom_editor_colours(): bool
{
    $value = get_option(TNP_CUSTOM_EDITOR_COLOURS_OPTION, '1');

    return '0' !== (string) $value;
}

function tnp_save_custom_editor_colours_setting(bool $allow_custom_colours): bool
{
    return update_option(TNP_CUSTOM_EDITOR_COLOURS_OPTION, $allow_custom_colours ? '1' : '0', false);
}

function tnp_clear_editor_palette_caches(): void
{
    if (function_exists('wp_clean_theme_json_cache')) {
        wp_clean_theme_json_cache();
    }

    if (function_exists('wp_cache_delete')) {
        wp_cache_delete('theme_json', 'theme_json');
    }
}
