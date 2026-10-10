<?php
/** Portable palette documents; the legacy flat option remains authoritative. @package TNPallet */
if (!defined('ABSPATH')) {
    exit;
}

function tnp_group_palette(array $palette): array
{
    $sections = array();
    foreach ($palette as $colour) {
        $slug = explode('-', $colour['name'], 2)[0];
        if (!isset($sections[$slug])) {
            $sections[$slug] = array('name' => tnp_format_palette_label($slug), 'slug' => $slug, 'colours' => array());
        }
        $sections[$slug]['colours'][] = $colour;
    }
    $ordered = array();
    foreach (array('base', 'contrast', 'dark', 'light') as $slug) {
        if (isset($sections[$slug])) {
            $ordered[] = $sections[$slug];
            unset($sections[$slug]);
        }
    }
    return array_merge($ordered, array_values($sections));
}

function tnp_get_document(): array
{
    $palette = tnp_get_palette();
    $metadata = get_option('tnp_palette_sections', array());
    $remaining = array_column($palette, null, 'name');
    $sections = array();
    foreach (is_array($metadata) ? $metadata : array() as $section) {
        $colours = array();
        foreach ($section['colours'] as $name) {
            if (isset($remaining[$name])) {
                $colours[] = $remaining[$name];
                unset($remaining[$name]);
            }
        }
        if ($colours) {
            $sections[] = array('name' => $section['name'], 'slug' => $section['slug'], 'colours' => $colours);
        }
    }
    foreach (tnp_group_palette(array_values($remaining)) as $group) {
        $found = false;
        foreach ($sections as &$section) {
            if ($section['slug'] === $group['slug']) {
                $section['colours'] = array_merge($section['colours'], $group['colours']);
                $found = true;
                break;
            }
        }
        unset($section);
        if (!$found) {
            $sections[] = $group;
        }
    }
    return array('version' => 1, 'sections' => $sections, 'allow_custom_editor_colours' => tnp_allow_custom_editor_colours());
}

function tnp_document_revision(): string
{
    return hash('sha256', wp_json_encode(tnp_get_document()));
}

/** Strict imports never silently discard malformed rows or change existing colour identifiers. */
function tnp_parse_document(string $json)
{
    if (strlen($json) > 262144) {
        return new WP_Error('tnp_size', __('Palette JSON must be smaller than 256 KB.', 'tn-pallet'));
    }
    $input = json_decode($json, true, 32);
    if (!is_array($input) || JSON_ERROR_NONE !== json_last_error() || !$input) {
        return new WP_Error('tnp_json', __('Enter a valid, non-empty palette JSON document.', 'tn-pallet'));
    }
    // Original TN Pallet JSON arrays can still be pasted directly.
    if (array_keys($input) === range(0, count($input) - 1)) {
        foreach ($input as $colour) {
            if (!is_array($colour) || !isset($colour['name']) || !is_string($colour['name'])) {
                return new WP_Error('tnp_colour', __('Every colour requires a name and hex value.', 'tn-pallet'));
            }
        }
        $input = array('version' => 1, 'sections' => tnp_group_palette($input));
    }
    if (1 !== ($input['version'] ?? null) || !isset($input['sections']) || !is_array($input['sections']) || !$input['sections'] || count($input['sections']) > 50) {
        return new WP_Error('tnp_schema', __('Use version 1 with between 1 and 50 sections.', 'tn-pallet'));
    }
    if (isset($input['allow_custom_editor_colours']) && !is_bool($input['allow_custom_editor_colours'])) {
        return new WP_Error('tnp_setting', __('allow_custom_editor_colours must be true or false.', 'tn-pallet'));
    }
    $sections = $slugs = $names = array();
    foreach ($input['sections'] as $section) {
        if (!is_array($section) || !isset($section['name'], $section['slug'], $section['colours']) || !is_string($section['name']) || !is_string($section['slug']) || !is_array($section['colours'])) {
            return new WP_Error('tnp_section', __('Each section requires a name, slug and colours array.', 'tn-pallet'));
        }
        $label = sanitize_text_field($section['name']);
        $slug = $section['slug'];
        if ('' === trim($label) || !preg_match('/^[a-z][a-z0-9-]*$/D', $slug) || isset($slugs[$slug]) || !$section['colours']) {
            return new WP_Error('tnp_section', __('Section names must be present and section slugs must be unique, lowercase identifiers starting with a letter.', 'tn-pallet'));
        }
        $slugs[$slug] = true;
        $colours = array();
        foreach ($section['colours'] as $colour) {
            if (!is_array($colour) || !isset($colour['name'], $colour['hex']) || !is_string($colour['name']) || !is_string($colour['hex']) || !preg_match('/^[a-z][a-z0-9-]*$/D', $colour['name']) || isset($names[$colour['name']])) {
                return new WP_Error('tnp_colour', __('Colour names must be unique, lowercase identifiers starting with a letter.', 'tn-pallet'));
            }
            if (!preg_match('/^#[0-9a-fA-F]{6}$/D', $colour['hex'])) {
                return new WP_Error('tnp_colour', __('Each colour requires a six-digit hex value, for example #002840.', 'tn-pallet'));
            }
            $parsed = tnp_parse_colour($colour['hex']);
            $colours[] = array_merge(array('name' => $colour['name']), $parsed);
            $names[$colour['name']] = true;
            if (count($names) > 300) {
                return new WP_Error('tnp_size', __('A palette can contain up to 300 colours.', 'tn-pallet'));
            }
        }
        $sections[] = array('name' => $label, 'slug' => $slug, 'colours' => $colours);
    }
    return array('version' => 1, 'sections' => $sections, 'allow_custom_editor_colours' => $input['allow_custom_editor_colours'] ?? tnp_allow_custom_editor_colours());
}

/** Save all entry points through one validation/write path, retaining a portable recovery copy. */
function tnp_commit_document(array $document, string $revision)
{
    if (!add_option('tnp_palette_write_lock', time(), '', false)) {
        if ((int) get_option('tnp_palette_write_lock') < time() - 120) {
            delete_option('tnp_palette_write_lock');
        }
        return new WP_Error('tnp_busy', __('Another palette save is in progress. Please retry.', 'tn-pallet'));
    }
    try {
        if (!hash_equals(tnp_document_revision(), $revision)) {
            return new WP_Error('tnp_conflict', __('The saved palette changed in another window. Copy your JSON, reload, and try again.', 'tn-pallet'));
        }
        $previous = tnp_get_document();
        $palette = $metadata = array();
        foreach ($document['sections'] as $section) {
            $palette = array_merge($palette, $section['colours']);
            $metadata[] = array('name' => $section['name'], 'slug' => $section['slug'], 'colours' => array_column($section['colours'], 'name'));
        }
        $generated = tnp_generate_palette_css($palette);
        if (is_wp_error($generated)) {
            return $generated;
        }
        update_option('tnp_palette_previous_json', wp_json_encode($previous, JSON_PRETTY_PRINT), false);
        tnp_save_palette($palette);
        update_option('tnp_palette_sections', $metadata, false);
        tnp_save_custom_editor_colours_setting($document['allow_custom_editor_colours']);
        if (tnp_get_document() !== $document) {
            // Verify persisted state; never report success when an option write failed.
            $old_palette = array();
            $old_metadata = array();
            foreach ($previous['sections'] as $section) {
                $old_palette = array_merge($old_palette, $section['colours']);
                $old_metadata[] = array('name' => $section['name'], 'slug' => $section['slug'], 'colours' => array_column($section['colours'], 'name'));
            }
            tnp_save_palette($old_palette);
            update_option('tnp_palette_sections', $old_metadata, false);
            tnp_save_custom_editor_colours_setting($previous['allow_custom_editor_colours']);
            tnp_generate_palette_css($old_palette);
            return new WP_Error('tnp_storage', __('The palette could not be fully saved. The previous JSON is available for recovery.', 'tn-pallet'));
        }
        tnp_clear_editor_palette_caches();
        return true;
    } finally {
        delete_option('tnp_palette_write_lock');
    }
}
