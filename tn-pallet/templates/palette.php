<?php
/** TN Pallet admin interface. @package TNPallet */
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap tnp-admin">
    <h1 class="screen-reader-text"><?php esc_html_e('Pallet', 'tn-pallet'); ?></h1>
    <?php if ($notice) : ?>
        <div class="notice notice-<?php echo esc_attr($notice['type']); ?> is-dismissible"><p><?php echo esc_html($notice['message']); ?></p></div>
    <?php endif; ?>
    <div id="tnp-feedback" class="notice" role="status" aria-live="polite" tabindex="-1" hidden><p></p></div>
    <header class="tnp-hero">
        <div class="tnp-hero-top">
            <span class="tnp-eyebrow"><?php esc_html_e('Colour. Organise. Apply.', 'tn-pallet'); ?></span>
            <span class="tnp-version"><span class="screen-reader-text"><?php esc_html_e('Version', 'tn-pallet'); ?> </span><?php echo esc_html('v' . TNP_VERSION); ?></span>
        </div>
        <h2><?php esc_html_e('Pallet', 'tn-pallet'); ?></h2>
        <div class="tnp-hero-bottom">
            <p><?php esc_html_e('One palette. Every part of your site.', 'tn-pallet'); ?></p>
            <ul class="tnp-capabilities" aria-label="<?php esc_attr_e('Palette capabilities', 'tn-pallet'); ?>">
                <li><?php esc_html_e('Grouped colours', 'tn-pallet'); ?></li>
                <li><?php esc_html_e('JSON import / export', 'tn-pallet'); ?></li>
                <li><?php esc_html_e('Generated CSS', 'tn-pallet'); ?></li>
                <?php if ($ai_available) : ?><li><?php esc_html_e('AI image interpretation', 'tn-pallet'); ?></li><?php endif; ?>
            </ul>
        </div>
    </header>
    <noscript><div class="notice notice-warning"><p><?php esc_html_e('Enable JavaScript to edit the palette. Your saved colours remain active.', 'tn-pallet'); ?></p></div></noscript>
    <div class="nav-tab-wrapper" role="tablist" aria-label="<?php esc_attr_e('Palette tools', 'tn-pallet'); ?>">
        <?php $tabs = array('palette' => __('Palette', 'tn-pallet'), 'json' => __('JSON', 'tn-pallet')); ?>
        <?php if ($ai_available) { $tabs['image'] = __('From image', 'tn-pallet'); } ?>
        <?php foreach ($tabs as $id => $label) : ?>
            <button type="button" role="tab" id="tnp-tab-<?php echo esc_attr($id); ?>" class="nav-tab<?php echo 'palette' === $id ? ' nav-tab-active' : ''; ?>" aria-controls="tnp-panel-<?php echo esc_attr($id); ?>" aria-selected="<?php echo 'palette' === $id ? 'true' : 'false'; ?>" tabindex="<?php echo 'palette' === $id ? '0' : '-1'; ?>"><?php echo esc_html($label); ?></button>
        <?php endforeach; ?>
    </div>
    <section id="tnp-panel-palette" role="tabpanel" aria-labelledby="tnp-tab-palette">
        <p><?php esc_html_e('Existing colour names stay fixed so your CSS and blocks keep working. Scroll each row for more colours.', 'tn-pallet'); ?></p>
        <div id="tnp-sections"></div>
        <form id="tnp-add-section" class="tnp-panel tnp-actions">
            <label for="tnp-section-name"><?php esc_html_e('New section name', 'tn-pallet'); ?></label>
            <input id="tnp-section-name" type="text" maxlength="80" required placeholder="<?php esc_attr_e('e.g. Brand', 'tn-pallet'); ?>">
            <button class="button" type="submit"><?php esc_html_e('Add section', 'tn-pallet'); ?></button>
        </form>
        <div class="tnp-panel">
            <label><input id="tnp-custom-colours" type="checkbox"> <?php esc_html_e('Allow custom editor colours', 'tn-pallet'); ?></label>
            <p class="tnp-actions"><button id="tnp-save" type="button" class="button button-primary"><?php esc_html_e('Save palette', 'tn-pallet'); ?></button> <span id="tnp-save-state" aria-live="polite"></span></p>
        </div>
    </section>
    <section id="tnp-panel-json" class="tnp-panel" role="tabpanel" aria-labelledby="tnp-tab-json" hidden>
        <h2><?php esc_html_e('View, transfer or update JSON', 'tn-pallet'); ?></h2>
        <p id="tnp-json-description"><?php esc_html_e('Copy this document to another site, or paste one here. Save JSON replaces this site’s palette, including its section names and editor setting. Colours omitted from an import are removed. Legacy flat arrays are also supported.', 'tn-pallet'); ?></p>
        <label for="tnp-json"><?php esc_html_e('Palette JSON', 'tn-pallet'); ?></label>
        <textarea id="tnp-json" rows="20" class="large-text code" spellcheck="false" aria-describedby="tnp-json-description"></textarea>
        <p class="tnp-actions">
            <button id="tnp-save-json" type="button" class="button button-primary"><?php esc_html_e('Save JSON', 'tn-pallet'); ?></button>
            <button id="tnp-copy-json" type="button" class="button"><?php esc_html_e('Copy JSON', 'tn-pallet'); ?></button>
            <button id="tnp-export-json" type="button" class="button"><?php esc_html_e('Download JSON', 'tn-pallet'); ?></button>
            <button id="tnp-previous-json" type="button" class="button"><?php esc_html_e('Load previous saved JSON', 'tn-pallet'); ?></button>
        </p>
        <p><?php esc_html_e('Loading the previous JSON only changes this editor. Select Save JSON to restore it.', 'tn-pallet'); ?></p>
        <?php $last_ai = get_option('tnp_palette_last_ai', array()); ?>
        <details id="tnp-last-ai" <?php echo empty($last_ai['json']) ? 'hidden' : ''; ?>>
            <summary><?php esc_html_e('Last AI response JSON', 'tn-pallet'); ?></summary>
            <label for="tnp-ai-json"><?php esc_html_e('Validated AI response', 'tn-pallet'); ?></label>
            <textarea id="tnp-ai-json" class="large-text code" rows="12" readonly><?php echo esc_textarea($last_ai['json'] ?? ''); ?></textarea>
        </details>
    </section>
    <?php if ($ai_available) : ?>
    <section id="tnp-panel-image" class="tnp-panel" role="tabpanel" aria-labelledby="tnp-tab-image" hidden>
        <h2><?php esc_html_e('Create a palette from a mockup', 'tn-pallet'); ?></h2>
        <p><?php esc_html_e('Upload or select a JPEG, PNG or WebP image, up to 10 MB. Uploads are stored in Media Library.', 'tn-pallet'); ?></p>
        <p><?php esc_html_e('Interpret and save sends this image and the saved palette to your configured WordPress AI provider; provider charges may apply. It updates matching colours and adds new ones while retaining colours not returned by AI. The result is saved automatically and can be reviewed in Palette and JSON.', 'tn-pallet'); ?></p>
        <p><a href="<?php echo esc_url(admin_url('options-connectors.php')); ?>"><?php esc_html_e('Manage WordPress connectors', 'tn-pallet'); ?></a></p>
        <button id="tnp-select-image" type="button" class="button"><?php esc_html_e('Choose or upload image', 'tn-pallet'); ?></button>
        <p id="tnp-image-name"></p>
        <img id="tnp-image-preview" alt="<?php esc_attr_e('Selected website mockup', 'tn-pallet'); ?>" hidden>
        <p><button id="tnp-interpret" type="button" class="button button-primary" disabled><?php esc_html_e('Interpret and save', 'tn-pallet'); ?></button></p>
    </section>
    <?php endif; ?>
    <details class="tnp-panel">
        <summary><?php esc_html_e('CSS and developer reference', 'tn-pallet'); ?></summary>
        <p><code>var(--base-1)</code> · <code>text-base-1</code> · <code>background-base-1-50</code></p>
        <p><?php esc_html_e('Open Help above for CSS examples, class patterns and your colour reference.', 'tn-pallet'); ?></p>
        <p><?php esc_html_e('Generated file:', 'tn-pallet'); ?> <code><?php echo esc_html($css_info['path']); ?></code></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('tnp_regenerate_css'); ?>
            <input type="hidden" name="action" value="tnp_regenerate_css">
            <?php submit_button(__('Regenerate CSS', 'tn-pallet'), 'secondary', 'submit', false); ?>
        </form>
    </details>
</div>
