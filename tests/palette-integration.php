<?php
/** Run against disposable WordPress: php tests/palette-integration.php /path/to/wp-load.php */
require $argv[1];
error_reporting(E_ALL & ~E_DEPRECATED);
require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
require_once ABSPATH . 'wp-admin/includes/screen.php';
require_once ABSPATH . 'wp-admin/includes/template.php';
wp_set_current_user(1);
$assertions = 0;
function tnp_test($condition, $label) {
    global $assertions;
    if (!$condition) { throw new RuntimeException($label); }
    $assertions++;
}
$original = array();
foreach (array(TNP_OPTION_NAME, TNP_CUSTOM_EDITOR_COLOURS_OPTION, 'tnp_palette_sections', 'tnp_palette_previous_json') as $key) {
    $original[$key] = get_option($key, null);
}
try {
    delete_option(TNP_OPTION_NAME);
    delete_option('tnp_palette_sections');
    tnp_test(count(tnp_get_palette()) === 12, 'Defaults only when no saved items');
    tnp_test(array_column(tnp_get_document()['sections'], 'slug') === array('base', 'contrast', 'dark', 'light'), 'Default row order');
    update_option(TNP_OPTION_NAME, '[]', false);
    tnp_test(count(tnp_get_palette()) === 12, 'Empty stored palette uses defaults');
    $legacy = array(array('name' => 'base-1', 'hex' => '#123456', 'rgb' => '18 52 86'), array('name' => 'my-special', 'hex' => '#abcdef', 'rgb' => '171 205 239'), array('name' => 'brand-9', 'hex' => '#112233', 'rgb' => '17 34 51'));
    tnp_save_palette($legacy);
    $raw = get_option(TNP_OPTION_NAME);
    $css = tnp_build_palette_css(tnp_get_palette());
    $document = tnp_get_document();
    tnp_test(in_array('my', array_column($document['sections'], 'slug'), true), 'Groups split on first hyphen');
    tnp_test(count(tnp_get_palette()) === 3, 'Never inject missing defaults into customised palette');
    tnp_test(get_option(TNP_OPTION_NAME) === $raw, 'Reading does not migrate storage');
    tnp_test($document['sections'][0]['colours'][0]['hex'] === '#123456', 'Custom default value retained');
    $parsed = tnp_parse_document(wp_json_encode($document));
    tnp_test(!is_wp_error($parsed), 'Structured export round trip');
    tnp_test(true === tnp_commit_document($parsed, tnp_document_revision()), 'Document commits');
    tnp_test(tnp_build_palette_css(tnp_get_palette()) === $css, 'Legacy CSS output byte identical');
    tnp_test(get_option(TNP_OPTION_NAME) === $raw, 'Legacy flat storage is byte identical');
    tnp_test(count(tnp_get_editor_colour_palette()) === 3, 'Block editor retains legacy colours');
    tnp_test(!is_wp_error(tnp_parse_document($raw)), 'Legacy flat JSON imports');
    foreach (array('', '{}', '{"version":2,"sections":[]}', '[{"name":[],"hex":"#123456"}]', '[{"name":"bad!","hex":"#123456"}]', '[{"name":"valid","hex":"red"}]', '[{"name":"same","hex":"#123456"},{"name":"same","hex":"#123456"}]') as $bad) {
        tnp_test(is_wp_error(tnp_parse_document($bad)), 'Reject invalid JSON: ' . $bad);
    }
    $bad = $document;
    $bad['sections'][] = $bad['sections'][0];
    tnp_test(is_wp_error(tnp_parse_document(wp_json_encode($bad))), 'Reject duplicate section');
    tnp_test(is_wp_error(tnp_parse_document(str_repeat(' ', 262145))), 'Bound JSON size');
    $renamed = $document;
    $renamed['sections'][0]['name'] = 'Renamed display label';
    tnp_test(true === tnp_commit_document($renamed, tnp_document_revision()), 'Rename display label');
    tnp_test(tnp_get_document()['sections'][0]['colours'][0]['name'] === 'base-1', 'Display rename leaves CSS slug');
    tnp_test(is_wp_error(tnp_commit_document($document, 'old-revision')), 'Reject concurrent edit');
    tnp_test(get_option('tnp_palette_previous_json') === wp_json_encode($document, JSON_PRETTY_PRINT), 'Previous JSON available');
    $incoming = tnp_parse_document('[{"name":"base-1","hex":"#fedcba"},{"name":"new-1","hex":"#223344"}]');
    $merged = tnp_merge_ai_document($document, $incoming);
    tnp_test(count(array_merge(...array_column($merged['sections'], 'colours'))) === 4, 'AI merge retains unreturned custom items');
    tnp_test($merged['sections'][0]['colours'][0]['hex'] === '#fedcba', 'AI updates matching value');
    $upload_failure = function ($uploads) { $uploads['basedir'] = '/dev/null/tnp-test'; return $uploads; };
    add_filter('upload_dir', $upload_failure);
    $before_failure = get_option(TNP_OPTION_NAME);
    tnp_test(is_wp_error(tnp_commit_document($merged, tnp_document_revision())), 'CSS write failure rejects save');
    tnp_test(get_option(TNP_OPTION_NAME) === $before_failure, 'CSS failure preserves palette');
    remove_filter('upload_dir', $upload_failure);
    $request = new WP_REST_Request('POST', '/tn-pallet/v1/palette');
    tnp_test(is_wp_error(tnp_rest_permissions($request)), 'Nonce required');
    $request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));
    tnp_test(true === tnp_rest_permissions($request), 'Admin with nonce authorised');
    wp_set_current_user(0);
    tnp_test(is_wp_error(tnp_rest_permissions($request)), 'Anonymous forbidden');
    wp_set_current_user(1);
    $request->set_param('revision', tnp_document_revision());
    $request->set_param('document', 'invalid');
    tnp_test(is_wp_error(tnp_rest_save_document($request)), 'Invalid REST save rejected');
    tnp_test(get_option(TNP_OPTION_NAME) === $raw, 'Invalid save leaves palette untouched');
    $request->set_param('document', wp_json_encode($merged));
    tnp_test(is_array(tnp_rest_save_document($request)), 'Valid REST save returns document');
    tnp_test(file_get_contents(tnp_get_css_file_info()['path']) === tnp_build_palette_css(tnp_get_palette()), 'Published CSS matches storage');
    $route_request = new WP_REST_Request('POST', '/tn-pallet/v1/palette');
    tnp_test(rest_get_server()->dispatch($route_request)->get_status() === 403, 'REST route blocks missing nonce');
    $route_request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));
    $route_request->set_param('revision', tnp_document_revision());
    $route_request->set_param('document', 'invalid');
    tnp_test(rest_get_server()->dispatch($route_request)->get_status() === 400, 'REST route returns validation status');
    tnp_test(false === tnp_ai_available(), 'AI unavailable without configured connector');
    set_current_screen('appearance_page_tn-pallet');
    tnp_register_help_tabs();
    tnp_test(count(get_current_screen()->get_help_tabs()) === 6, 'Six separate help topics without AI');
    ob_start(); tnp_render_admin_page(); $html = ob_get_clean();
    tnp_test(false === strpos($html, 'id="tnp-panel-image"'), 'Absent connector hides image controls');
    tnp_test(false !== strpos($html, 'id="tnp-panel-json"'), 'JSON always present');
    require __DIR__ . '/controller-integration.php';
    echo "PASS: $assertions palette integration assertions on PHP " . PHP_VERSION . "\n";
} finally {
    foreach ($original as $key => $value) {
        if (null === $value) delete_option($key); else update_option($key, $value, false);
    }
    tnp_generate_palette_css(tnp_get_palette());
}
