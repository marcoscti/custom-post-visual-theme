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
        add_action('wp_enqueue_scripts', 'cpvt_enqueue_theme_assets');
        add_action('wp_head', 'cpvt_output_inline_css_fallback', 999);
    }
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

    $css = cpvt_generate_theme_css($cpvt_active_preset);
    if ($css) {
        // Use a unique handle for the inline styles
        wp_register_style('cpvt-inline-styles', false);
        wp_enqueue_style('cpvt-inline-styles');
        wp_add_inline_style('cpvt-inline-styles', $css);
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

    // Check if styles have already been enqueued
    if (empty($cpvt_active_preset) || wp_style_is('cpvt-inline-styles', 'enqueued')) {
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

    $custom_selector = trim(get_option('cpvt_target_selector', ''));
    $container_selector = !empty($custom_selector) ? $custom_selector : '.cpvt-theme-target';

    $css = '';
    $rules = [];

    // Sanitize and add background color if it exists.
    if (!empty($preset['background_color'])) {
        $css .= 'body { background-color: ' . sanitize_text_field($preset['background_color']) . ' !important; }';
    }
    
    // Sanitize position values, default to '0' if empty.
    $top_y = sanitize_text_field($preset['top_vertical_position'] ?? '0');
    $bottom_y = sanitize_text_field($preset['bottom_vertical_position'] ?? '0');
    $background_size = sanitize_text_field($preset['background_size'] ?? 'auto');
    if (empty($background_size)) {
        $background_size = 'auto';
    }

    // Background images
    $backgrounds = [];
    $positions = [];
    
    if (!empty($preset['top_left'])) {
        $backgrounds[] = "url('" . esc_url($preset['top_left']) . "')";
        $positions[] = 'left ' . $top_y;
    }
    if (!empty($preset['top_right'])) {
        $backgrounds[] = "url('" . esc_url($preset['top_right']) . "')";
        $positions[] = 'right ' . $top_y;
    }
    if (!empty($preset['bottom_left'])) {
        $backgrounds[] = "url('" . esc_url($preset['bottom_left']) . "')";
        $positions[] = 'left bottom ' . $bottom_y;
    }
    if (!empty($preset['bottom_right'])) {
        $backgrounds[] = "url('" . esc_url($preset['bottom_right']) . "')";
        $positions[] = 'right bottom ' . $bottom_y;
    }

    if (!empty($backgrounds)) {
        $rules[] = 'background-image: ' . implode(', ', $backgrounds) . ' !important;';
        $rules[] = 'background-position: ' . implode(', ', $positions) . ' !important;';
        $rules[] = 'background-repeat: no-repeat !important;';
        $rules[] = 'background-size: ' . $background_size . ' !important;';
        $rules[] = 'position: relative;';
    }

    if (!empty($rules)) {
        $css .= sprintf('%s { %s }', $container_selector, implode(' ', $rules));
    }

    $background_size_mobile = sanitize_text_field($preset['background_size_mobile'] ?? '');
    if (!empty($background_size_mobile)) {
        $css .= sprintf('@media (max-width: 768px) { %s { background-size: %s !important; } }', $container_selector, $background_size_mobile);
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

    // If a custom selector is defined, do nothing. The CSS will target it directly.
    $custom_selector = trim(get_option('cpvt_target_selector', ''));
    if (!empty($custom_selector)) {
        return;
    }

    // If no custom selector, inject script to find a target and add a class.
    ?>
    <script id="cpvt-mark-target-script">
    (function(){
        function markTarget() {
            var selectors = ['.elementor-location-single', '.site-main', '.site-content', '.content-area', '.entry-content', '.elementor-top-section', '.elementor-section:first-of-type'];
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