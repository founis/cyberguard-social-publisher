<?php defined('ABSPATH') || exit; ?>
<div class="wrap cgsp-wrap" dir="rtl">
    <div class="cgsp-hero">
        <div><span class="cgsp-kicker">CYBERGUARD</span><h1>Social Publisher</h1><p>פרסום ותזמון תוכן לפייסבוק ולאינסטגרם ממקום אחד.</p></div>
        <a class="button button-secondary" href="<?php echo esc_url(admin_url('admin.php?page=cgsp-settings')); ?>">הגדרות חיבור</a>
    </div>
    <?php if ($notice) : ?>
        <div class="notice notice-<?php echo in_array($notice, array('invalid'), true) ? 'error' : 'success'; ?> is-dismissible"><p>
            <?php echo esc_html(array('published' => 'בקשת הפרסום נשלחה.', 'scheduled' => 'הפוסט תוזמן בהצלחה.', 'invalid' => 'יש למלא תוכן ולבחור פלטפורמה.')[$notice] ?? 'הפעולה הושלמה.'); ?>
        </p></div>
    <?php endif; ?>
    <div class="cgsp-grid">
        <section class="cgsp-card">
            <h2>פוסט חדש</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="cgsp_publish">
                <?php wp_nonce_field('cgsp_publish'); ?>
                <label for="cgsp-message">תוכן הפוסט</label>
                <textarea id="cgsp-message" name="message" rows="9" maxlength="2200" required></textarea>
                <label for="cgsp-image-url">תמונה</label>
                <div class="cgsp-media-row"><input id="cgsp-image-url" name="image_url" type="url" placeholder="https://..."><button type="button" class="button" id="cgsp-choose-image">בחירה מספריית המדיה</button></div>
                <div id="cgsp-image-preview"></div>
                <fieldset><legend>איפה לפרסם?</legend><label><input type="checkbox" name="platforms[]" value="facebook" checked> Facebook</label><label><input type="checkbox" name="platforms[]" value="instagram" checked> Instagram</label></fieldset>
                <label for="cgsp-schedule">מועד פרסום (ריק = עכשיו)</label>
                <input id="cgsp-schedule" name="schedule_at" type="datetime-local">
                <button class="button button-primary button-hero" type="submit">פרסום / תזמון</button>
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
