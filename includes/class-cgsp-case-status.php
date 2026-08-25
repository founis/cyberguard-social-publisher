<?php
defined('ABSPATH') || exit;

class CGSP_Case_Status {
    const POST_TYPE = 'cgsp_case';

    private $stages = array(
        'received' => 'הפנייה התקבלה',
        'review' => 'בדיקה ואבחון',
        'submitted' => 'הטיפול הועבר לבדיקה',
        'waiting' => 'ממתינים לעדכון',
        'completed' => 'הטיפול הושלם',
    );

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post_' . self::POST_TYPE, array($this, 'save_case'));
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', array($this, 'columns'));
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', array($this, 'column_content'), 10, 2);
        add_shortcode('cg_case_status', array($this, 'shortcode'));
    }

    public function register_post_type() {
        register_post_type(self::POST_TYPE, array(
            'labels' => array(
                'name' => 'פניות לקוחות',
                'singular_name' => 'פניית לקוח',
                'add_new_item' => 'הוספת פנייה',
                'edit_item' => 'עדכון פנייה',
                'search_items' => 'חיפוש פנייה',
                'not_found' => 'לא נמצאו פניות',
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'cgsp-publisher',
            'supports' => array('title'),
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ));
    }

    public function add_meta_box() {
        add_meta_box('cgsp_case_details', 'פרטי סטטוס ללקוח', array($this, 'render_meta_box'), self::POST_TYPE, 'normal', 'high');
    }

    public function render_meta_box($post) {
        wp_nonce_field('cgsp_save_case', 'cgsp_case_nonce');
        $reference = get_post_meta($post->ID, '_cgsp_reference', true);
        $phone_last4 = get_post_meta($post->ID, '_cgsp_phone_last4', true);
        $stage = get_post_meta($post->ID, '_cgsp_stage', true);
        $note = get_post_meta($post->ID, '_cgsp_customer_note', true);
        $updated = get_post_meta($post->ID, '_cgsp_status_updated', true);
        ?>
        <div dir="rtl" style="display:grid;gap:16px;max-width:760px">
            <p><label><strong>מספר פנייה</strong><br><input class="regular-text" name="cgsp_reference" value="<?php echo esc_attr($reference); ?>" placeholder="CG-2026-0001" required></label></p>
            <p><label><strong>4 ספרות אחרונות של טלפון הלקוח</strong><br><input class="small-text" inputmode="numeric" maxlength="4" name="cgsp_phone_last4" value="<?php echo esc_attr($phone_last4); ?>" required></label></p>
            <p><label><strong>שלב נוכחי</strong><br><select name="cgsp_stage">
                <?php foreach ($this->stages as $key => $label) : ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($stage ?: 'received', $key); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select></label></p>
            <p><label><strong>עדכון שמוצג ללקוח</strong><br><textarea class="large-text" rows="4" name="cgsp_customer_note" placeholder="לדוגמה: המסמכים התקבלו ונמצאים בבדיקה."><?php echo esc_textarea($note); ?></textarea></label></p>
            <?php if ($updated) : ?><p>עדכון אחרון: <strong><?php echo esc_html($updated); ?></strong></p><?php endif; ?>
            <p style="color:#646970">אין להזין כאן סיסמאות, קודי אימות או מידע רגיש.</p>
        </div>
        <?php
    }

    public function save_case($post_id) {
        if (!isset($_POST['cgsp_case_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cgsp_case_nonce'])), 'cgsp_save_case')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $reference = isset($_POST['cgsp_reference']) ? strtoupper(preg_replace('/[^A-Za-z0-9-]/', '', wp_unslash($_POST['cgsp_reference']))) : '';
        $phone_last4 = isset($_POST['cgsp_phone_last4']) ? preg_replace('/\D/', '', wp_unslash($_POST['cgsp_phone_last4'])) : '';
        $stage = isset($_POST['cgsp_stage']) ? sanitize_key(wp_unslash($_POST['cgsp_stage'])) : 'received';
        $note = isset($_POST['cgsp_customer_note']) ? sanitize_textarea_field(wp_unslash($_POST['cgsp_customer_note'])) : '';

        if (!isset($this->stages[$stage])) {
            $stage = 'received';
        }

        update_post_meta($post_id, '_cgsp_reference', $reference);
        update_post_meta($post_id, '_cgsp_phone_last4', substr($phone_last4, -4));
        update_post_meta($post_id, '_cgsp_stage', $stage);
        update_post_meta($post_id, '_cgsp_customer_note', $note);
        update_post_meta($post_id, '_cgsp_status_updated', wp_date('d/m/Y H:i'));
    }

    public function columns($columns) {
        return array(
            'cb' => $columns['cb'],
            'title' => 'שם הלקוח / כותרת פנימית',
            'reference' => 'מספר פנייה',
            'stage' => 'סטטוס',
            'updated' => 'עודכן',
            'date' => $columns['date'],
        );
    }

    public function column_content($column, $post_id) {
        if ('reference' === $column) {
            echo esc_html(get_post_meta($post_id, '_cgsp_reference', true));
        } elseif ('stage' === $column) {
            $stage = get_post_meta($post_id, '_cgsp_stage', true);
            echo esc_html(isset($this->stages[$stage]) ? $this->stages[$stage] : '—');
        } elseif ('updated' === $column) {
            echo esc_html(get_post_meta($post_id, '_cgsp_status_updated', true));
        }
    }

    public function shortcode() {
        $result = null;
        $reference = '';
        $last4 = '';

        if ('POST' === strtoupper(isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'])) : '') && isset($_POST['cgsp_status_nonce'])) {
            if (wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cgsp_status_nonce'])), 'cgsp_check_status')) {
                $reference = isset($_POST['cgsp_reference']) ? strtoupper(preg_replace('/[^A-Za-z0-9-]/', '', wp_unslash($_POST['cgsp_reference']))) : '';
                $last4 = isset($_POST['cgsp_phone_last4']) ? substr(preg_replace('/\D/', '', wp_unslash($_POST['cgsp_phone_last4'])), -4) : '';
                if ($reference && 4 === strlen($last4)) {
                    $query = new WP_Query(array(
                        'post_type' => self::POST_TYPE,
                        'post_status' => 'publish',
                        'posts_per_page' => 1,
                        'no_found_rows' => true,
                        'meta_query' => array(
                            'relation' => 'AND',
                            array('key' => '_cgsp_reference', 'value' => $reference),
                            array('key' => '_cgsp_phone_last4', 'value' => $last4),
                        ),
                    ));
                    if ($query->have_posts()) {
                        $case_id = $query->posts[0]->ID;
                        $result = array(
                            'stage' => get_post_meta($case_id, '_cgsp_stage', true) ?: 'received',
                            'note' => get_post_meta($case_id, '_cgsp_customer_note', true),
                            'updated' => get_post_meta($case_id, '_cgsp_status_updated', true),
                        );
                    } else {
                        $result = false;
                    }
                } else {
                    $result = false;
                }
            }
        }

        $keys = array_keys($this->stages);
        $active_index = is_array($result) ? array_search($result['stage'], $keys, true) : -1;
        ob_start();
        ?>
        <section class="cgsp-status" dir="rtl" aria-labelledby="cgsp-status-title">
            <style>
                .cgsp-status{max-width:900px;margin:32px auto;padding:clamp(22px,4vw,44px);border-radius:28px;background:linear-gradient(145deg,#071426,#0b2442);color:#fff;box-shadow:0 24px 70px rgba(3,12,28,.22);font-family:inherit}
                .cgsp-status *{box-sizing:border-box}.cgsp-status h2{margin:0 0 10px;font-size:clamp(28px,5vw,44px)}.cgsp-status__lead{color:#b9cae0;margin:0 0 26px}
                .cgsp-status__form{display:grid;grid-template-columns:1fr 180px auto;gap:12px}.cgsp-status input{width:100%;min-height:52px;border:1px solid #315171;border-radius:14px;background:#fff;color:#071426;padding:0 16px;font-size:17px}.cgsp-status button{min-height:52px;border:0;border-radius:14px;background:linear-gradient(135deg,#16d9c4,#3d8cff);color:#04101f;font-weight:800;padding:0 24px;cursor:pointer}
                .cgsp-status__result{margin-top:28px;padding:22px;border:1px solid rgba(99,216,255,.28);border-radius:18px;background:rgba(255,255,255,.06)}.cgsp-status__steps{display:grid;grid-template-columns:repeat(5,1fr);gap:8px;margin-top:22px}.cgsp-status__step{position:relative;text-align:center;color:#8297af;font-size:13px}.cgsp-status__dot{display:block;width:22px;height:22px;margin:0 auto 8px;border:3px solid #36516d;border-radius:50%;background:#0b2442}.cgsp-status__step.is-done{color:#fff}.cgsp-status__step.is-done .cgsp-status__dot{border-color:#2ce0c8;background:#2ce0c8;box-shadow:0 0 0 5px rgba(44,224,200,.12)}.cgsp-status__error{color:#ffd0d7}
                @media(max-width:700px){.cgsp-status__form{grid-template-columns:1fr}.cgsp-status__steps{grid-template-columns:1fr;text-align:right}.cgsp-status__step{display:flex;align-items:center;gap:10px;text-align:right}.cgsp-status__dot{margin:0}}
            </style>
            <h2 id="cgsp-status-title">בדיקת סטטוס טיפול</h2>
            <p class="cgsp-status__lead">הזינו את מספר הפנייה ואת ארבע הספרות האחרונות של הטלפון.</p>
            <form method="post" class="cgsp-status__form">
                <?php wp_nonce_field('cgsp_check_status', 'cgsp_status_nonce'); ?>
                <label><span class="screen-reader-text">מספר פנייה</span><input name="cgsp_reference" value="<?php echo esc_attr($reference); ?>" placeholder="מספר פנייה" autocomplete="off" required></label>
                <label><span class="screen-reader-text">4 ספרות אחרונות</span><input name="cgsp_phone_last4" value="<?php echo esc_attr($last4); ?>" placeholder="4 ספרות אחרונות" inputmode="numeric" maxlength="4" autocomplete="off" required></label>
                <button type="submit">בדיקת סטטוס</button>
            </form>
            <?php if (false === $result) : ?>
                <div class="cgsp-status__result cgsp-status__error" role="alert">לא נמצאה פנייה תואמת. בדקו את הפרטים או פנו לצוות CyberGuard.</div>
            <?php elseif (is_array($result)) : ?>
                <div class="cgsp-status__result" aria-live="polite">
                    <strong><?php echo esc_html($this->stages[$result['stage']]); ?></strong>
                    <?php if ($result['note']) : ?><p><?php echo nl2br(esc_html($result['note'])); ?></p><?php endif; ?>
                    <?php if ($result['updated']) : ?><small>עדכון אחרון: <?php echo esc_html($result['updated']); ?></small><?php endif; ?>
                    <div class="cgsp-status__steps">
                        <?php foreach ($this->stages as $key => $label) : $index = array_search($key, $keys, true); ?>
                            <div class="cgsp-status__step <?php echo $index <= $active_index ? 'is-done' : ''; ?>"><span class="cgsp-status__dot"></span><?php echo esc_html($label); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </section>
        <?php
        return ob_get_clean();
    }
}
