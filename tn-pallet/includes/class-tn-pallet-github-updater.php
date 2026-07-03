<?php
/**
 * GitHub release updater.
 *
 * @package TNPallet
 */

if (!defined('ABSPATH')) {
    exit;
}

class TNP_GitHub_Updater
{
    private const OWNER = 'cchatterton';
    private const REPO = 'tn-pallet';
    private const SLUG = 'tn-pallet';
    private const ASSET_NAME = 'tn-pallet.zip';
    private const RELEASE_TRANSIENT = 'tnp_github_latest_release';
    private const ERROR_TRANSIENT = 'tnp_github_latest_release_error';

    public function register(): void
    {
        add_filter('pre_set_site_transient_update_plugins', array($this, 'add_update_data'));
        add_filter('site_transient_update_plugins', array($this, 'add_update_data'));
        add_filter('update_plugins_github.com', array($this, 'get_update_uri_data'), 10, 4);
        add_filter('plugins_api', array($this, 'plugin_information'), 10, 3);
        add_filter('plugin_row_meta', array($this, 'plugin_row_meta'), 10, 2);
        add_action('admin_init', array($this, 'handle_manual_update_check'));
        add_action('upgrader_process_complete', array($this, 'clear_cache_after_update'), 10, 2);
    }

    public function add_update_data($transient)
    {
        if (!is_object($transient)) {
            $transient = new stdClass();
        }

        $transient->response = isset($transient->response) && is_array($transient->response) ? $transient->response : array();
        $transient->no_update = isset($transient->no_update) && is_array($transient->no_update) ? $transient->no_update : array();

        $plugin_file = plugin_basename(TNP_PLUGIN_FILE);
        $release = $this->get_latest_release($this->is_forced_update_check());

        unset($transient->response[$plugin_file]);
        unset($transient->no_update[$plugin_file]);

        if (!$release || empty($release['version']) || empty($release['download_url'])) {
            return $transient;
        }

        if (version_compare($release['version'], TNP_VERSION, '>')) {
            $transient->response[$plugin_file] = (object) array(
                'id' => TNP_GITHUB_REPO_URL,
                'slug' => self::SLUG,
                'plugin' => $plugin_file,
                'new_version' => $release['version'],
                'url' => $release['html_url'],
                'package' => $release['download_url'],
                'requires' => '6.0',
                'requires_php' => '8.1',
            );
        }

        return $transient;
    }

    public function get_update_uri_data($update, array $plugin_data, string $plugin_file, array $locales)
    {
        unset($locales);

        if (plugin_basename(TNP_PLUGIN_FILE) !== $plugin_file) {
            return $update;
        }

        if (empty($plugin_data['UpdateURI']) || TNP_GITHUB_REPO_URL !== untrailingslashit((string) $plugin_data['UpdateURI'])) {
            return $update;
        }

        $release = $this->get_latest_release($this->is_forced_update_check());

        if (!$release || empty($release['version']) || empty($release['download_url'])) {
            return false;
        }

        return array(
            'version' => $release['version'],
            'slug' => self::SLUG,
            'url' => $release['html_url'],
            'package' => $release['download_url'],
            'requires' => '6.0',
            'requires_php' => '8.1',
        );
    }

    public function plugin_information($result, string $action, object $args)
    {
        if ('plugin_information' !== $action || empty($args->slug) || self::SLUG !== $args->slug) {
            return $result;
        }

        $release = $this->get_latest_release(false);

        if (!$release || empty($release['download_url'])) {
            return $result;
        }

        return (object) array(
            'name' => 'TN Pallet',
            'slug' => self::SLUG,
            'version' => $release['version'],
            'author' => 'Techn',
            'homepage' => TNP_GITHUB_REPO_URL,
            'download_link' => $release['download_url'],
            'requires' => '6.0',
            'requires_php' => '8.1',
            'sections' => array(
                'description' => __('Manage a named colour palette and generated utility CSS from WordPress admin.', 'tn-pallet'),
                'changelog' => isset($release['body']) ? wp_kses_post($release['body']) : '',
            ),
        );
    }

    public function plugin_row_meta(array $links, string $file): array
    {
        if (plugin_basename(TNP_PLUGIN_FILE) !== $file) {
            return $links;
        }

        $links[] = '<a href="' . esc_url(TNP_GITHUB_REPO_URL) . '">' . esc_html__('GitHub', 'tn-pallet') . '</a>';

        if (current_user_can('update_plugins')) {
            $plugins_url = is_multisite() ? network_admin_url('plugins.php') : admin_url('plugins.php');
            $check_url = wp_nonce_url(
                add_query_arg('tnp_check_updates', '1', $plugins_url),
                'tnp_check_updates'
            );
            $links[] = '<a href="' . esc_url($check_url) . '">' . esc_html__('Check for updates', 'tn-pallet') . '</a>';
        }

        return $links;
    }

    public function handle_manual_update_check(): void
    {
        if (empty($_GET['tnp_check_updates'])) {
            return;
        }

        if (!current_user_can('update_plugins')) {
            wp_die(esc_html__('You do not have permission to check plugin updates.', 'tn-pallet'));
        }

        check_admin_referer('tnp_check_updates');

        $this->clear_release_cache();
        delete_site_transient('update_plugins');
        wp_update_plugins();

        wp_safe_redirect(is_multisite() ? network_admin_url('plugins.php') : admin_url('plugins.php'));
        exit;
    }

    public function clear_cache_after_update($upgrader, array $hook_extra): void
    {
        if (empty($hook_extra['type']) || 'plugin' !== $hook_extra['type']) {
            return;
        }

        if (empty($hook_extra['plugins']) || !is_array($hook_extra['plugins'])) {
            return;
        }

        if (in_array(plugin_basename(TNP_PLUGIN_FILE), $hook_extra['plugins'], true)) {
            $this->clear_release_cache();
        }
    }

    private function get_latest_release(bool $force)
    {
        if ($force) {
            $this->clear_release_cache();
        }

        $cached = get_site_transient(self::RELEASE_TRANSIENT);

        if (is_array($cached)) {
            return $cached;
        }

        $release = $this->request_latest_release_from_api();

        if (!$release) {
            $release = $this->request_latest_release_from_redirect();
        }

        if (!$release || empty($release['version']) || empty($release['download_url'])) {
            delete_site_transient(self::RELEASE_TRANSIENT);
            return false;
        }

        $ttl = version_compare($release['version'], TNP_VERSION, '>') ? 6 * HOUR_IN_SECONDS : 5 * MINUTE_IN_SECONDS;
        set_site_transient(self::RELEASE_TRANSIENT, $release, $ttl);
        delete_site_transient(self::ERROR_TRANSIENT);

        return $release;
    }

    private function request_latest_release_from_api()
    {
        $response = wp_remote_get(
            'https://api.github.com/repos/' . self::OWNER . '/' . self::REPO . '/releases/latest',
            array(
                'timeout' => 10,
                'headers' => array(
                    'Accept' => 'application/vnd.github+json',
                    'User-Agent' => 'TN-Pallet/' . TNP_VERSION,
                ),
            )
        );

        if (is_wp_error($response)) {
            $this->store_error('wp_error', 0, $response->get_error_message(), '');
            return false;
        }

        $code = (int) wp_remote_retrieve_response_code($response);

        if (200 !== $code) {
            $this->store_error(
                'http_error',
                $code,
                wp_remote_retrieve_response_message($response),
                substr(wp_remote_retrieve_body($response), 0, 500)
            );
            return false;
        }

        $release = json_decode(wp_remote_retrieve_body($response), true);

        if (!is_array($release)) {
            $this->store_error('json_error', $code, __('The GitHub release response could not be decoded.', 'tn-pallet'), '');
            return false;
        }

        $version = $this->normalise_version((string) ($release['tag_name'] ?? ''));
        $download_url = $this->find_asset_download_url($release);

        if ('' === $version || '' === $download_url) {
            return false;
        }

        return array(
            'version' => $version,
            'download_url' => $download_url,
            'html_url' => esc_url_raw((string) ($release['html_url'] ?? TNP_GITHUB_REPO_URL)),
            'body' => (string) ($release['body'] ?? ''),
        );
    }

    private function request_latest_release_from_redirect()
    {
        $response = wp_remote_get(
            TNP_GITHUB_REPO_URL . '/releases/latest',
            array(
                'timeout' => 10,
                'redirection' => 0,
                'headers' => array(
                    'User-Agent' => 'TN-Pallet/' . TNP_VERSION,
                ),
            )
        );

        if (is_wp_error($response)) {
            $this->store_error('wp_error', 0, $response->get_error_message(), '');
            return false;
        }

        $location = wp_remote_retrieve_header($response, 'location');

        if (!is_string($location) || !preg_match('#/releases/tag/([^/?#]+)#', $location, $matches)) {
            return false;
        }

        $version = $this->normalise_version(rawurldecode($matches[1]));

        if ('' === $version) {
            return false;
        }

        $asset_url = TNP_GITHUB_REPO_URL . '/releases/download/v' . $version . '/' . self::ASSET_NAME;

        if (!$this->asset_is_reachable($asset_url)) {
            $asset_url = TNP_GITHUB_REPO_URL . '/releases/download/' . $version . '/' . self::ASSET_NAME;
        }

        if (!$this->asset_is_reachable($asset_url)) {
            return false;
        }

        return array(
            'version' => $version,
            'download_url' => esc_url_raw($asset_url),
            'html_url' => esc_url_raw(TNP_GITHUB_REPO_URL . '/releases/tag/v' . $version),
            'body' => '',
        );
    }

    private function find_asset_download_url(array $release): string
    {
        if (empty($release['assets']) || !is_array($release['assets'])) {
            return '';
        }

        foreach ($release['assets'] as $asset) {
            if (!is_array($asset)) {
                continue;
            }

            if (self::ASSET_NAME === ($asset['name'] ?? '') && !empty($asset['browser_download_url'])) {
                return esc_url_raw((string) $asset['browser_download_url']);
            }
        }

        return '';
    }

    private function asset_is_reachable(string $url): bool
    {
        $response = wp_remote_head(
            $url,
            array(
                'timeout' => 10,
                'redirection' => 5,
                'headers' => array(
                    'User-Agent' => 'TN-Pallet/' . TNP_VERSION,
                ),
            )
        );

        if (is_wp_error($response)) {
            return false;
        }

        $code = (int) wp_remote_retrieve_response_code($response);

        return $code >= 200 && $code < 400;
    }

    private function normalise_version(string $tag): string
    {
        $version = ltrim($tag, 'vV');

        return preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version) ? $version : '';
    }

    private function is_forced_update_check(): bool
    {
        if (!current_user_can('update_plugins')) {
            return false;
        }

        $request = array_merge($_GET, $_POST);
        $action = isset($request['action']) ? sanitize_key((string) wp_unslash($request['action'])) : '';

        return isset($request['force-check'])
            || in_array($action, array('update-selected', 'upgrade-plugin', 'do-plugin-upgrade'), true);
    }

    private function store_error(string $type, int $code, string $message, string $body): void
    {
        set_site_transient(
            self::ERROR_TRANSIENT,
            array(
                'type' => $type,
                'code' => $code,
                'message' => $message,
                'body' => $body,
                'checked_at' => time(),
            ),
            10 * MINUTE_IN_SECONDS
        );
        delete_site_transient(self::RELEASE_TRANSIENT);
    }

    private function clear_release_cache(): void
    {
        delete_site_transient(self::RELEASE_TRANSIENT);
        delete_site_transient(self::ERROR_TRANSIENT);
    }
}
