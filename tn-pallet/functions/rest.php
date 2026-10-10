<?php
/** Authenticated palette JSON and image endpoints. @package TNPallet */
if (!defined('ABSPATH')) {
    exit;
}
add_action('rest_api_init', 'tnp_register_rest_routes');

function tnp_register_rest_routes(): void
{
    register_rest_route('tn-pallet/v1', '/palette', array(
        'methods' => 'POST', 'callback' => 'tnp_rest_save_document', 'permission_callback' => 'tnp_rest_permissions',
    ));
    register_rest_route('tn-pallet/v1', '/interpret', array(
        'methods' => 'POST', 'callback' => 'tnp_rest_interpret_image', 'permission_callback' => 'tnp_rest_image_permissions',
    ));
}

function tnp_rest_permissions($request)
{
    if (!current_user_can('edit_theme_options') || !wp_verify_nonce($request->get_header('X-WP-Nonce'), 'wp_rest')) {
        return new WP_Error('tnp_forbidden', __('You do not have permission, or your session expired. Reload and try again.', 'tn-pallet'), array('status' => 403));
    }
    return true;
}

function tnp_rest_image_permissions($request)
{
    $permission = tnp_rest_permissions($request);
    if (is_wp_error($permission)) {
        return $permission;
    }
    return current_user_can('upload_files') ? true : new WP_Error('tnp_forbidden', __('You do not have permission to upload images.', 'tn-pallet'), array('status' => 403));
}

function tnp_rest_save_document($request)
{
    $json = $request->get_param('document');
    $revision = $request->get_param('revision');
    if (!is_string($json) || !is_string($revision)) {
        return new WP_Error('tnp_request', __('Palette JSON and its saved revision are required.', 'tn-pallet'), array('status' => 400));
    }
    $document = tnp_parse_document($json);
    if (is_wp_error($document)) {
        $document->add_data(array('status' => 400));
        return $document;
    }
    $result = tnp_commit_document($document, $revision);
    if (is_wp_error($result)) {
        $result->add_data(array('status' => 409));
        return $result;
    }
    return tnp_document_response();
}

function tnp_document_response(): array
{
    return array('document' => tnp_get_document(), 'revision' => tnp_document_revision(), 'previous' => get_option('tnp_palette_previous_json', ''), 'last_ai' => get_option('tnp_palette_last_ai', array()));
}
