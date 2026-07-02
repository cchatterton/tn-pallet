(function ($) {
    'use strict';

    function initColourPicker($context) {
        $context.find('.tnp-colour-picker').wpColorPicker({
            change: function (event, ui) {
                var colour = ui.color.toString();
                var $row = $(event.target).closest('.tnp-palette-row');

                $row.find('.tnp-colour-value').val(colour);
                $row.find('.tnp-preview').css('background-color', colour);
            },
            clear: function (event) {
                var $row = $(event.target).closest('.tnp-palette-row');

                $row.find('.tnp-colour-value').val('');
                $row.find('.tnp-preview').css('background-color', 'transparent');
            }
        });
    }

    function syncManualColour(input) {
        var $input = $(input);
        var colour = $input.val();
        var $row = $input.closest('.tnp-palette-row');

        $row.find('.tnp-colour-picker').wpColorPicker('color', colour);
        $row.find('.tnp-preview').css('background-color', colour);
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

        $rows.on('input change', '.tnp-colour-value', function () {
            syncManualColour(this);
        });

        $rows.on('click', '.tnp-remove-colour', function () {
            $(this).closest('.tnp-palette-row').remove();
        });

        $rows.on('click', '.tnp-move-up', function () {
            var $row = $(this).closest('.tnp-palette-row');
            var $previous = $row.prev('.tnp-palette-row');

            if ($previous.length) {
                $row.insertBefore($previous);
            }
        });

        $rows.on('click', '.tnp-move-down', function () {
            var $row = $(this).closest('.tnp-palette-row');
            var $next = $row.next('.tnp-palette-row');

            if ($next.length) {
                $row.insertAfter($next);
            }
        });
    });
})(jQuery);
