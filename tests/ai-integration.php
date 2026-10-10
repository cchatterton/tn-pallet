<?php
/** Real WP AI Client + official OpenAI provider; HTTP responses are fixtures, never paid calls. */
require $argv[1];
error_reporting(E_ALL & ~E_DEPRECATED);
wp_set_current_user(1);
require_once ABSPATH . 'wp-content/plugins/ai-provider-for-openai/plugin.php';
WordPress\OpenAiAiProvider\register_provider();
$registry = WordPress\AiClient\AiClient::defaultRegistry();
$registry->setProviderRequestAuthentication('openai', new WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication('tnp-test-placeholder'));
$reply = '{"version":1,"sections":[{"name":"Base","slug":"base","colours":[{"name":"base-1","hex":"#112233"}]}]}';
$mode = 'success';
$generated = 0;
$assertions = 0;
function tnp_ai_test($condition, $message) {
    global $assertions;
    if (!$condition) throw new RuntimeException($message);
    $assertions++;
}
add_filter('pre_http_request', function ($pre, $args, $url) use (&$mode, &$reply, &$generated) {
    if (strpos($url, 'api.openai.com/v1/models') !== false) {
        $body = array('data' => array(array('id' => 'gpt-4o')));
    } elseif (strpos($url, 'api.openai.com/v1/responses') !== false) {
        $generated++;
        tnp_ai_test(strpos($args['body'], 'input_image') !== false, 'Image supplied to real provider adapter');
        if ($mode === 'failure') return new WP_Error('http_request_failed', 'Fixture timeout');
        $body = array('id' => 'test-response', 'status' => 'completed', 'output' => array(array('type' => 'message', 'role' => 'assistant', 'content' => array(array('type' => 'output_text', 'text' => $mode === 'invalid' ? 'not json' : $reply)))));
    } else {
        return new WP_Error('blocked_test_http', 'Unexpected HTTP blocked');
    }
    return array('headers' => array('content-type' => 'application/json'), 'body' => wp_json_encode($body), 'response' => array('code' => 200, 'message' => 'OK'), 'cookies' => array());
}, 1, 3);
$before = tnp_get_document();
$last = get_option('tnp_palette_last_ai', null);
$upload = wp_upload_bits('tnp-fixture.png', null, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
$id = wp_insert_attachment(array('post_title' => 'TN Pallet test mockup', 'post_mime_type' => 'image/png', 'post_status' => 'inherit'), $upload['file']);
try {
    tnp_ai_test(tnp_ai_available(), 'Configured image-capable provider detected');
    tnp_ai_test($generated === 0, 'Capability probe does not generate');
    $request = new WP_REST_Request('POST');
    $request->set_param('attachment_id', $id);
    $request->set_param('revision', tnp_document_revision());
    $result = tnp_rest_interpret_image($request);
    if (is_wp_error($result)) throw new RuntimeException($result->get_error_message());
    tnp_ai_test($generated === 1, 'One explicit interpretation generates once');
    tnp_ai_test(tnp_get_palette()[0]['hex'] === '#112233', 'AI result saved automatically');
    tnp_ai_test(count(tnp_get_palette()) === count(array_merge(...array_column($before['sections'], 'colours'))), 'Unreturned colours preserved');
    tnp_ai_test(!empty(get_post_meta($id, '_tnp_palette_ai_json', true)), 'Validated JSON saved with media');
    tnp_ai_test(!empty($result['last_ai']['json']), 'AI JSON exposed alongside current document');
    foreach (array('invalid', 'failure') as $mode) {
        $raw = get_option(TNP_OPTION_NAME);
        $request->set_param('revision', tnp_document_revision());
        tnp_ai_test(is_wp_error(tnp_rest_interpret_image($request)), 'Bad AI result rejected: ' . $mode);
        tnp_ai_test($raw === get_option(TNP_OPTION_NAME), 'Failure preserves stored palette');
    }
    $request->set_param('attachment_id', 999999);
    tnp_ai_test(is_wp_error(tnp_rest_interpret_image($request)), 'Missing media rejected');
    echo "PASS: $assertions AI integration assertions on PHP " . PHP_VERSION . "\n";
} finally {
    tnp_commit_document($before, tnp_document_revision());
    wp_delete_attachment($id, true);
    if (null === $last) delete_option('tnp_palette_last_ai'); else update_option('tnp_palette_last_ai', $last, false);
}
