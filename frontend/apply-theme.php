<?php
if (!defined('ABSPATH')) exit;

add_action('wp_enqueue_scripts', function () {

    if (!is_single()) return;

    global $post;
    $themes = get_option('cpvt_themes', []);

    $post_id = intval($post->ID);

    // Per-post preset takes precedence
    $post_preset = get_post_meta($post->ID, 'cpvt_theme', true);
    $preset = null;

    if ($post_preset && isset($themes[$post_preset])) {
        $preset = $themes[$post_preset];
    } else {
        // Find the first preset that targets this post ID
        foreach ($themes as $slug => $th) {
            if (empty($th['post_ids'])) continue;
            $ids = array_filter(array_map('intval', array_map('trim', explode(',', $th['post_ids']))));
            if (in_array($post_id, $ids, true)) {
                $preset = $th;
                break;
            }
        }
    }

    if (! $preset) return;

    wp_enqueue_style(
        'cpvt-theme',
        CPVT_URL . 'assets/css/theme.css',
        [],
        '1.0.2',
        "all"
    );

    $bg_color = isset($preset['bg_color']) ? esc_attr($preset['bg_color']) : '';
    $text_color = isset($preset['text_color']) ? esc_attr($preset['text_color']) : '';
    $title_color = isset($preset['title_color']) ? esc_attr($preset['title_color']) : '';

    $css = sprintf(
        'body.cpvt-theme { background-color: %s; color: %s; }',
        $bg_color,
        $text_color
    );

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

    $backgrounds = [];
    $positions = [];
    foreach (['top_left', 'top_right', 'bottom_left', 'bottom_right'] as $pos) {
        if (!empty($preset[$pos])) {
            $backgrounds[] = "url('" . esc_url($preset[$pos]) . "')";
            switch ($pos) {
                case 'top_left':
                    $positions[] = 'left top';
                    break;
                case 'top_right':
                    $positions[] = 'right top';
                    break;
                case 'bottom_left':
                    $positions[] = 'left bottom';
                    break;
                case 'bottom_right':
                    $positions[] = 'right bottom';
                    break;
            }
        }
    }

    if (!empty($backgrounds)) {
        $css .= 'body.cpvt-theme { background-image: ' . implode(', ', $backgrounds) . '; background-position: ' . implode(', ', $positions) . '; background-repeat: no-repeat; }';
        // also apply as fallback to Elementor sections
        $css .= 'body.cpvt-theme .elementor-top-section, body.cpvt-theme .elementor-section:first-of-type { background-image: ' . implode(', ', $backgrounds) . '; background-position: ' . implode(', ', $positions) . '; background-repeat: no-repeat; }';
    }

    wp_add_inline_style('cpvt-theme', $css);
});

// Fallback: inject inline CSS into head with high priority in case optimizers remove wp_add_inline_style output
add_action('wp_head', function () {
    if (!is_singular()) return;

    global $post;
    $themes = get_option('cpvt_themes', []);
    $post_id = intval($post->ID);

    // Determine applicable preset
    $preset = null;
    $post_preset = get_post_meta($post->ID, 'cpvt_theme', true);
    if ($post_preset && isset($themes[$post_preset])) {
        $preset = $themes[$post_preset];
    } else {
        foreach ($themes as $slug => $th) {
            if (empty($th['post_ids'])) continue;
            $ids = array_filter(array_map('intval', array_map('trim', explode(',', $th['post_ids']))));
            if (in_array($post_id, $ids, true)) {
                $preset = $th;
                break;
            }
        }
    }

    if (! $preset) return;

    $bg_color = isset($preset['bg_color']) ? esc_attr($preset['bg_color']) : '';
    $text_color = isset($preset['text_color']) ? esc_attr($preset['text_color']) : '';
    $title_color = isset($preset['title_color']) ? esc_attr($preset['title_color']) : '';

    $images = [];
    $positions = [];
    foreach (['top_left', 'top_right', 'bottom_left', 'bottom_right'] as $pos) {
        if (!empty($preset[$pos])) {
            $images[] = "url('" . esc_url($preset[$pos]) . "')";
            switch ($pos) {
                case 'top_left':
                    $positions[] = 'left top';
                    break;
                case 'top_right':
                    $positions[] = 'right top';
                    break;
                case 'bottom_left':
                    $positions[] = 'left bottom';
                    break;
                case 'bottom_right':
                    $positions[] = 'right bottom';
                    break;
            }
        }
    }

    $css = '';
    if ($title_color) {
        $selectors = [
            'body.cpvt-theme .entry-title',
            'body.cpvt-theme h1',
            'body.cpvt-theme .elementor-widget-heading .elementor-heading-title',
            'body.cpvt-theme .elementor-heading-title',
            'body.cpvt-theme .elementor-widget-container .elementor-heading-title',
        ];
        $css .= implode(', ', $selectors) . " { color: {$title_color} !important; }\n";
    }

    $bg_rules = [];
    if ($bg_color) $bg_rules[] = "background-color: {$bg_color} !important;";
    if (!empty($images)) $bg_rules[] = "background-image: " . implode(', ', $images) . " !important;";
    if ($bg_rules) {
        $bg_rules[] = "background-repeat: no-repeat !important;";
        //$bg_rules[] = "background-size: cover !important;";
        $css .= "body.cpvt-theme { " . implode(' ', $bg_rules) . " }\n";
        $css .= "body.cpvt-theme .elementor-top-section, body.cpvt-theme .elementor-section:first-of-type { " . implode(' ', $bg_rules) . " }\n";
    }

    if ($text_color) {
        $css .= "body.cpvt-theme { color: {$text_color} !important; }\n";
    }

    if ($css) {
        echo "<style id=\"cpvt-inline-css\">\n" . $css . "\n</style>\n";
    }
}, 999);

/**
 * Add a body class when the visual theme should be active (no need to wrap content)
 */
add_filter('body_class', function ($classes) {
    if (!is_singular()) return $classes;

    global $post;
    $themes = get_option('cpvt_themes', []);

    // Per-post preset takes precedence
    $post_preset = get_post_meta($post->ID, 'cpvt_theme', true);
    if ($post_preset && isset($themes[$post_preset])) {
        $classes[] = 'cpvt-theme';
        return $classes;
    }

    $post_id = intval($post->ID);
    foreach ($themes as $slug => $th) {
        if (empty($th['post_ids'])) continue;
        $ids = array_filter(array_map('intval', array_map('trim', explode(',', $th['post_ids']))));
        if (in_array($post_id, $ids, true)) {
            $classes[] = 'cpvt-theme';
            break;
        }
    }

    return $classes;
});
