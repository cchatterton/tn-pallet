(function ($) {
    'use strict';

    function initColourPicker($context) {
        $context.find('.tnp-colour-picker').wpColorPicker({
            defaultColor: '#000000'
        });
    }

    $(function () {
        var $rows = $('#tnp-palette-rows');
        var rowTemplate = $('#tmpl-tnp-palette-row').html();

        initColourPicker($rows);

        $('#tnp-add-colour').on('click', function () {
            var $row = $(rowTemplate);

            $rows.append($row);
            initColourPicker($row);
        });

        $rows.on('click', '.tnp-remove-colour', function () {
            $(this).closest('.tnp-palette-row').remove();
        });
    });
})(jQuery);
