<?php defined('ABSPATH') || exit; ?>
<div class="wrap cgsp-wrap" dir="rtl">
    <div class="cgsp-hero">
        <div><span class="cgsp-kicker">CYBERGUARD</span><h1>Publisher</h1><p>פוסט אחד. תזמון אחד. אתר + Facebook + Instagram ביחד.</p></div>
        <a class="button button-secondary" href="<?php echo esc_url(admin_url('admin.php?page=cgsp-settings')); ?>">הגדרות חיבור</a>
    </div>

    <?php if ($notice) : ?>
        <div class="notice notice-<?php echo in_array($notice, array('invalid', 'missing_title'), true) ? 'error' : 'success'; ?> is-dismissible"><p>
            <?php echo esc_html(array(
                'published' => 'בקשת הפרסום נשלחה לכל היעדים שנבחרו.',
                'scheduled' => 'הפוסט תוזמן בהצלחה לכל היעדים שנבחרו.',
                'invalid' => 'יש למלא תוכן ולבחור לפחות יעד פרסום אחד.',
                'missing_title' => 'כדי לפרסם גם באתר יש להזין כותרת לפוסט.'
            )[$notice] ?? 'הפעולה הושלמה.'); ?>
        </p></div>
    <?php endif; ?>

    <section class="cgsp-card cgsp-library-card">
        <div class="cgsp-library-head">
            <div>
                <span class="cgsp-kicker">CONTENT LIBRARY</span>
                <h2>ספריית הפוסטים של CyberGuard</h2>
                <p><?php echo esc_html(count($content_library)); ?> פוסטים מוכנים. טוענים פוסט, בוחרים יעד ומפרסמים או מתזמנים.</p>
            </div>
            <input id="cgsp-library-search" type="search" placeholder="חיפוש פוסט..." aria-label="חיפוש פוסט">
        </div>

        <?php if (!$content_library) : ?>
            <p>לא נמצאו פוסטים מוכנים בספרייה.</p>
        <?php else : ?>
            <div class="cgsp-post-library" id="cgsp-post-library">
                <?php foreach ($content_library as $index => $post) :
                    $title = isset($post['title']) ? $post['title'] : 'פוסט CyberGuard';
                    $message = isset($post['message']) ? $post['message'] : '';
                    $image_note = isset($post['image_note']) ? $post['image_note'] : '';
                    $has_image = !empty($post['image_library_file_id']) || !empty($post['image_url']);
                    $image_url = !empty($post['image_url']) ? $post['image_url'] : '';
                    $platforms = !empty($post['platforms']) && is_array($post['platforms']) ? $post['platforms'] : array('facebook', 'instagram');
                ?>
                    <article class="cgsp-library-item" data-search="<?php echo esc_attr(strtolower($title . ' ' . $message)); ?>">
                        <div class="cgsp-library-index">#<?php echo esc_html($index + 1); ?></div>
                        <div class="cgsp-library-content">
                            <h3><?php echo esc_html($title); ?></h3>
                            <p><?php echo esc_html(wp_trim_words($message, 28, '…')); ?></p>
                            <div class="cgsp-library-meta">
                                <span>Website</span><span>Facebook</span><span>Instagram</span>
                                <span class="<?php echo $has_image ? 'is-ready' : 'is-missing'; ?>"><?php echo $has_image ? 'תמונה משויכת' : 'ללא תמונה'; ?></span>
                            </div>
                            <?php if ($image_note) : ?><small><?php echo esc_html($image_note); ?></small><?php endif; ?>
                        </div>
                        <button type="button"
                            class="button button-primary cgsp-load-post"
                            data-title="<?php echo esc_attr($title); ?>"
                            data-message="<?php echo esc_attr($message); ?>"
                            data-image-url="<?php echo esc_attr($image_url); ?>"
                            data-website="1"
                            data-facebook="<?php echo in_array('facebook', $platforms, true) ? '1' : '0'; ?>"
                            data-instagram="<?php echo in_array('instagram', $platforms, true) ? '1' : '0'; ?>">
                            טען פוסט
                        </button>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <div class="cgsp-grid">
        <section class="cgsp-card" id="cgsp-editor-card">
            <h2>פוסט חדש / מתוזמן</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="cgsp_publish">
                <?php wp_nonce_field('cgsp_publish'); ?>

                <label for="cgsp-title">כותרת לפוסט באתר</label>
                <input id="cgsp-title" name="title" type="text" maxlength="180" placeholder="לדוגמה: 5 דרכים להגן על חשבון האינסטגרם שלכם">

                <label for="cgsp-message">תוכן הפוסט</label>
                <textarea id="cgsp-message" name="message" rows="9" maxlength="2200" required></textarea>
                <div class="cgsp-counter"><span id="cgsp-char-count">0</span>/2200</div>

                <label for="cgsp-image-url">תמונה</label>
                <div class="cgsp-media-row"><input id="cgsp-image-url" name="image_url" type="url" placeholder="https://..."><button type="button" class="button" id="cgsp-choose-image">בחירה מספריית המדיה</button></div>
                <div id="cgsp-image-preview"></div>

                <fieldset class="cgsp-destinations">
                    <legend>לאן לפרסם?</legend>
                    <label><input id="cgsp-platform-website" type="checkbox" name="platforms[]" value="website" checked> <strong>האתר</strong><small>מאמר חדש ב־CyberGuard</small></label>
                    <label><input id="cgsp-platform-facebook" type="checkbox" name="platforms[]" value="facebook" checked> <strong>Facebook</strong><small>עמוד CyberGuard</small></label>
                    <label><input id="cgsp-platform-instagram" type="checkbox" name="platforms[]" value="instagram" checked> <strong>Instagram</strong><small>חשבון העסק</small></label>
                </fieldset>

                <label for="cgsp-schedule">מועד פרסום</label>
                <input id="cgsp-schedule" name="schedule_at" type="datetime-local">
                <p class="description">משאירים ריק לפרסום מיידי. בוחרים שעה ותאריך כדי שכל היעדים יעלו יחד.</p>
                <button class="button button-primary button-hero" type="submit">פרסום / תזמון בכל היעדים</button>
            </form>
        </section>

        <section class="cgsp-card">
            <h2>פעילות אחרונה</h2>
            <?php if (!$logs) : ?><p>עדיין אין פעילות.</p><?php else : ?>
                <div class="cgsp-log-list">
                <?php foreach ($logs as $log) : ?>
                    <article class="cgsp-log cgsp-log-<?php echo esc_attr($log->status); ?>"><div><strong><?php echo esc_html(ucfirst($log->platform)); ?></strong><span><?php echo esc_html($log->created_at); ?></span></div><p><?php echo esc_html($log->message); ?></p><?php if ($log->remote_id) : ?><code><?php echo esc_html($log->remote_id); ?></code><?php endif; ?></article>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
