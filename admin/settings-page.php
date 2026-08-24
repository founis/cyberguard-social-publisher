<?php defined('ABSPATH') || exit; ?>
<div class="wrap cgsp-wrap" dir="rtl">
    <div class="cgsp-hero"><div><span class="cgsp-kicker">CYBERGUARD</span><h1>הגדרות Meta</h1><p>הפרטים נשמרים באתר WordPress בלבד ואינם נשלחים ל־GitHub.</p></div></div>
    <?php if ($notice) : ?><div class="notice notice-<?php echo 'test_error' === $notice ? 'error' : 'success'; ?> is-dismissible"><p><?php echo esc_html(array('saved' => 'ההגדרות נשמרו.', 'test_ok' => 'החיבור ל־Meta תקין.', 'test_error' => 'החיבור נכשל. יש לבדוק את המזהים והטוקן.')[$notice] ?? ''); ?></p></div><?php endif; ?>
    <section class="cgsp-card cgsp-settings-card">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="cgsp_save_settings"><?php wp_nonce_field('cgsp_save_settings'); ?>
            <label for="graph_version">גרסת Graph API</label><input id="graph_version" name="graph_version" value="<?php echo esc_attr($settings['graph_version']); ?>" placeholder="v23.0">
            <label for="page_id">Facebook Page ID</label><input id="page_id" name="page_id" value="<?php echo esc_attr($settings['page_id']); ?>" inputmode="numeric">
            <label for="instagram_id">Instagram Business Account ID</label><input id="instagram_id" name="instagram_id" value="<?php echo esc_attr($settings['instagram_id']); ?>" inputmode="numeric">
            <label for="access_token">Page Access Token</label><textarea id="access_token" name="access_token" rows="4" placeholder="השאר ריק כדי לשמור את הטוקן הקיים"></textarea>
            <p class="description"><?php echo empty($settings['access_token']) ? 'עדיין לא נשמר טוקן.' : 'טוקן קיים נשמר. מטעמי אבטחה הוא אינו מוצג.'; ?></p>
            <button class="button button-primary" type="submit">שמירת הגדרות</button>
        </form>
        <form class="cgsp-test-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="cgsp_test_connection"><?php wp_nonce_field('cgsp_test_connection'); ?><button class="button" type="submit">בדיקת חיבור</button></form>
    </section>
</div>
