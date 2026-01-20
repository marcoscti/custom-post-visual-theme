<?php
if (!defined('ABSPATH')) exit;

// Global variable to hold the active theme preset for the current page load.
global $cpvt_active_preset;
$cpvt_active_preset = false;

/**
 * Sets up the theme context on the 'wp' action.
 *
 * This hook runs after the main WordPress query is resolved, so conditional tags
 * like is_singular() and the global $post object are reliably available.
 * It checks if a visual theme should be applied and stores the preset data.
 */
add_action('wp', 'cpvt_setup_theme_context');
function cpvt_setup_theme_context() {
    global $cpvt_active_preset;

    if (!is_singular()) {
        return;
    }

    global $post;
    if (!isset($post->ID)) {
        return;
    }

    $themes = get_option('cpvt_themes', []);
    if (empty($themes)) {
        return;
    }

    $post_id = intval($post->ID);
    $found_preset = false;

    // Per-post preset meta field takes precedence.
    $post_preset_slug = get_post_meta($post_id, 'cpvt_theme', true);

    if ($post_preset_slug && !empty($themes[$post_preset_slug])) {
        $found_preset = $themes[$post_preset_slug];
    } else {
        // Fallback: Check if the post ID is targeted by any theme's bulk settings.
        foreach ($themes as $slug => $theme_data) {
            if (empty($theme_data['post_ids'])) continue;

            $target_ids = array_filter(array_map('intval', array_map('trim', explode(',', $theme_data['post_ids']))));
            if (in_array($post_id, $target_ids, true)) {
                $found_preset = $theme_data;
                break;
            }
        }
    }

    if ($found_preset) {
        $cpvt_active_preset = $found_preset;
        // If a theme is active for this page, add the necessary hooks to apply it.
        add_filter('body_class', 'cpvt_add_theme_body_class');
        add_action('wp_enqueue_scripts', 'cpvt_enqueue_theme_assets');
        add_action('wp_head', 'cpvt_output_inline_css_fallback', 999);
    }
}

/**
 * Adds the 'cpvt-theme' class to the body tag.
 *
 * This function is only hooked if a theme is determined to be active.
 *
 * @param array $classes An array of body classes.
 * @return array The modified array of body classes.
 */
function cpvt_add_theme_body_class($classes) {
    $classes[] = 'cpvt-theme';
    return $classes;
}

/**
 * Enqueues theme stylesheet and adds inline styles.
 *
 * This function is only hooked if a theme is determined to be active.
 */
function cpvt_enqueue_theme_assets() {
    global $cpvt_active_preset;

    if (empty($cpvt_active_preset)) {
        return;
    }

    wp_enqueue_style('cpvt-theme', CPVT_URL . 'assets/css/theme.css', [], '1.0.5', 'all');

    $css = cpvt_generate_theme_css($cpvt_active_preset);
    if ($css) {
        wp_add_inline_style('cpvt-theme', $css);
    }
}

/**
 * Outputs inline CSS as a fallback.
 *
 * This is a fallback for optimization plugins that might strip out styles
 * added by wp_add_inline_style. It runs late in the wp_head action.
 */
function cpvt_output_inline_css_fallback() {
    global $cpvt_active_preset;

    if (empty($cpvt_active_preset) || did_action('wp_enqueue_scripts')) {
        return;
    }
    
    $css = cpvt_generate_theme_css($cpvt_active_preset);
    if ($css) {
        echo '<style id="cpvt-inline-css-fallback">' . $css . '</style>';
    }
}

/**
 * Generates the dynamic CSS rules based on a theme preset.
 *
 * @param array $preset The theme preset data.
 * @return string The generated CSS rules.
 */
function cpvt_generate_theme_css($preset) {
    if (empty($preset)) {
        return '';
    }

    $bg_color    = !empty($preset['bg_color']) ? esc_attr($preset['bg_color']) : '';
    $text_color  = !empty($preset['text_color']) ? esc_attr($preset['text_color']) : '';
    $title_color = !empty($preset['title_color']) ? esc_attr($preset['title_color']) : '';

    $container_selector = 'body.cpvt-theme';
    $css = '';
    $rules = [];

    // Background color and text color for the main container
    if ($bg_color)   $rules[] = "background-color: {$bg_color} !important;";
    if ($text_color) $rules[] = "color: {$text_color} !important;";

    // Background images
    $backgrounds = [];
    $positions   = [];
    foreach (['top_left', 'top_right', 'bottom_left', 'bottom_right'] as $pos) {
        if (!empty($preset[$pos])) {
            $backgrounds[] = "url('" . esc_url($preset[$pos]) . "')";
            $positions[] = str_replace('_', ' ', $pos); // 'top_left' -> 'top left'
        }
    }

    if (!empty($backgrounds)) {
        $rules[] = 'background-image: ' . implode(', ', $backgrounds) . ' !important;';
        $rules[] = 'background-position: ' . implode(', ', $positions) . ' !important;';
        $rules[] = 'background-repeat: no-repeat !important;';
        $rules[] = 'background-size: contain !important;';
    }

    if (!empty($rules)) {
        $css .= sprintf('%s { %s }', $container_selector, implode(' ', $rules));
    }

    // Title color for various common title selectors
    if ($title_color) {
        $selectors = [
            'body.cpvt-theme .entry-title',
            'body.cpvt-theme .post-title',
            'body.cpvt-theme h1.entry-title',
            'body.cpvt-theme .entry-header .entry-title',
            'body.cpvt-theme .entry-header h1',
            'body.cpvt-theme h1',
            'body.cpvt-theme .elementor-widget-heading .elementor-heading-title',
            'body.cpvt-theme .elementor-heading-title',
            'body.cpvt-theme .elementor-widget-container .elementor-heading-title',
        ];
        $css .= sprintf('%s { color: %s !important; }', implode(', ', $selectors), $title_color);
    }

    return $css;
}

// The JavaScript for marking a theme target is not dependent on the preset data,
// so it can remain as it was. It helps scope the theme to a specific content area.
add_action('wp_head', 'cpvt_mark_theme_target_script', 1);
function cpvt_mark_theme_target_script() {
    // Only run this if a theme is actually active.
    global $cpvt_active_preset;
    if (empty($cpvt_active_preset)) {
        return;
    }
    ?>
    <script id="cpvt-mark-target-script">
    (function(){
        function markTarget() {
            var selectors = ['.site-main', '.site-content', '.content-area', '.entry-content', '.elementor-top-section', '.elementor-section:first-of-type'];
            for (var i = 0; i < selectors.length; i++) {
                try {
                    var el = document.querySelector(selectors[i]);
                    if (el) {
                        el.classList.add('cpvt-theme-target');
                        return true;
                    }
                } catch (e) { /* Ignore invalid selectors */ }
            }
            return false;
        }

        // Try immediately. If DOM isn't ready, wait for it.
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', markTarget);
        } else {
            markTarget();
        }
    })();
    </script>
    <?php
}