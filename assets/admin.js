(function ($) {
    'use strict';
    $(function () {
        var frame;

        function updatePreview() {
            var url = $('#cgsp-image-url').val();
            $('#cgsp-image-preview').html(url ? $('<img>', { src: url, alt: '' }) : '');
        }

        function updateCount() {
            $('#cgsp-char-count').text($('#cgsp-message').val().length);
        }

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

        $('#cgsp-image-url').on('change input', updatePreview);
        $('#cgsp-message').on('input', updateCount);

        $('.cgsp-load-post').on('click', function () {
            var $button = $(this);
            var message = $button.attr('data-message') || '';
            var imageUrl = $button.attr('data-image-url') || '';

            $('#cgsp-message').val(message).trigger('input');
            $('#cgsp-image-url').val(imageUrl).trigger('change');
            $('#cgsp-platform-facebook').prop('checked', $button.attr('data-facebook') === '1');
            $('#cgsp-platform-instagram').prop('checked', $button.attr('data-instagram') === '1');

            $('html, body').animate({ scrollTop: $('#cgsp-editor-card').offset().top - 40 }, 300);
            $('#cgsp-message').trigger('focus');
        });

        $('#cgsp-library-search').on('input', function () {
            var query = ($(this).val() || '').toLowerCase().trim();
            $('.cgsp-library-item').each(function () {
                var haystack = ($(this).attr('data-search') || '').toLowerCase();
                $(this).toggle(!query || haystack.indexOf(query) !== -1);
            });
        });

        updateCount();
        updatePreview();
    });
})(jQuery);
