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
        '1.1.0',
        "all"
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

    // Register a setting for the target selector
    register_setting('cpvt_settings_group', 'cpvt_target_selector', 'sanitize_text_field');

    add_settings_section(
        'cpvt_advanced_settings',
        __('Configurações Avançadas', 'cpvt'),
        null,
        'custom-post-visual-theme'
    );

    add_settings_field(
        'cpvt_target_selector',
        __('Seletor de CSS Alvo', 'cpvt'),
        'cpvt_field_target_selector',
        'custom-post-visual-theme',
        'cpvt_advanced_settings'
    );

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
 * Renderiza o campo do seletor de CSS
 */
function cpvt_field_target_selector()
{
    $selector = get_option('cpvt_target_selector', '');
?>
    <input type="text" name="cpvt_target_selector" value="<?php echo esc_attr($selector); ?>" class="regular-text">
    <p class="description">
        <?php echo esc_html__('Especifique o seletor de CSS (ex: .minha-classe, #meu-id) onde o tema será aplicado. Se deixado em branco, o plugin tentará encontrar um seletor padrão.', 'cpvt'); ?>
    </p>
<?php
}

/**
 * Render the presets UI
 */
function cpvt_field_themes()
{
    $themes = get_option('cpvt_themes', []);
?>
    <div id="cpvt-themes">
        <style>
            .cpvt-themes-list {
                display: flex;
                flex-direction: column;
                gap: 1rem;
                margin-top: 1rem;
            }

            .cpvt-theme-item {
                border: 1px solid #e1e1e1;
                padding: 12px;
                border-radius: 6px;
                background: #fff;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
            }

            .cpvt-theme-item p {
                margin: 0 0 8px;
            }

            .cpvt-pos-label {
                font-weight: 600;
                display: block;
                margin-bottom: 4px;
            }

            .cpvt-pos-desc {
                font-size: 12px;
                color: #666;
                margin-left: 4px;
                display: block;
            }

            .cpvt-image-row {
                display: flex;
                gap: 1rem;
            }

            .cpvt-image-col {
                flex: 1;
            }
            .regular-text{
                max-width: 80%;
            }
        </style>
        <p><button type="button" class="button cpvt-add-theme"><?php echo esc_html__('Adicionar Preset', 'cpvt'); ?></button></p>
        <div class="cpvt-themes-list">
            <?php foreach ($themes as $slug => $th) : ?>
                <div class="cpvt-theme-item">
                    <input type="hidden" name="cpvt_themes[<?php echo esc_attr($slug); ?>][slug]" value="<?php echo esc_attr($slug); ?>">
                    <p><label><?php echo esc_html__('Rótulo', 'cpvt'); ?>: <input type="text" name="cpvt_themes[<?php echo esc_attr($slug); ?>][label]" value="<?php echo esc_attr($th['label'] ?? ''); ?>"></label></p>
                    <p><label><?php echo esc_html__('IDs dos Posts (separados por vírgula)', 'cpvt'); ?>: <input type="text" name="cpvt_themes[<?php echo esc_attr($slug); ?>][post_ids]" value="<?php echo esc_attr($th['post_ids'] ?? ''); ?>" class="regular-text"></label></p>
                    <p><label><?php echo esc_html__('Posição Vertical Superior (ex: 100px, 20%, etc)', 'cpvt'); ?>: <input type="text" name="cpvt_themes[<?php echo esc_attr($slug); ?>][top_vertical_position]" value="<?php echo esc_attr($th['top_vertical_position'] ?? ''); ?>"></label>
                        <label><?php echo esc_html__('Posição Vertical Inferior (ex: 100px, 20%, etc)', 'cpvt'); ?>: <input type="text" name="cpvt_themes[<?php echo esc_attr($slug); ?>][bottom_vertical_position]" value="<?php echo esc_attr($th['bottom_vertical_position'] ?? ''); ?>"></label>
                    </p>

                    <p><label><?php echo esc_html__('Tamanho do Fundo (CSS background-size)', 'cpvt'); ?>: <input type="text" name="cpvt_themes[<?php echo esc_attr($slug); ?>][background_size]" value="<?php echo esc_attr($th['background_size'] ?? 'auto'); ?>" class="regular-text" style="width: 150px;"><span class="cpvt-pos-desc" style="display: inline; margin-left: 5px;"><?php echo esc_html__('Ex: auto, contain, cover, 15%.', 'cpvt'); ?></span></label></p>
                    <p>
                        <label><?php echo esc_html__('Tamanho do Fundo em Mobile (CSS background-size)', 'cpvt'); ?>: <input type="text" name="cpvt_themes[<?php echo esc_attr($slug); ?>][background_size_mobile]" value="<?php echo esc_attr($th['background_size_mobile'] ?? 'auto'); ?>" class="regular-text"  style="width: 150px;"><span class="cpvt-pos-desc" style="display: inline; margin-left: 5px;"><?php echo esc_html__('Ex: auto, contain, cover, 30%.', 'cpvt'); ?></span></label>
                    </p>
                    <p>
                        <label><?php echo esc_html__('Cor do fundo da página', 'cpvt'); ?>: <input type="text" name="cpvt_themes[<?php echo esc_attr($slug); ?>][background_color]" value="<?php echo esc_attr($th['background_color'] ?? ''); ?>" class="regular-text"  style="width: 150px;"><span class="cpvt-pos-desc" style="display: inline; margin-left: 5px;"><?php echo esc_html__('Ex: #ffffff, rgba(0,0,0,0.5), etc.', 'cpvt'); ?></span></label>
                    </p>

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
                    ?>
                    <div class="cpvt-image-row">
                        <div class="cpvt-image-col">
                            <p>
                                <span class="cpvt-pos-label"><?php echo esc_html($positions_map['top_left']); ?></span>
                                <span class="cpvt-pos-desc"><?php echo esc_html($positions_desc['top_left']); ?></span>
                                <input type="text" class="regular-text cpvt-image-field" name="cpvt_themes[<?php echo esc_attr($slug); ?>][top_left]" value="<?php echo esc_url($th['top_left'] ?? ''); ?>">
                                <button type="button" class="button cpvt-upload"><?php echo esc_html__('Selecionar', 'cpvt'); ?></button>
                            </p>
                        </div>
                        <div class="cpvt-image-col">
                            <p>
                                <span class="cpvt-pos-label"><?php echo esc_html($positions_map['top_right']); ?></span>
                                <span class="cpvt-pos-desc"><?php echo esc_html($positions_desc['top_right']); ?></span>
                                <input type="text" class="regular-text cpvt-image-field" name="cpvt_themes[<?php echo esc_attr($slug); ?>][top_right]" value="<?php echo esc_url($th['top_right'] ?? ''); ?>">
                                <button type="button" class="button cpvt-upload"><?php echo esc_html__('Selecionar', 'cpvt'); ?></button>
                            </p>
                        </div>
                    </div>
                    <div class="cpvt-image-row">
                        <div class="cpvt-image-col">
                            <p>
                                <span class="cpvt-pos-label"><?php echo esc_html($positions_map['bottom_left']); ?></span>
                                <span class="cpvt-pos-desc"><?php echo esc_html($positions_desc['bottom_left']); ?></span>
                                <input type="text" class="regular-text cpvt-image-field" name="cpvt_themes[<?php echo esc_attr($slug); ?>][bottom_left]" value="<?php echo esc_url($th['bottom_left'] ?? ''); ?>">
                                <button type="button" class="button cpvt-upload"><?php echo esc_html__('Selecionar', 'cpvt'); ?></button>
                            </p>
                        </div>
                        <div class="cpvt-image-col">
                            <p>
                                <span class="cpvt-pos-label"><?php echo esc_html($positions_map['bottom_right']); ?></span>
                                <span class="cpvt-pos-desc"><?php echo esc_html($positions_desc['bottom_right']); ?></span>
                                <input type="text" class="regular-text cpvt-image-field" name="cpvt_themes[<?php echo esc_attr($slug); ?>][bottom_right]" value="<?php echo esc_url($th['bottom_right'] ?? ''); ?>">
                                <button type="button" class="button cpvt-upload"><?php echo esc_html__('Selecionar', 'cpvt'); ?></button>
                            </p>
                        </div>
                    </div>
                    <p><button type="button" class="button cpvt-remove-theme"><?php echo esc_html__('Remover', 'cpvt'); ?></button></p>
                </div>
            <?php endforeach; ?>
        </div>

        <div id="cpvt-theme-template" style="display:none;">
            <div class="cpvt-theme-item">
                <input type="hidden" data-name="cpvt_themes[__SLUG__][slug]" value="__SLUG__">
                <p><label><?php echo esc_html__('Rótulo', 'cpvt'); ?>: <input type="text" data-name="cpvt_themes[__SLUG__][label]" value=""></label></p>
                <p><label><?php echo esc_html__('IDs dos Posts (separados por vírgula)', 'cpvt'); ?>: <input type="text" data-name="cpvt_themes[__SLUG__][post_ids]" value="" class="regular-text"></label></p>
                <p><label><?php echo esc_html__('Posição Vertical Superior (ex: 100px, 20%, etc)', 'cpvt'); ?>: <input type="text" data-name="cpvt_themes[__SLUG__][top_vertical_position]" value=""></label></p>
                <p><label><?php echo esc_html__('Posição Vertical Inferior (ex: 100px, 20%, etc)', 'cpvt'); ?>: <input type="text" data-name="cpvt_themes[__SLUG__][bottom_vertical_position]" value=""></label></p>
                <p><label><?php echo esc_html__('Tamanho do Fundo', 'cpvt'); ?>: <input type="text" data-name="cpvt_themes[__SLUG__][background_size]" value="auto" class="regular-text"><span class="cpvt-pos-desc" style="display: inline; margin-left: 5px;"><?php echo esc_html__('Ex: auto, contain, cover, 15%.', 'cpvt'); ?></span></label></p>
                <p><label><?php echo esc_html__('Tamanho do Fundo Mobile', 'cpvt'); ?>: <input type="text" data-name="cpvt_themes[__SLUG__][background_size_mobile]" value="auto" class="regular-text"><span class="cpvt-pos-desc" style="display: inline; margin-left: 5px;"><?php echo esc_html__('Ex: auto, contain, cover, 30%.', 'cpvt'); ?></span></label></p>
                <p><label><?php echo esc_html__('Cor do fundo da página', 'cpvt'); ?>: <input type="color" data-name="cpvt_themes[__SLUG__][background_color]" value="" class="regular-text"  style="width: 150px;"><span class="cpvt-pos-desc" style="display: inline; margin-left: 5px;"><?php echo esc_html__('Ex: #ffffff, rgba(0,0,0,0.5), etc.', 'cpvt'); ?></span></label></p>
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
                ?>
                <div class="cpvt-image-row">
                    <div class="cpvt-image-col">
                        <p>
                            <span class="cpvt-pos-label"><?php echo esc_html($positions_map['top_left']); ?></span>
                            <span class="cpvt-pos-desc"><?php echo esc_html($positions_desc['top_left']); ?></span>
                            <input type="text" class="regular-text cpvt-image-field" data-name="cpvt_themes[__SLUG__][top_left]" value="">
                            <button type="button" class="button cpvt-upload"><?php echo esc_html__('Selecionar', 'cpvt'); ?></button>
                        </p>
                    </div>
                    <div class="cpvt-image-col">
                        <p>
                            <span class="cpvt-pos-label"><?php echo esc_html($positions_map['top_right']); ?></span>
                            <span class="cpvt-pos-desc"><?php echo esc_html($positions_desc['top_right']); ?></span>
                            <input type="text" class="regular-text cpvt-image-field" data-name="cpvt_themes[__SLUG__][top_right]" value="">
                            <button type="button" class="button cpvt-upload"><?php echo esc_html__('Selecionar', 'cpvt'); ?></button>
                        </p>
                    </div>
                </div>
                <div class="cpvt-image-row">
                    <div class="cpvt-image-col">
                        <p>
                            <span class="cpvt-pos-label"><?php echo esc_html($positions_map['bottom_left']); ?></span>
                            <span class="cpvt-pos-desc"><?php echo esc_html($positions_desc['bottom_left']); ?></span>
                            <input type="text" class="regular-text cpvt-image-field" data-name="cpvt_themes[__SLUG__][bottom_left]" value="">
                            <button type="button" class="button cpvt-upload"><?php echo esc_html__('Selecionar', 'cpvt'); ?></button>
                        </p>
                    </div>
                    <div class="cpvt-image-col">
                        <p>
                            <span class="cpvt-pos-label"><?php echo esc_html($positions_map['bottom_right']); ?></span>
                            <span class="cpvt-pos-desc"><?php echo esc_html($positions_desc['bottom_right']); ?></span>
                            <input type="text" class="regular-text cpvt-image-field" data-name="cpvt_themes[__SLUG__][bottom_right]" value="">
                            <button type="button" class="button cpvt-upload"><?php echo esc_html__('Selecionar', 'cpvt'); ?></button>
                        </p>
                    </div>
                </div>
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
        $theme['top_vertical_position'] = sanitize_text_field($data['top_vertical_position'] ?? '');
        $theme['bottom_vertical_position'] = sanitize_text_field($data['bottom_vertical_position'] ?? '');
        $theme['background_size'] = sanitize_text_field($data['background_size'] ?? 'auto');
        $theme['background_size_mobile'] = sanitize_text_field($data['background_size_mobile'] ?? 'auto');
        $theme['background_color'] = sanitize_text_field($data['background_color'] ?? '');
        $post_ids_raw = sanitize_text_field($data['post_ids'] ?? '');
        $ids = array_filter(array_map('intval', array_map('trim', explode(',', $post_ids_raw))), function ($v) {
            return $v > 0;
        });
        $theme['post_ids'] = implode(',', $ids);
        foreach (['top_left', 'top_right', 'bottom_left', 'bottom_right'] as $pos) {
            $theme[$pos] = esc_url_raw($data[$pos] ?? '');
        }

        // Skip empty preset (no label, no post IDs, no images)
        $has_content = (bool) ($theme['label'] || $theme['post_ids'] || $theme['top_left'] || $theme['top_right'] || $theme['bottom_left'] || $theme['bottom_right'] || $theme['background_color']);
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
    $post_types = get_post_types(['public' => true]);
    foreach ($post_types as $pt) {
        $priority = ($pt === 'noticia' || $pt === 'post') ? 'high' : 'low';
        add_meta_box('cpvt_theme_meta', __('CPVT Theme Preset', 'cpvt'), 'cpvt_meta_box_callback', $pt, 'side', $priority);
    }
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
    // Check nonce and capability
    if (!isset($_POST['cpvt_theme_meta_nonce']) || !wp_verify_nonce($_POST['cpvt_theme_meta_nonce'], 'cpvt_theme_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (array_key_exists('cpvt_theme', $_POST)) {
        $val = sanitize_text_field($_POST['cpvt_theme']);
        if ($val === '') {
            delete_post_meta($post_id, 'cpvt_theme');
        } else {
            update_post_meta($post_id, 'cpvt_theme', $val);
        }
    }
}, 10, 1);
