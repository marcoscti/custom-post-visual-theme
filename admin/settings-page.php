<?php
if (!defined('ABSPATH')) exit;

/**
 * Registra a página de configurações
 */
add_action('admin_menu', function () {
    add_options_page(
        __('Post Visual Theme', 'cpvt'),
        __('Post Visual Theme', 'cpvt'),
        'manage_options',
        'custom-post-visual-theme',
        'cpvt_render_settings_page'
    );
});

/**
 * Registra scripts do admin
 */
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'settings_page_custom-post-visual-theme') return;

    wp_enqueue_media();
    wp_enqueue_script(
        'cpvt-admin',
        CPVT_URL . 'assets/js/admin.js',
        ['jquery'],
        '1.0',
        true
    );
});

/**
 * Renderiza a página
 */
function cpvt_render_settings_page()
{
    if (! current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Custom Post Visual Theme', 'cpvt'); ?></h1>

        <form method="post" action="options.php">
            <?php
            settings_fields('cpvt_settings_group');
            do_settings_sections('custom-post-visual-theme');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Registra settings
 */
add_action('admin_init', function () {

    $positions = [
        'top_left' => 'Imagem Superior Esquerda',
        'top_right' => 'Imagem Superior Direita',
        'bottom_left' => 'Imagem Inferior Esquerda',
        'bottom_right' => 'Imagem Inferior Direita',
    ];

    foreach ($positions as $key => $label) {
        // image fields are handled per-preset now
    }

    // Register theme presets option
    register_setting('cpvt_settings_group', 'cpvt_themes', 'cpvt_sanitize_themes');

    add_settings_section(
        'cpvt_themes',
        __('Presets de Tema', 'cpvt'),
        null,
        'custom-post-visual-theme'
    );

    add_settings_field(
        'cpvt_themes',
        __('Crie uma personalização', 'cpvt'),
        'cpvt_field_themes',
        'custom-post-visual-theme',
        'cpvt_themes'
    );
});

/**
 * Render the presets UI
 */
function cpvt_field_themes()
{
    $themes = get_option('cpvt_themes', []);
    ?>
    <div id="cpvt-themes">
        <style>
            .cpvt-themes-list { display:flex; flex-direction:column; gap:1rem; margin-top:1rem; }
            .cpvt-theme-item { border:1px solid #e1e1e1; padding:12px; border-radius:6px; background:#fff; box-shadow:0 1px 2px rgba(0,0,0,0.02); }
            .cpvt-theme-item p { margin:0 0 8px; }
            .cpvt-pos-label { font-weight:600; display:block; margin-bottom:4px; }
            .cpvt-pos-desc { font-size:12px; color:#666; margin-left:4px; display:block; }
        </style>
        <p><button type="button" class="button cpvt-add-theme"><?php echo esc_html__('Adicionar Preset', 'cpvt'); ?></button></p>
        <div class="cpvt-themes-list">
            <?php foreach ($themes as $slug => $th) : ?>
                <div class="cpvt-theme-item">
                    <input type="hidden" name="cpvt_themes[<?php echo esc_attr($slug); ?>][slug]" value="<?php echo esc_attr($slug); ?>">
                    <p><label><?php echo esc_html__('Rótulo', 'cpvt'); ?>: <input type="text" name="cpvt_themes[<?php echo esc_attr($slug); ?>][label]" value="<?php echo esc_attr($th['label'] ?? ''); ?>"></label></p>
                    <p><label><?php echo esc_html__('Cor do Background', 'cpvt'); ?>: <input type="color" name="cpvt_themes[<?php echo esc_attr($slug); ?>][bg_color]" value="<?php echo esc_attr($th['bg_color'] ?? ''); ?>"></label></p>
                    <p><label><?php echo esc_html__('Cor do Texto', 'cpvt'); ?>: <input type="color" name="cpvt_themes[<?php echo esc_attr($slug); ?>][text_color]" value="<?php echo esc_attr($th['text_color'] ?? ''); ?>"></label></p>
                    <p><label><?php echo esc_html__('Cor do Título', 'cpvt'); ?>: <input type="color" name="cpvt_themes[<?php echo esc_attr($slug); ?>][title_color]" value="<?php echo esc_attr($th['title_color'] ?? ''); ?>"></label></p>
                    <p><label><?php echo esc_html__('IDs dos Posts (vírgula separados)', 'cpvt'); ?>: <input type="text" name="cpvt_themes[<?php echo esc_attr($slug); ?>][post_ids]" value="<?php echo esc_attr($th['post_ids'] ?? ''); ?>" class="regular-text"></label></p>
                    <?php
                    $positions_map = [
                        'top_left' => __('Superior Esquerda', 'cpvt'),
                        'top_right' => __('Superior Direita', 'cpvt'),
                        'bottom_left' => __('Inferior Esquerda', 'cpvt'),
                        'bottom_right' => __('Inferior Direita', 'cpvt'),
                    ];
                    $positions_desc = [
                        'top_left' => __('Imagem aplicada no canto superior esquerdo do background', 'cpvt'),
                        'top_right' => __('Imagem aplicada no canto superior direito do background', 'cpvt'),
                        'bottom_left' => __('Imagem aplicada no canto inferior esquerdo do background', 'cpvt'),
                        'bottom_right' => __('Imagem aplicada no canto inferior direito do background', 'cpvt'),
                    ];
                    foreach ($positions_map as $pos_key => $label) : ?>
                        <p>
                            <span class="cpvt-pos-label"><?php echo esc_html($label); ?></span>
                            <span class="cpvt-pos-desc"><?php echo esc_html($positions_desc[$pos_key]); ?></span>
                            <input type="text" class="regular-text cpvt-image-field" name="cpvt_themes[<?php echo esc_attr($slug); ?>][<?php echo esc_attr($pos_key); ?>]" value="<?php echo esc_url($th[$pos_key] ?? ''); ?>">
                            <button type="button" class="button cpvt-upload"><?php echo esc_html__('Selecionar', 'cpvt'); ?></button>
                        </p>
                    <?php endforeach; ?>
                    <p><button type="button" class="button cpvt-remove-theme"><?php echo esc_html__('Remover', 'cpvt'); ?></button></p>
                </div>
            <?php endforeach; ?>
        </div>

        <div id="cpvt-theme-template" style="display:none;">
            <div class="cpvt-theme-item">
                <input type="hidden" data-name="cpvt_themes[__SLUG__][slug]" value="__SLUG__">
                <p><label><?php echo esc_html__('Título da personalização', 'cpvt'); ?>: <input type="text" data-name="cpvt_themes[__SLUG__][label]" value=""></label></p>
                <p><label><?php echo esc_html__('Cor do Background da página', 'cpvt'); ?>: <input type="color" data-name="cpvt_themes[__SLUG__][bg_color]" value=""></label></p>
                <p><label><?php echo esc_html__('Cor do Texto da página', 'cpvt'); ?>: <input type="color" data-name="cpvt_themes[__SLUG__][text_color]" value=""></label></p>
                <p><label><?php echo esc_html__('Cor do Título da página', 'cpvt'); ?>: <input type="color" data-name="cpvt_themes[__SLUG__][title_color]" value=""></label></p>
                <p><label><?php echo esc_html__('IDs dos Posts (vírgula separados)', 'cpvt'); ?>: <input type="text" data-name="cpvt_themes[__SLUG__][post_ids]" value="" class="regular-text"></label></p>
                <?php
                $positions_map = [
                    'top_left' => __('Superior Esquerda', 'cpvt'),
                    'top_right' => __('Superior Direita', 'cpvt'),
                    'bottom_left' => __('Inferior Esquerda', 'cpvt'),
                    'bottom_right' => __('Inferior Direita', 'cpvt'),
                ];
                $positions_desc = [
                    'top_left' => __('Imagem aplicada no canto superior esquerdo do background', 'cpvt'),
                    'top_right' => __('Imagem aplicada no canto superior direito do background', 'cpvt'),
                    'bottom_left' => __('Imagem aplicada no canto inferior esquerdo do background', 'cpvt'),
                    'bottom_right' => __('Imagem aplicada no canto inferior direito do background', 'cpvt'),
                ];
                foreach ($positions_map as $pos_key => $label) : ?>
                    <p>
                        <span class="cpvt-pos-label"><?php echo esc_html($label); ?></span>
                        <span class="cpvt-pos-desc"><?php echo esc_html($positions_desc[$pos_key]); ?></span>
                        <input type="text" class="regular-text cpvt-image-field" data-name="cpvt_themes[__SLUG__][<?php echo esc_attr($pos_key); ?>]" value="">
                        <button type="button" class="button cpvt-upload"><?php echo esc_html__('Selecionar', 'cpvt'); ?></button>
                    </p>
                <?php endforeach; ?>
                <p><button type="button" class="button cpvt-remove-theme"><?php echo esc_html__('Remover', 'cpvt'); ?></button></p>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Sanitizes themes array
 */
function cpvt_sanitize_themes($input)
{
    $out = [];
    if (!is_array($input)) return $out;

    foreach ($input as $slug => $data) {
        $label = sanitize_text_field($data['label'] ?? '');
        $slug_clean = sanitize_title($slug ?: $label);
        if (!$slug_clean) continue;
        if (isset($out[$slug_clean])) {
            $slug_clean .= '-' . wp_generate_password(4, false, false);
        }
        $theme = [];
        $theme['label'] = $label;
        $theme['bg_color'] = sanitize_hex_color($data['bg_color'] ?? '');
        $theme['text_color'] = sanitize_hex_color($data['text_color'] ?? '');
        $theme['title_color'] = sanitize_hex_color($data['title_color'] ?? '');
        $post_ids_raw = sanitize_text_field($data['post_ids'] ?? '');
        $ids = array_filter(array_map('intval', array_map('trim', explode(',', $post_ids_raw))), function ($v) {
            return $v > 0;
        });
        $theme['post_ids'] = implode(',', $ids);
        foreach (['top_left', 'top_right', 'bottom_left', 'bottom_right'] as $pos) {
            $theme[$pos] = esc_url_raw($data[$pos] ?? '');
        }

        // Skip empty preset (no label, no post IDs, no colors, no images)
        $has_content = (bool) ($theme['label'] || $theme['post_ids'] || $theme['bg_color'] || $theme['text_color'] || $theme['title_color'] || $theme['top_left'] || $theme['top_right'] || $theme['bottom_left'] || $theme['bottom_right']);
        if (! $has_content) {
            continue;
        }

        $out[$slug_clean] = $theme;
    }

    return $out;
}

/**
 * Adds a meta box to select preset per post
 */
add_action('add_meta_boxes', function () {
    add_meta_box('cpvt_theme_meta', __('CPVT Theme Preset', 'cpvt'), 'cpvt_meta_box_callback', 'post', 'side', 'low');
});

function cpvt_meta_box_callback($post)
{
    wp_nonce_field('cpvt_theme_meta', 'cpvt_theme_meta_nonce');
    $themes = get_option('cpvt_themes', []);
    $current = get_post_meta($post->ID, 'cpvt_theme', true);
    ?>
    <label for="cpvt_theme_select"><?php echo esc_html__('Preset', 'cpvt'); ?></label>
    <select name="cpvt_theme" id="cpvt_theme_select" style="width:100%">
        <option value=""><?php echo esc_html__('(none)', 'cpvt'); ?></option>
        <?php foreach ($themes as $slug => $th) : ?>
            <option value="<?php echo esc_attr($slug); ?>" <?php selected($current, $slug); ?>><?php echo esc_html($th['label'] ?? $slug); ?></option>
        <?php endforeach; ?>
    </select>
    <?php
}

add_action('save_post', function ($post_id) {
    if (!isset($_POST['cpvt_theme_meta_nonce']) || !wp_verify_nonce($_POST['cpvt_theme_meta_nonce'], 'cpvt_theme_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (isset($_POST['cpvt_theme'])) {
        $val = sanitize_text_field($_POST['cpvt_theme']);
        if ($val === '') {
            delete_post_meta($post_id, 'cpvt_theme');
        } else {
            update_post_meta($post_id, 'cpvt_theme', $val);
        }
    }
}, 10, 1); 
