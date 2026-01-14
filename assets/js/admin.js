jQuery(document).ready(function ($) {
    // Delegated media picker to support dynamically added fields
    $(document).on('click', '.cpvt-upload', function (e) {
        e.preventDefault();

        if (typeof wp === 'undefined' || !wp.media) {
            return;
        }

        var button = $(this);
        var input = button.siblings('.cpvt-image-field').first();

        var frame = wp.media({
            title: wp.i18n ? wp.i18n.__('Selecionar imagem', 'cpvt') : 'Selecionar imagem',
            button: { text: wp.i18n ? wp.i18n.__('Usar imagem', 'cpvt') : 'Usar imagem' },
            multiple: false
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            input.val(attachment.url);
        });

        frame.open();
    });

    // Add preset
    $(document).on('click', '.cpvt-add-theme', function (e) {
        e.preventDefault();
        var tpl = $('#cpvt-theme-template').html();
        var slug = 'preset-' + Date.now();
        tpl = tpl.replace(/__SLUG__/g, slug);
        $('.cpvt-themes-list').append(tpl);
        var $new = $('.cpvt-themes-list .cpvt-theme-item').last();
        // convert data-name -> name and set slug value
        $new.find('[data-name]').each(function () {
            var $el = $(this);
            var name = $el.attr('data-name').replace(/__SLUG__/g, slug);
            $el.attr('name', name).removeAttr('data-name');
        });
        $new.find('input[name*="[slug]"]').val(slug);
        $new.find('input[name*="[label]"]').focus();
    });

    // Remove preset
    $(document).on('click', '.cpvt-remove-theme', function (e) {
        e.preventDefault();
        $(this).closest('.cpvt-theme-item').remove();
    });

    // Clean empty presets before submit
    $(document).on('submit', 'form', function () {
        $('.cpvt-themes-list .cpvt-theme-item').each(function () {
            var $item = $(this);
            var label = $item.find('input[name*="[label]"]').val();
            var postids = $item.find('input[name*="[post_ids]"]').val();
            var imagesEmpty = true;
            $item.find('.cpvt-image-field').each(function () {
                if ($(this).val()) imagesEmpty = false;
            });
            var colorsEmpty = true;
            $item.find('input[type="color"]').each(function () {
                if ($(this).val()) colorsEmpty = false;
            });
            if (!label && !postids && imagesEmpty && colorsEmpty) {
                $item.remove();
            }
        });
    });
});
