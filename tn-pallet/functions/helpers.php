<?php
/**
 * Shared palette helpers.
 *
 * @package TNPallet
 */

if (!defined('ABSPATH')) {
    exit;
}

function tnp_get_default_palette(): array
{
    return array(
        array('name' => 'dark-1', 'hex' => '#10243e', 'rgb' => '16 36 62'),
        array('name' => 'dark-2', 'hex' => '#1c3555', 'rgb' => '28 53 85'),
        array('name' => 'dark-3', 'hex' => '#2d4a70', 'rgb' => '45 74 112'),
        array('name' => 'light-1', 'hex' => '#ffffff', 'rgb' => '255 255 255'),
        array('name' => 'light-2', 'hex' => '#f4f7fb', 'rgb' => '244 247 251'),
        array('name' => 'light-3', 'hex' => '#e6edf5', 'rgb' => '230 237 245'),
        array('name' => 'contrast-1', 'hex' => '#f2b705', 'rgb' => '242 183 5'),
        array('name' => 'contrast-2', 'hex' => '#ef6c00', 'rgb' => '239 108 0'),
        array('name' => 'contrast-3', 'hex' => '#00a3a3', 'rgb' => '0 163 163'),
        array('name' => 'base-1', 'hex' => '#0f172a', 'rgb' => '15 23 42'),
        array('name' => 'base-2', 'hex' => '#64748b', 'rgb' => '100 116 139'),
        array('name' => 'base-3', 'hex' => '#cbd5e1', 'rgb' => '203 213 225'),
    );
}

function tnp_get_palette(): array
{
    $stored = get_option(TNP_OPTION_NAME, '');

    if (!is_string($stored) || '' === trim($stored)) {
        return tnp_get_default_palette();
    }

    $decoded = json_decode($stored, true);

    if (!is_array($decoded)) {
        return tnp_get_default_palette();
    }

    $palette = array();

    foreach ($decoded as $row) {
        if (!is_array($row)) {
            continue;
        }

        $name = isset($row['name']) ? tnp_sanitize_palette_name((string) $row['name']) : '';
        $hex  = isset($row['hex']) ? strtolower((string) $row['hex']) : '';
        $rgb  = isset($row['rgb']) ? trim((string) $row['rgb']) : '';

        if ('' === $name || !preg_match('/^#[0-9a-f]{6}$/', $hex) || !tnp_rgb_string_is_valid($rgb)) {
            continue;
        }

        $palette[] = array(
            'name' => $name,
            'hex'  => $hex,
            'rgb'  => $rgb,
        );
    }

    return $palette;
}

function tnp_save_palette(array $palette): bool
{
    return update_option(TNP_OPTION_NAME, wp_json_encode(array_values($palette)), false);
}

function tnp_sanitize_palette_name(string $name): string
{
    $name = strtolower($name);
    $name = preg_replace('/[^a-z0-9-]+/', '-', $name);
    $name = trim((string) $name, '-');
    $name = preg_replace('/-+/', '-', $name);

    if (preg_match('/^[0-9]/', $name)) {
        return '';
    }

    return sanitize_key($name);
}

function tnp_parse_colour(string $value)
{
    $value = trim(strtolower($value));

    if (preg_match('/^#?([0-9a-f]{3})$/', $value, $matches)) {
        $hex = $matches[1];
        $rgb = array(
            hexdec($hex[0] . $hex[0]),
            hexdec($hex[1] . $hex[1]),
            hexdec($hex[2] . $hex[2]),
        );

        return tnp_colour_from_rgb($rgb);
    }

    if (preg_match('/^#?([0-9a-f]{6})$/', $value, $matches)) {
        $hex = $matches[1];
        $rgb = array(
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        );

        return tnp_colour_from_rgb($rgb);
    }

    if (preg_match('/^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/', $value, $matches)) {
        return tnp_colour_from_rgb(array((int) $matches[1], (int) $matches[2], (int) $matches[3]));
    }

    if (preg_match('/^(\d{1,3})(?:\s*,\s*|\s+)(\d{1,3})(?:\s*,\s*|\s+)(\d{1,3})$/', $value, $matches)) {
        return tnp_colour_from_rgb(array((int) $matches[1], (int) $matches[2], (int) $matches[3]));
    }

    return false;
}

function tnp_colour_from_rgb(array $rgb)
{
    foreach ($rgb as $channel) {
        if (!is_int($channel) || $channel < 0 || $channel > 255) {
            return false;
        }
    }

    return array(
        'hex' => sprintf('#%02x%02x%02x', $rgb[0], $rgb[1], $rgb[2]),
        'rgb' => implode(' ', $rgb),
    );
}

function tnp_rgb_string_is_valid(string $rgb): bool
{
    if (!preg_match('/^\d{1,3} \d{1,3} \d{1,3}$/', $rgb)) {
        return false;
    }

    foreach (explode(' ', $rgb) as $channel) {
        $value = (int) $channel;

        if ($value < 0 || $value > 255) {
            return false;
        }
    }

    return true;
}

function tnp_get_css_file_info(): array
{
    $uploads = wp_upload_dir();
    $base_dir = trailingslashit($uploads['basedir']) . 'alphasys-palette';
    $base_url = trailingslashit($uploads['baseurl']) . 'alphasys-palette';
    $path = trailingslashit($base_dir) . 'palette.css';

    return array(
        'dir' => $base_dir,
        'url' => trailingslashit($base_url) . 'palette.css',
        'path' => $path,
        'exists' => file_exists($path),
        'modified' => file_exists($path) ? (int) filemtime($path) : 0,
        'writable' => (file_exists($base_dir) && is_writable($base_dir)) || (!file_exists($base_dir) && is_writable(dirname($base_dir))),
    );
}

function tnp_generate_palette_css(array $palette)
{
    $info = tnp_get_css_file_info();

    if (!wp_mkdir_p($info['dir'])) {
        return new WP_Error('tnp_css_directory', __('The palette CSS directory could not be created.', 'tn-pallet'));
    }

    $css = tnp_build_palette_css($palette);
    $written = file_put_contents($info['path'], $css);

    if (false === $written) {
        return new WP_Error('tnp_css_file', __('The palette CSS file could not be written.', 'tn-pallet'));
    }

    return true;
}

function tnp_build_palette_css(array $palette): string
{
    $css = ":root {\n";

    foreach ($palette as $colour) {
        $name = tnp_sanitize_palette_name((string) $colour['name']);
        $hex = strtolower((string) $colour['hex']);
        $rgb = trim((string) $colour['rgb']);

        $css .= "  --{$name}: {$hex};\n";
        $css .= "  --{$name}-rgb: {$rgb};\n\n";
    }

    $css .= "}\n\n";

    $utilities = array(
        'text' => array('property' => 'color', 'hover' => false),
        'border' => array('property' => 'border-color', 'hover' => false),
        'background' => array('property' => 'background-color', 'hover' => false),
        'hover-text' => array('property' => 'color', 'hover' => true),
        'hover-border' => array('property' => 'border-color', 'hover' => true),
        'hover-background' => array('property' => 'background-color', 'hover' => true),
    );

    foreach ($palette as $colour) {
        $name = tnp_sanitize_palette_name((string) $colour['name']);

        foreach ($utilities as $prefix => $utility) {
            $selector = '.' . $prefix . '-' . $name . ($utility['hover'] ? ':hover' : '');
            $css .= "{$selector} {\n  {$utility['property']}: var(--{$name});\n}\n\n";

            for ($opacity = 10; $opacity <= 90; $opacity += 10) {
                $selector = '.' . $prefix . '-' . $name . '-' . $opacity . ($utility['hover'] ? ':hover' : '');
                $css .= "{$selector} {\n  {$utility['property']}: rgb(var(--{$name}-rgb) / {$opacity}%);\n}\n\n";
            }
        }
    }

    return $css;
}

function tnp_maybe_generate_missing_css(): void
{
    $info = tnp_get_css_file_info();

    if (!$info['exists']) {
        tnp_generate_palette_css(tnp_get_palette());
    }
}
