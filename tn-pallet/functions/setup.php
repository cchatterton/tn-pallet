<?php
/**
 * Plugin setup and activation.
 *
 * @package TNPallet
 */

if (!defined('ABSPATH')) {
    exit;
}

function tnp_activate_plugin(): void
{
    $stored = get_option(TNP_OPTION_NAME, null);

    if (null === $stored || false === $stored || '' === $stored) {
        tnp_save_palette(tnp_get_default_palette());
    }

    tnp_generate_palette_css(tnp_get_palette());
}
