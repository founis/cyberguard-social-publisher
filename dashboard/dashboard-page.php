<?php defined('ABSPATH') || exit; ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CyberGuard Publisher</title>
<?php wp_head(); ?>
</head>
<body class="cgsp-dashboard-body" dir="rtl">
<div class="cgsp-dashboard-shell">
    <header class="cgsp-dashboard-header">
        <div>
            <span class="cgsp-dashboard-kicker">CYBERGUARD</span>
            <h1>Publisher Control Center</h1>
            <p>מנהלים פוסט פעם אחת ומפרסמים לאתר, Facebook ו־Instagram.</p>
        </div>
        <div class="cgsp-dashboard-status">
            <span class="<?php echo !empty($settings['page_id']) && !empty($settings['access_token']) ? 'is-ok' : 'is-off'; ?>">Facebook</span>
            <span class="<?php echo !empty($settings['instagram_id']) && !empty($settings['access_token']) ? 'is-ok' : 'is-off'; ?>">Instagram</span>
            <span class="is-ok">Website</span>
        </div>
    </header>

    <?php if ($notice) :
        $messages = array(
            'published' => 'הפוסט פורסם.',
            'scheduled' => 'הפוסט תוזמן בהצלחה.',
            'cancelled' => 'התזמון בוטל.',
            'invalid' => 'יש למלא תוכן ולבחור לפחות יעד אחד.',
            'missing_title' => 'כדי לפרסם באתר צריך להוסיף כותרת.',
            'instagram_image' => 'Instagram דורשת תמונה.',
            'not_found' => 'לא מצאתי את הפוסט המתוזמן.',
        );
    ?>
        <div class="cgsp-dashboard-notice <?php echo in_array($notice, array('invalid','missing_title','instagram_image','not_found'), true) ? 'is-error' : 'is-success'; ?>">
            <?php echo esc_html(isset($messages[$notice]) ? $messages[$notice] : 'הפעולה הושלמה.'); ?>
        </div>
    <?php endif; ?>

    <section class="cgsp-dashboard-stats">
        <article><strong><?php echo esc_html(count($content_library)); ?></strong><span>פוסטים בספרייה</span></article>
        <article><strong><?php echo esc_html(count($scheduled_posts)); ?></strong><span>פוסטים מתוזמנים</span></article>
        <article><strong>3</strong><span>ערוצי פרסום</span></article>
    </section>

    <main class="cgsp-dashboard-grid">
        <section class="cgsp-dashboard-panel cgsp-dashboard-editor" id="cgsp-dashboard-editor">
            <div class="cgsp-panel-head"><div><span class="cgsp-dashboard-kicker">COMPOSER</span><h2>יצירת פוסט</h2></div><button type="button" class="cgsp-link-button" id="cgsp-reset-editor">נקה</button></div>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="cgsp-dashboard-form">
                <input type="hidden" name="action" value="cgsp_dashboard_publish">
                <input type="hidden" name="edit_timestamp" id="cgsp-edit-timestamp" value="">
                <input type="hidden" name="edit_key" id="cgsp-edit-key" value="">
                <?php wp_nonce_field('cgsp_dashboard_publish'); ?>

                <label for="cgsp-dashboard-title">כותרת</label>
                <input id="cgsp-dashboard-title" name="title" type="text" placeholder="כותרת לפוסט / מאמר">

                <label for="cgsp-dashboard-message">תוכן</label>
                <textarea id="cgsp-dashboard-message" name="message" rows="10" maxlength="2200" required></textarea>
                <div class="cgsp-counter"><span id="cgsp-dashboard-char-count">0</span>/2200</div>

                <label for="cgsp-dashboard-image">תמונה</label>
                <div class="cgsp-media-row"><input id="cgsp-dashboard-image" name="image_url" type="url" placeholder="https://..."><button type="button" id="cgsp-dashboard-choose-image">בחירת תמונה</button></div>
                <div id="cgsp-dashboard-preview"></div>

                <fieldset>
                    <legend>יעדי פרסום</legend>
                    <label><input id="cgsp-target-website" type="checkbox" name="platforms[]" value="website" checked> <span>אתר CyberGuard</span></label>
                    <label><input id="cgsp-target-facebook" type="checkbox" name="platforms[]" value="facebook" checked> <span>Facebook</span></label>
                    <label><input id="cgsp-target-instagram" type="checkbox" name="platforms[]" value="instagram" checked> <span>Instagram</span></label>
                </fieldset>

                <label for="cgsp-dashboard-schedule">מועד פרסום</label>
                <input id="cgsp-dashboard-schedule" name="schedule_at" type="datetime-local">
                <p class="cgsp-help">השאירו ריק לפרסום מיידי.</p>
                <button class="cgsp-primary-action" type="submit" id="cgsp-submit-post">פרסום / תזמון בכל היעדים</button>
            </form>
        </section>

        <section class="cgsp-dashboard-panel">
            <div class="cgsp-panel-head"><div><span class="cgsp-dashboard-kicker">QUEUE</span><h2>פוסטים מתוזמנים</h2></div></div>
            <?php if (!$scheduled_posts) : ?>
                <div class="cgsp-empty">אין כרגע פוסטים מתוזמנים.</div>
            <?php else : ?>
                <div class="cgsp-queue">
                    <?php foreach ($scheduled_posts as $event) :
                        $payload = $event['payload'];
                        $platforms = !empty($payload['platforms']) ? (array) $payload['platforms'] : array();
                    ?>
                        <article class="cgsp-queue-item">
                            <div class="cgsp-queue-time"><strong><?php echo esc_html(wp_date('d/m', $event['timestamp'])); ?></strong><span><?php echo esc_html(wp_date('H:i', $event['timestamp'])); ?></span></div>
                            <div class="cgsp-queue-copy">
                                <h3><?php echo esc_html(!empty($payload['title']) ? $payload['title'] : wp_trim_words($payload['message'] ?? '', 7, '…')); ?></h3>
                                <div class="cgsp-channel-pills">
                                    <?php foreach ($platforms as $platform) : ?><span><?php echo esc_html('website' === $platform ? 'אתר' : ucfirst($platform)); ?></span><?php endforeach; ?>
                                </div>
                            </div>
                            <div class="cgsp-queue-actions">
                                <button type="button" class="cgsp-edit-event"
                                    data-timestamp="<?php echo esc_attr($event['timestamp']); ?>"
                                    data-key="<?php echo esc_attr($event['key']); ?>"
                                    data-title="<?php echo esc_attr($payload['title'] ?? ''); ?>"
                                    data-message="<?php echo esc_attr($payload['message'] ?? ''); ?>"
                                    data-image="<?php echo esc_attr($payload['image_url'] ?? ''); ?>"
                                    data-platforms="<?php echo esc_attr(implode(',', $platforms)); ?>"
                                    data-schedule="<?php echo esc_attr(wp_date('Y-m-d\TH:i', $event['timestamp'])); ?>">עריכה</button>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                    <input type="hidden" name="action" value="cgsp_dashboard_cancel">
                                    <input type="hidden" name="timestamp" value="<?php echo esc_attr($event['timestamp']); ?>">
                                    <input type="hidden" name="event_key" value="<?php echo esc_attr($event['key']); ?>">
                                    <?php wp_nonce_field('cgsp_dashboard_cancel'); ?>
                                    <button type="submit" class="cgsp-danger-action">ביטול</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <section class="cgsp-dashboard-panel cgsp-library-panel">
        <div class="cgsp-panel-head"><div><span class="cgsp-dashboard-kicker">CONTENT LIBRARY</span><h2>ספריית CyberGuard</h2></div><input id="cgsp-dashboard-search" type="search" placeholder="חיפוש פוסט..."></div>
        <div class="cgsp-dashboard-library">
            <?php foreach ($content_library as $post) :
                $platforms = !empty($post['platforms']) && is_array($post['platforms']) ? $post['platforms'] : array('facebook','instagram');
            ?>
                <article class="cgsp-library-card" data-search="<?php echo esc_attr(strtolower(($post['title'] ?? '') . ' ' . ($post['message'] ?? ''))); ?>">
                    <div><h3><?php echo esc_html($post['title'] ?? 'פוסט CyberGuard'); ?></h3><p><?php echo esc_html(wp_trim_words($post['message'] ?? '', 20, '…')); ?></p></div>
                    <button type="button" class="cgsp-load-library-post"
                        data-title="<?php echo esc_attr($post['title'] ?? ''); ?>"
                        data-message="<?php echo esc_attr($post['message'] ?? ''); ?>"
                        data-image="<?php echo esc_attr($post['image_url'] ?? ''); ?>"
                        data-platforms="<?php echo esc_attr(implode(',', array_unique($platforms))); ?>">טען לעורך</button>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</div>
<?php wp_footer(); ?>
</body>
</html>
