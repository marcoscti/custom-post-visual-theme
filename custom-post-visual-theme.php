<?php
/**
 * Plugin Name: Custom Post Visual Theme
 * Description: Aplica um tema visual personalizado a posts específicos com base no ID.
 * Version: 1.1.0
 * Author: Marcos Cordeiro
 * Author URI:        https://github.com/marcoscti
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 6.0
 */
if (!defined('ABSPATH')) exit;
define('CPVT_PATH', plugin_dir_path(__FILE__));
define('CPVT_URL', plugin_dir_url(__FILE__));

/**
 * Load translations and admin only when needed
 */
add_action('plugins_loaded', function () {
    load_plugin_textdomain('cpvt', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

if (is_admin()) {
    require_once CPVT_PATH . 'admin/settings-page.php';
}

require_once CPVT_PATH . 'frontend/apply-theme.php';

// Register post meta for post types to support ACF / Gutenberg UI (REST)
add_action('init', function () {
    if (function_exists('register_post_meta')) {
        $post_types = ['noticia', 'post'];
        foreach ($post_types as $pt) {
            register_post_meta($pt, 'cpvt_theme', [
                'show_in_rest' => true,
                'single' => true,
                'sanitize_callback' => 'sanitize_text_field',
            ]);
        }
    }
});
