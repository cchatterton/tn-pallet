(function ($) {
    'use strict';

    function initColourPicker($context) {
        $context.find('.tnp-colour-picker').each(function () {
            var $input = $(this);

            $input.wpColorPicker({
                defaultColor: '#000000'
            });

            $input.closest('.wp-picker-container').addClass('wp-picker-active');
            $input.closest('.wp-picker-container').find('.wp-picker-holder').show();
            $input.closest('.wp-picker-container').find('.wp-color-result').attr('tabindex', '-1').hide();
        });
    }

    $(function () {
        var $rows = $('#tnp-palette-rows');
        var cardTemplate = $('#tmpl-tnp-palette-card').html();

        initColourPicker($rows);

        $('#tnp-add-colour').on('click', function () {
            var $card = $(cardTemplate);

            $rows.append($card);
            initColourPicker($card);
        });

        $rows.on('click', '.tnp-remove-colour', function () {
            $(this).closest('.tnp-palette-card').remove();
        });
    });
})(jQuery);
