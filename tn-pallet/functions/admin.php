<?php
/**
 * Palette admin page.
 *
 * @package TNPallet
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', 'tnp_register_admin_page');
add_action('admin_post_tnp_save_palette', 'tnp_handle_save_palette');
add_action('admin_post_tnp_regenerate_css', 'tnp_handle_regenerate_css');
add_action('load-appearance_page_' . TNP_MENU_SLUG, 'tnp_register_help_tabs');

function tnp_register_admin_page(): void
{
    add_theme_page(
        __('Palette', 'tn-pallet'),
        __('Palette', 'tn-pallet'),
        'edit_theme_options',
        TNP_MENU_SLUG,
        'tnp_render_admin_page'
    );
}

function tnp_register_help_tabs(): void
{
    $screen = get_current_screen();

    if (!$screen) {
        return;
    }

    $screen->add_help_tab(
        array(
            'id' => 'tnp_palette_classes',
            'title' => __('Palette Classes', 'tn-pallet'),
            'content' => tnp_render_palette_classes_help(),
        )
    );

    $screen->set_help_sidebar(
        '<p><strong>' . esc_html__('Generated CSS', 'tn-pallet') . '</strong></p>' .
        '<p>' . esc_html(tnp_get_css_file_info()['path']) . '</p>'
    );
}

function tnp_handle_save_palette(): void
{
    if (!current_user_can('edit_theme_options')) {
        wp_die(esc_html__('You do not have permission to edit the palette.', 'tn-pallet'));
    }

    check_admin_referer('tnp_save_palette');

    $names = isset($_POST['tnp_name']) && is_array($_POST['tnp_name']) ? wp_unslash($_POST['tnp_name']) : array();
    $picker_colours = isset($_POST['tnp_picker']) && is_array($_POST['tnp_picker']) ? wp_unslash($_POST['tnp_picker']) : array();

    $result = tnp_prepare_submitted_palette($names, $picker_colours);

    if (is_wp_error($result)) {
        tnp_set_admin_notice($result->get_error_message(), 'error');
        tnp_redirect_to_palette_page();
    }

    tnp_save_palette($result);
    $generated = tnp_generate_palette_css($result);

    if (is_wp_error($generated)) {
        tnp_set_admin_notice($generated->get_error_message(), 'error');
    } else {
        tnp_set_admin_notice(__('Palette saved and CSS regenerated.', 'tn-pallet'), 'success');
    }

    tnp_redirect_to_palette_page();
}

function tnp_handle_regenerate_css(): void
{
    if (!current_user_can('edit_theme_options')) {
        wp_die(esc_html__('You do not have permission to regenerate palette CSS.', 'tn-pallet'));
    }

    check_admin_referer('tnp_regenerate_css');

    $generated = tnp_generate_palette_css(tnp_get_palette());

    if (is_wp_error($generated)) {
        tnp_set_admin_notice($generated->get_error_message(), 'error');
    } else {
        tnp_set_admin_notice(__('Palette CSS regenerated.', 'tn-pallet'), 'success');
    }

    tnp_redirect_to_palette_page();
}

function tnp_prepare_submitted_palette(array $names, array $picker_colours)
{
    $palette = array();
    $used_names = array();
    $row_count = max(count($names), count($picker_colours));

    for ($index = 0; $index < $row_count; $index++) {
        $raw_name = isset($names[$index]) && is_scalar($names[$index]) ? sanitize_text_field((string) $names[$index]) : '';
        $name = tnp_sanitize_palette_name($raw_name);
        $picker_colour = isset($picker_colours[$index]) && is_scalar($picker_colours[$index]) ? sanitize_text_field((string) $picker_colours[$index]) : '';
        $colour_value = $picker_colour;

        if ('' === trim($raw_name) && '' === trim($colour_value)) {
            continue;
        }

        if ('' === $name || !preg_match('/^[a-z][a-z0-9-]*$/', $name)) {
            return new WP_Error('tnp_invalid_name', sprintf(__('Row %d has an invalid colour name.', 'tn-pallet'), $index + 1));
        }

        if (isset($used_names[$name])) {
            return new WP_Error('tnp_duplicate_name', sprintf(__('The colour name "%s" is used more than once.', 'tn-pallet'), esc_html($name)));
        }

        $colour = tnp_parse_colour($colour_value);

        if (false === $colour) {
            return new WP_Error('tnp_invalid_colour', sprintf(__('Row %d has an invalid colour value.', 'tn-pallet'), $index + 1));
        }

        $used_names[$name] = true;
        $palette[] = array(
            'name' => $name,
            'hex' => $colour['hex'],
            'rgb' => $colour['rgb'],
        );
    }

    if (empty($palette)) {
        return new WP_Error('tnp_empty_palette', __('Add at least one valid palette colour before saving.', 'tn-pallet'));
    }

    return tnp_sort_palette_by_name($palette);
}

function tnp_render_admin_page(): void
{
    if (!current_user_can('edit_theme_options')) {
        wp_die(esc_html__('You do not have permission to edit the palette.', 'tn-pallet'));
    }

    $palette = tnp_get_palette();
    $css_info = tnp_get_css_file_info();
    $notice = tnp_get_admin_notice();
    ?>
    <div class="wrap tnp-admin">
        <h1><?php echo esc_html__('Palette', 'tn-pallet'); ?></h1>

        <?php if ($notice) : ?>
            <div class="notice notice-<?php echo esc_attr($notice['type']); ?> is-dismissible">
                <p><?php echo esc_html($notice['message']); ?></p>
            </div>
        <?php endif; ?>

        <div class="tnp-status">
            <p><strong><?php echo esc_html__('Configured colours:', 'tn-pallet'); ?></strong> <?php echo esc_html((string) count($palette)); ?></p>
            <p><strong><?php echo esc_html__('CSS file:', 'tn-pallet'); ?></strong> <?php echo esc_html($css_info['path']); ?></p>
            <p><strong><?php echo esc_html__('Last generated:', 'tn-pallet'); ?></strong> <?php echo $css_info['modified'] ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $css_info['modified'])) : esc_html__('Not generated yet', 'tn-pallet'); ?></p>
            <p><strong><?php echo esc_html__('Writable and available:', 'tn-pallet'); ?></strong> <?php echo esc_html($css_info['exists'] && $css_info['writable'] ? __('Yes', 'tn-pallet') : __('No', 'tn-pallet')); ?></p>
        </div>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('tnp_save_palette'); ?>
            <input type="hidden" name="action" value="tnp_save_palette">

            <div id="tnp-palette-rows" class="tnp-palette-grid">
                <?php foreach ($palette as $colour) : ?>
                    <?php tnp_render_palette_row($colour); ?>
                <?php endforeach; ?>
            </div>

            <p>
                <button type="button" class="button" id="tnp-add-colour"><?php echo esc_html__('Add Colour', 'tn-pallet'); ?></button>
                <?php submit_button(__('Save Palette', 'tn-pallet'), 'primary', 'submit', false); ?>
            </p>
        </form>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="tnp-regenerate-form">
            <?php wp_nonce_field('tnp_regenerate_css'); ?>
            <input type="hidden" name="action" value="tnp_regenerate_css">
            <?php submit_button(__('Regenerate CSS', 'tn-pallet'), 'secondary', 'submit', false); ?>
        </form>

        <script type="text/html" id="tmpl-tnp-palette-card">
            <?php tnp_render_palette_row(array('name' => '', 'hex' => '#000000', 'rgb' => '0 0 0')); ?>
        </script>
    </div>
    <?php
}

function tnp_render_palette_row(array $colour): void
{
    $name = isset($colour['name']) ? (string) $colour['name'] : '';
    $hex = isset($colour['hex']) ? (string) $colour['hex'] : '#000000';
    ?>
    <div class="tnp-palette-card">
        <div class="tnp-card-header">
            <label>
                <span class="screen-reader-text"><?php echo esc_html__('Colour name', 'tn-pallet'); ?></span>
                <input type="text" name="tnp_name[]" value="<?php echo esc_attr($name); ?>" pattern="[a-z][a-z0-9-]*" class="tnp-colour-name" required>
            </label>
            <button type="button" class="button-link-delete tnp-remove-colour" aria-label="<?php echo esc_attr__('Remove colour', 'tn-pallet'); ?>">
                <span class="dashicons dashicons-trash" aria-hidden="true"></span>
            </button>
        </div>
        <label class="screen-reader-text"><?php echo esc_html__('Colour picker', 'tn-pallet'); ?></label>
        <input type="text" name="tnp_picker[]" value="<?php echo esc_attr($hex); ?>" class="tnp-colour-picker">
    </div>
    <?php
}

function tnp_render_palette_classes_help(): string
{
    $palette = tnp_get_palette();

    if (empty($palette)) {
        return '<p>' . esc_html__('Save at least one palette colour to generate utility classes.', 'tn-pallet') . '</p>';
    }

    $first = tnp_sanitize_palette_name((string) ($palette[0]['name'] ?? ''));
    $names = array_map(
        static function (array $colour): string {
            return tnp_sanitize_palette_name((string) ($colour['name'] ?? ''));
        },
        $palette
    );
    $names = array_values(array_filter(array_unique($names)));

    $html = '<p>' . esc_html__('Use these generated classes in templates, blocks, or custom markup. Replace the example colour name with any configured palette name.', 'tn-pallet') . '</p>';
    $html .= '<p><strong>' . esc_html__('Example', 'tn-pallet') . '</strong></p>';
    $html .= '<pre><code>' . esc_html(tnp_get_palette_class_example($first)) . '</code></pre>';
    $html .= '<p><strong>' . esc_html__('Available Class Patterns', 'tn-pallet') . '</strong></p>';
    $html .= '<pre><code>' . esc_html(tnp_get_palette_class_patterns()) . '</code></pre>';
    $html .= '<p><strong>' . esc_html__('Configured Palette Classes', 'tn-pallet') . '</strong></p>';
    $html .= '<pre><code>' . esc_html(tnp_get_palette_class_reference($names)) . '</code></pre>';

    return $html;
}

function tnp_get_palette_class_example(string $name): string
{
    return implode(
        "\n",
        array(
            '<div class="text-' . $name . '">Text colour</div>',
            '<div class="text-' . $name . '-50">Text at 50% opacity</div>',
            '<div class="border-' . $name . '">Border colour</div>',
            '<div class="background-' . $name . '">Background colour</div>',
            '<a class="hover-text-' . $name . ' hover-background-' . $name . '-10" href="#">Hover states</a>',
        )
    );
}

function tnp_get_palette_class_patterns(): string
{
    return implode(
        "\n",
        array(
            '.text-{name}',
            '.text-{name}-10 through .text-{name}-90',
            '.border-{name}',
            '.border-{name}-10 through .border-{name}-90',
            '.background-{name}',
            '.background-{name}-10 through .background-{name}-90',
            '.hover-text-{name}:hover',
            '.hover-text-{name}-10 through .hover-text-{name}-90',
            '.hover-border-{name}:hover',
            '.hover-border-{name}-10 through .hover-border-{name}-90',
            '.hover-background-{name}:hover',
            '.hover-background-{name}-10 through .hover-background-{name}-90',
        )
    );
}

function tnp_get_palette_class_reference(array $names): string
{
    $lines = array();

    foreach ($names as $name) {
        $lines[] = $name;
        $lines[] = '  text-' . $name . ' text-' . $name . '-50';
        $lines[] = '  border-' . $name . ' border-' . $name . '-50';
        $lines[] = '  background-' . $name . ' background-' . $name . '-50';
        $lines[] = '  hover-text-' . $name . ' hover-border-' . $name . ' hover-background-' . $name;
        $lines[] = '';
    }

    return trim(implode("\n", $lines));
}

function tnp_set_admin_notice(string $message, string $type): void
{
    set_transient(
        'tnp_admin_notice_' . get_current_user_id(),
        array(
            'message' => $message,
            'type' => 'error' === $type ? 'error' : 'success',
        ),
        MINUTE_IN_SECONDS
    );
}

function tnp_get_admin_notice()
{
    $key = 'tnp_admin_notice_' . get_current_user_id();
    $notice = get_transient($key);
    delete_transient($key);

    return is_array($notice) ? $notice : false;
}

function tnp_redirect_to_palette_page(): void
{
    wp_safe_redirect(admin_url('themes.php?page=' . TNP_MENU_SLUG));
    exit;
}
