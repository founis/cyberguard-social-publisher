(function ($) {
    'use strict';
    $(function () {
        var frame;
        $('#cgsp-choose-image').on('click', function (event) {
            event.preventDefault();
            if (frame) { frame.open(); return; }
            frame = wp.media({ title: 'בחירת תמונה לפוסט', button: { text: 'בחירת תמונה' }, multiple: false });
            frame.on('select', function () {
                var image = frame.state().get('selection').first().toJSON();
                $('#cgsp-image-url').val(image.url).trigger('change');
            });
            frame.open();
        });
        $('#cgsp-image-url').on('change input', function () {
            var url = $(this).val();
            $('#cgsp-image-preview').html(url ? $('<img>', { src: url, alt: '' }) : '');
        });
    });
})(jQuery);
