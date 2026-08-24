(function ($) {
    'use strict';
    $(function () {
        var mediaFrame;
        var $form = $('#cgsp-dashboard-form');
        var $title = $('#cgsp-dashboard-title');
        var $message = $('#cgsp-dashboard-message');
        var $image = $('#cgsp-dashboard-image');
        var $schedule = $('#cgsp-dashboard-schedule');
        var $editTimestamp = $('#cgsp-edit-timestamp');
        var $editKey = $('#cgsp-edit-key');

        function preview() {
            var url = $image.val();
            $('#cgsp-dashboard-preview').html(url ? $('<img>', { src: url, alt: 'תצוגה מקדימה' }) : '');
        }

        function count() {
            $('#cgsp-dashboard-char-count').text($message.val().length);
        }

        function setPlatforms(value) {
            var platforms = (value || '').split(',');
            $('#cgsp-target-website').prop('checked', platforms.indexOf('website') !== -1);
            $('#cgsp-target-facebook').prop('checked', platforms.indexOf('facebook') !== -1);
            $('#cgsp-target-instagram').prop('checked', platforms.indexOf('instagram') !== -1);
        }

        function resetEditor() {
            $form[0].reset();
            $editTimestamp.val('');
            $editKey.val('');
            $('#cgsp-target-website,#cgsp-target-facebook,#cgsp-target-instagram').prop('checked', true);
            $('#cgsp-submit-post').text('פרסום / תזמון בכל היעדים');
            count();
            preview();
        }

        function scrollEditor() {
            var target = $('#cgsp-dashboard-editor').offset().top;
            $('html, body').animate({ scrollTop: Math.max(0, target - 16) }, 250);
        }

        $('#cgsp-dashboard-choose-image').on('click', function () {
            if (mediaFrame) { mediaFrame.open(); return; }
            mediaFrame = wp.media({ title: 'בחירת תמונה', button: { text: 'השתמש בתמונה' }, multiple: false });
            mediaFrame.on('select', function () {
                var selected = mediaFrame.state().get('selection').first().toJSON();
                $image.val(selected.url).trigger('input');
            });
            mediaFrame.open();
        });

        $image.on('input change', preview);
        $message.on('input', count);
        $('#cgsp-reset-editor').on('click', resetEditor);

        $('.cgsp-load-library-post').on('click', function () {
            var $button = $(this);
            resetEditor();
            $title.val($button.attr('data-title') || '');
            $message.val($button.attr('data-message') || '').trigger('input');
            $image.val($button.attr('data-image') || '').trigger('input');
            setPlatforms($button.attr('data-platforms') || 'website,facebook,instagram');
            scrollEditor();
        });

        $('.cgsp-edit-event').on('click', function () {
            var $button = $(this);
            $title.val($button.attr('data-title') || '');
            $message.val($button.attr('data-message') || '').trigger('input');
            $image.val($button.attr('data-image') || '').trigger('input');
            $schedule.val($button.attr('data-schedule') || '');
            $editTimestamp.val($button.attr('data-timestamp') || '');
            $editKey.val($button.attr('data-key') || '');
            setPlatforms($button.attr('data-platforms') || '');
            $('#cgsp-submit-post').text('שמירת שינויים בתזמון');
            scrollEditor();
        });

        $('#cgsp-dashboard-search').on('input', function () {
            var query = ($(this).val() || '').toLowerCase().trim();
            $('.cgsp-library-card').each(function () {
                var text = ($(this).attr('data-search') || '').toLowerCase();
                $(this).toggle(!query || text.indexOf(query) !== -1);
            });
        });

        $('.cgsp-danger-action').on('click', function (event) {
            if (!window.confirm('לבטל את הפוסט המתוזמן?')) {
                event.preventDefault();
            }
        });

        count();
        preview();
    });
})(jQuery);
