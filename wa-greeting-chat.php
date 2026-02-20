<?php
/**
 * Plugin Name: WA Greeting Chat
 * Plugin URI: https://github.com/Gioidstar/wa-greeting-chat
 * Description: Floating WhatsApp chat form with greeting message and WP-Admin storage.
 * Version: 1.6
 * Author: Gio fandi Idstar
 * Author URI: https://github.com/Gioidstar
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Text Domain: wa-greeting-chat
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WA_GREETING_CHAT_VERSION', '1.6');
define('WA_GREETING_CHAT_FILE', __FILE__);
define('WA_GREETING_CHAT_PATH', plugin_dir_path(__FILE__));

// =============================================================================
// GITHUB AUTO-UPDATER CONFIGURATION
// =============================================================================
// Ganti nilai di bawah dengan username dan repo GitHub Anda
define('WA_GREETING_CHAT_GITHUB_USERNAME', 'Gioidstar');
define('WA_GREETING_CHAT_GITHUB_REPO', 'wa-greeting-chat');   // Ganti dengan nama repository

// Initialize GitHub Updater
add_action('init', function() {
    if (is_admin()) {
        require_once WA_GREETING_CHAT_PATH . 'includes/class-github-updater.php';

        $updater = new WA_Greeting_Chat_GitHub_Updater(WA_GREETING_CHAT_FILE);
        $updater->set_repository(
            WA_GREETING_CHAT_GITHUB_USERNAME,
            WA_GREETING_CHAT_GITHUB_REPO
        );

        // Uncomment baris di bawah jika menggunakan private repository
        // $updater->set_access_token('YOUR_GITHUB_TOKEN');
    }
});

// Enqueue styles and scripts
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('wa-greeting-chat-style', plugin_dir_url(__FILE__) . 'style.css');
    wp_enqueue_script('wa-greeting-chat-script', plugin_dir_url(__FILE__) . 'script.js', [], false, true);
    $blocked_domains_raw = get_option('wa_blocked_email_domains', '');
    $blocked_domains = [];
    if (!empty($blocked_domains_raw)) {
        $blocked_domains = array_map('trim', explode(',', strtolower($blocked_domains_raw)));
        $blocked_domains = array_values(array_filter($blocked_domains));
    }
    // Build service group tree for cascading dropdowns
    $service_tree = [];
    $parent_terms = get_terms([
        'taxonomy' => 'wa_service',
        'hide_empty' => false,
        'parent' => 0,
    ]);
    if (!is_wp_error($parent_terms)) {
        foreach ($parent_terms as $parent) {
            $children = get_terms([
                'taxonomy' => 'wa_service',
                'hide_empty' => false,
                'parent' => $parent->term_id,
            ]);
            $child_names = [];
            if (!is_wp_error($children)) {
                foreach ($children as $child) {
                    $child_names[] = $child->name;
                }
            }
            $service_tree[] = [
                'name' => html_entity_decode($parent->name),
                'children' => array_map('html_entity_decode', $child_names),
            ];
        }
    }

    wp_localize_script('wa-greeting-chat-script', 'waGreeting', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'admin_wa' => get_option('wa_admin_number', ''),
        'blocked_domains' => $blocked_domains,
        'service_tree' => $service_tree,
    ]);
});

// Insert HTML into footer
add_action('wp_footer', function () {
  $chat_label = get_option('wa_chat_label', 'Click to Chat');
  $chat_image = get_option('wa_chat_image', 'https://randomuser.me/api/portraits/women/44.jpg');
  $privacy_policy_url = get_option('wa_privacy_policy_url', '#');
?>
<div id="wa-widget">
  <button onclick="toggleChat()">
  <svg viewBox="0 0 32 32" width="30" height="30" fill="white" xmlns="http://www.w3.org/2000/svg">
      <path d="M16 .6C7.6.6.6 7.6.6 16c0 2.8.7 5.4 2 7.8L.3 31.4l7.8-2C11 30.7 13.5 31.4 16 31.4c8.4 0 15.4-7 15.4-15.4S24.4.6 16 .6zm0 28.2c-2.3 0-4.5-.6-6.5-1.7l-.5-.3-4.6 1.2 1.2-4.5-.3-.5C4.6 20.5 4 18.3 4 16 4 8.8 9.8 3 17 3s13 5.8 13 13-5.8 13-13 13zm7.1-9.7c-.4-.2-2.5-1.2-2.9-1.3-.4-.1-.7-.2-1 .2-.3.4-1.2 1.3-1.4 1.5-.2.2-.5.3-.9.1-.4-.2-1.7-.6-3.2-2-1.2-1.2-2-2.7-2.2-3.1-.2-.4 0-.6.2-.8.2-.2.4-.5.6-.8.2-.3.3-.5.5-.8.2-.3.1-.5 0-.7-.1-.2-1-2.4-1.4-3.3-.4-.9-.7-.7-.9-.7-.2 0-.5 0-.8 0s-.7.1-1 .5c-.3.4-1.3 1.2-1.3 3 0 1.8 1.3 3.5 1.5 3.7.2.2 2.6 4 6.3 5.6.9.4 1.6.7 2.1.9.9.3 1.7.2 2.3.1.7-.1 2.5-1 2.8-1.9.4-1 .4-1.9.3-2z"/>
    </svg>
  </button>
</div>

<div id="wa-chat-box">
  <div class="wa-header">
    <div>
      <img src="<?= esc_url($chat_image) ?>" alt="agent">
      <strong><?= esc_html($chat_label) ?></strong>
    </div>
    <span onclick="toggleChat()">&times;</span>
  </div>

  <div class="wa-body">
    <label>Name<span class="required">*</span></label>
    <input id="wa-name" type="text" placeholder="Enter FullName">
    <small id="error-name" class="wa-error"></small>

    <label>Email<span class="required">*</span></label>
    <input id="wa-email" type="email" placeholder="Enter Email">
    <small id="error-email" class="wa-error"></small>

    <label>Company<span class="required">*</span></label>
    <input id="wa-company" type="text" placeholder="Enter Company">
    <small id="error-company" class="wa-error"></small>

    <label>What Service Do You Need?<span class="required">*</span></label>
    <select id="wa-service-group">
      <option value="" selected disabled>Choose Service </option>
    </select>
    <small id="error-service-group" class="wa-error"></small>

    <div id="wa-service-wrapper" style="display:none;">
      <label>What Service are You interested in<span class="required">*</span></label>
      <select id="wa-plugin">
        <option value="" selected disabled>Choose Service</option>
      </select>
      <small id="error-service" class="wa-error"></small>
    </div>

    <label>WhatsApp Number<span class="required">*</span></label>
    <input id="wa-number" type="number" placeholder="Example: 81234567890">
    <small id="error-number" class="wa-error"></small>

    <label>Message<span class="required">*</span></label>
    <textarea id="wa-message" placeholder="Enter Message.."></textarea>
    <small id="error-message" class="wa-error"></small>
    <label class="privacy">
  <input type="checkbox" id="wa-privacy" />
  <div class="checkmark">
    <p>Accept <a href="<?= esc_url($privacy_policy_url) ?>">Privacy Policy<span class="required">*</span></a></p>
  </div>
</label>
    <small id="error-privacy" class="wa-error"></small>

    <button onclick="sendWhatsapp()"> Send </button>

    <div class="wa-footer">
      <span class="dot"></span> Online | <a href="<?= esc_url($privacy_policy_url) ?>">Privacy</a>
    </div>
  </div>
</div>
<?php
});

// Save submission to custom post type
add_action('wp_ajax_wa_greeting_save', 'wa_greeting_save_submission');
add_action('wp_ajax_nopriv_wa_greeting_save', 'wa_greeting_save_submission');

function wa_greeting_save_submission() {
    // Check if all necessary fields are set
    if (empty($_POST['name']) || empty($_POST['email']) || empty($_POST['number']) || empty($_POST['plugin'])) {
        wp_send_json_error(['message' => 'Required fields are missing']);
        return;
    }

    // Check if email domain is blocked
    $email = sanitize_email($_POST['email']);
    $email_domain = strtolower(substr(strrchr($email, '@'), 1));
    $blocked_domains_raw = get_option('wa_blocked_email_domains', '');
    if (!empty($blocked_domains_raw)) {
        $blocked_domains = array_map('trim', explode(',', strtolower($blocked_domains_raw)));
        $blocked_domains = array_filter($blocked_domains);
        if (in_array($email_domain, $blocked_domains, true)) {
            wp_send_json_error(['message' => 'Email domain @' . $email_domain . ' is not allowed. Please use a business email.']);
            return;
        }
    }

    $data = [
        'name'          => sanitize_text_field($_POST['name']),
        'email'         => $email,
        'company'       => sanitize_text_field($_POST['company']),
        'service_group' => isset($_POST['service_group']) ? sanitize_text_field($_POST['service_group']) : '',
        'plugin'        => sanitize_text_field($_POST['plugin']),
        'number'        => sanitize_text_field($_POST['number']),
        'message'       => sanitize_textarea_field($_POST['message']),
        'url'           => isset($_POST['url']) ? esc_url_raw($_POST['url']) : '',
    ];

    // Create the post with metadata
    $post_id = wp_insert_post([
        'post_type'   => 'wa_submission',
        'post_title'  => $data['name'] . ' - ' . current_time('mysql'),
        'post_status' => 'publish',
        'meta_input'  => $data
    ]);

    // Ensure company field is explicitly saved as post meta
    update_post_meta($post_id, 'company', $data['company']);

    // Set the service taxonomy term (only child, or parent if no child)
    $term_id = null;
    if (!empty($_POST['plugin'])) {
        $term = get_term_by('name', sanitize_text_field($_POST['plugin']), 'wa_service');
        if ($term) {
            $term_id = $term->term_id;
        }
    }
    // Only use parent term if no child term was found
    if (!$term_id && !empty($_POST['service_group'])) {
        $parent_term = get_term_by('name', sanitize_text_field($_POST['service_group']), 'wa_service');
        if ($parent_term) {
            $term_id = $parent_term->term_id;
        }
    }
    if ($term_id) {
        wp_set_post_terms($post_id, [$term_id], 'wa_service');
    }

    // Send email notification to admin
    send_admin_notification_email($data, $post_id);

    // Log the successful submission
    error_log('WA Submission saved successfully. Post ID: ' . $post_id . ' | Company: ' . $data['company']);

    wp_send_json_success(['id' => $post_id]);
}

/**
 * Send notification email to admin when a new submission is received
 */
function send_admin_notification_email($data, $post_id) {
    // Get admin email
    $admin_email = get_option('admin_email');
    
    // Get notification emails from settings
    $notification_emails = get_option('wa_notification_emails', $admin_email);
    
    // Convert to array if it's a string (for backward compatibility)
    if (!is_array($notification_emails)) {
        $notification_emails = explode(',', $notification_emails);
        // Clean up email addresses
        $notification_emails = array_map('trim', $notification_emails);
        $notification_emails = array_filter($notification_emails, 'is_email');
        
        // If no valid emails found, use admin email
        if (empty($notification_emails)) {
            $notification_emails = array($admin_email);
        }
    }
    
    $subject = '[' . get_bloginfo('name') . '] New WhatsApp Chat Submission';
    
    $message = "Hello Admin,\n\n";
    $message .= "You have received a new WhatsApp chat submission.\n\n";
    $message .= "Submission Details:\n";
    $message .= "Name: " . $data['name'] . "\n";
    $message .= "Email: " . $data['email'] . "\n";
    $message .= "Company: " . $data['company'] . "\n";
    $message .= "Service Group: " . $data['service_group'] . "\n";
    $message .= "Service: " . $data['plugin'] . "\n";
    $message .= "WhatsApp Number: " . $data['number'] . "\n";
    $message .= "Message: " . $data['message'] . "\n";
    $message .= "URL: " . $data['url'] . "\n\n";
    
    $message .= "You can view this submission in WordPress admin:\n";
    $message .= admin_url('post.php?post=' . $post_id . '&action=edit') . "\n\n";
    
    $message .= "Regards,\n";
    $message .= "WA Greeting Chat Plugin";
    
    // Headers
    $headers = 'From: ' . get_bloginfo('name') . ' <' . $admin_email . '>' . "\r\n";
    
    // Send email to each recipient
    foreach ($notification_emails as $email) {
        if (!empty($email)) {
            wp_mail($email, $subject, $message, $headers);
        }
    }
}

// Register custom post type and taxonomy
add_action('init', function () {
    register_post_type('wa_submission', [
        'labels' => [
            'name' => 'WA Submissions',
            'singular_name' => 'WA Submission',
            'all_items' => 'All Submissions',
            'view_item' => 'View Submission'
        ],
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-format-chat',
        'supports' => ['title'],
        'capability_type' => 'post',
        'capabilities' => [
            'create_posts' => 'do_not_allow', // 🔒 disable Add New
            'edit_post' => 'read_post',       // 🔒 disable editing individual post
            'edit_posts' => 'read',           // 🔒 disable editing list
        ],
        'map_meta_cap' => true, // penting agar WordPress pakai capability mapping
    ]);
    

    register_taxonomy('wa_service', 'wa_submission', [
        'label' => 'Services',
        'labels' => [
            'name' => 'Services',
            'singular_name' => 'Service',
            'parent_item' => 'Service Group',
            'parent_item_colon' => 'Service Group:',
            'add_new_item' => 'Add New Service',
            'edit_item' => 'Edit Service',
        ],
        'rewrite' => ['slug' => 'service'],
        'hierarchical' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
    ]);
});

// Redirect default CPT list to custom submissions page
add_action('admin_init', function () {
    global $pagenow;
    if ($pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'wa_submission' && !isset($_GET['page'])) {
        wp_redirect(admin_url('admin.php?page=wa-submissions'));
        exit;
    }
});

// Display custom fields in admin metabox
add_action('add_meta_boxes', function () {
    add_meta_box(
        'wa_submission_details',
        'WA Submission Details',
        'wa_render_submission_details',
        'wa_submission',
        'normal',
        'high'
    );
});

function wa_render_submission_details($post) {
    $fields = ['name', 'email', 'company', 'service_group', 'plugin', 'number', 'message', 'url'];
    $labels = [
        'name' => 'Name',
        'email' => 'Email',
        'company' => 'Company',
        'service_group' => 'Service Group',
        'plugin' => 'Service',
        'number' => 'Number',
        'message' => 'Message',
        'url' => 'URL',
    ];
    echo '<table class="form-table">';
    foreach ($fields as $field) {
        $value = get_post_meta($post->ID, $field, true);
        $label = isset($labels[$field]) ? $labels[$field] : ucfirst($field);
        echo '<tr>';
        echo '<th><label>' . esc_html($label) . '</label></th>';
        echo '<td><input type="text" value="' . esc_attr($value) . '" class="regular-text" readonly></td>';
        echo '</tr>';
    }
    echo '</table>';
}

// Register admin menu pages
add_action('admin_menu', function () {
    // Custom submissions page
    add_submenu_page(
        'edit.php?post_type=wa_submission',
        'All Submissions',
        'All Submissions',
        'read',
        'wa-submissions',
        'wa_render_submissions_page'
    );

    // Settings page
    add_submenu_page(
        'edit.php?post_type=wa_submission',
        'WA Chat Settings',
        'Settings',
        'manage_options',
        'wa-chat-settings',
        'wa_render_settings_page'
    );
});

// Remove the default CPT "All Submissions" submenu link
add_action('admin_menu', function () {
    remove_submenu_page('edit.php?post_type=wa_submission', 'edit.php?post_type=wa_submission');
}, 999);

// Enqueue admin styles/scripts for custom page
add_action('admin_enqueue_scripts', function ($hook) {
    if (strpos($hook, 'wa-submissions') === false) {
        return;
    }
    wp_enqueue_style(
        'wa-admin-style',
        plugin_dir_url(WA_GREETING_CHAT_FILE) . 'admin/admin-style.css',
        [],
        WA_GREETING_CHAT_VERSION
    );
    wp_enqueue_script(
        'wa-admin-script',
        plugin_dir_url(WA_GREETING_CHAT_FILE) . 'admin/admin-script.js',
        [],
        WA_GREETING_CHAT_VERSION,
        true
    );
});

// Render custom submissions page
function wa_render_submissions_page() {
    // Handle CSV export before any HTML output
    if (isset($_GET['wa_export_csv']) && $_GET['wa_export_csv'] == '1') {
        wa_handle_csv_export();
        return;
    }

    // Handle bulk delete
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && !empty($_GET['submission_ids'])) {
        $nonce = isset($_GET['_wpnonce']) ? $_GET['_wpnonce'] : '';
        if (wp_verify_nonce($nonce, 'bulk-wa_submissions')) {
            $ids = array_map('intval', $_GET['submission_ids']);
            foreach ($ids as $id) {
                wp_delete_post($id, true);
            }
            echo '<div class="notice notice-success"><p>' . count($ids) . ' submission(s) deleted.</p></div>';
        }
    }

    require_once WA_GREETING_CHAT_PATH . 'includes/class-submissions-table.php';

    $table = new WA_Submissions_List_Table();
    $table->prepare_items();
    ?>
    <div class="wrap wa-submissions-wrap">
        <h1 class="wp-heading-inline">WA Chat Submissions</h1>
        <p class="description">All form submissions from the WhatsApp greeting chat widget.</p>

        <form method="get">
            <input type="hidden" name="page" value="wa-submissions" />
            <?php
            $table->search_box('Search Submissions', 'wa-submission-search');
            $table->display();
            ?>
        </form>
    </div>
    <?php
}

// Handle CSV export
function wa_handle_csv_export() {
    if (!current_user_can('read')) {
        wp_die('You do not have permission to export submissions.');
    }

    $args = [
        'post_type'      => 'wa_submission',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];

    // Date range filter
    $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
    $date_to   = isset($_GET['date_to'])   ? sanitize_text_field($_GET['date_to'])   : '';
    if ($date_from || $date_to) {
        $date_query = ['inclusive' => true];
        if ($date_from) {
            $date_query['after'] = $date_from;
        }
        if ($date_to) {
            $date_query['before'] = date('Y-m-d', strtotime($date_to . ' +1 day'));
        }
        $args['date_query'] = [$date_query];
    }

    // Search filter
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    if (!empty($search)) {
        $args['meta_query'] = [
            'relation' => 'OR',
            ['key' => 'name',          'value' => $search, 'compare' => 'LIKE'],
            ['key' => 'email',         'value' => $search, 'compare' => 'LIKE'],
            ['key' => 'company',       'value' => $search, 'compare' => 'LIKE'],
            ['key' => 'message',       'value' => $search, 'compare' => 'LIKE'],
            ['key' => 'number',        'value' => $search, 'compare' => 'LIKE'],
            ['key' => 'service_group', 'value' => $search, 'compare' => 'LIKE'],
            ['key' => 'plugin',        'value' => $search, 'compare' => 'LIKE'],
        ];
    }

    $query = new WP_Query($args);

    // Generate filename
    $filename = 'wa-submissions';
    if ($date_from) $filename .= '-from-' . $date_from;
    if ($date_to)   $filename .= '-to-' . $date_to;
    $filename .= '-' . date('Ymd-His') . '.csv';

    // CSV headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    // BOM for Excel UTF-8 compatibility
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Header row
    fputcsv($output, ['No', 'Name', 'Email', 'Company', 'Service Group', 'Service', 'WhatsApp Number', 'Message', 'Page URL', 'Date']);

    // Data rows
    $counter = 0;
    foreach ($query->posts as $post) {
        $counter++;
        fputcsv($output, [
            $counter,
            get_post_meta($post->ID, 'name', true),
            get_post_meta($post->ID, 'email', true),
            get_post_meta($post->ID, 'company', true),
            get_post_meta($post->ID, 'service_group', true),
            get_post_meta($post->ID, 'plugin', true),
            get_post_meta($post->ID, 'number', true),
            get_post_meta($post->ID, 'message', true),
            get_post_meta($post->ID, 'url', true),
            get_the_date('Y-m-d H:i:s', $post),
        ]);
    }

    fclose($output);
    exit;
}

function wa_render_settings_page() {
    if (isset($_POST['wa_chat_settings_submit'])) {
        update_option('wa_chat_label', sanitize_text_field($_POST['wa_chat_label']));
        update_option('wa_chat_image', esc_url_raw($_POST['wa_chat_image']));
        update_option('wa_admin_number', sanitize_text_field($_POST['wa_admin_number']));
        
        // Handle multiple notification emails
        $notification_emails = sanitize_textarea_field($_POST['wa_notification_emails']);
        update_option('wa_notification_emails', $notification_emails);
        
        update_option('wa_privacy_policy_url', esc_url_raw($_POST['wa_privacy_policy_url']));

        // Handle blocked email domains
        $blocked_domains = sanitize_textarea_field($_POST['wa_blocked_email_domains']);
        update_option('wa_blocked_email_domains', $blocked_domains);

        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    $label = get_option('wa_chat_label', 'Click to Chat');
    $image = get_option('wa_chat_image', 'https://randomuser.me/api/portraits/women/44.jpg');
    $number = get_option('wa_admin_number', '');
    $admin_email = get_option('admin_email');

    // Get notification emails option
    $notification_emails = get_option('wa_notification_emails', $admin_email);

    $privacy_policy_url = get_option('wa_privacy_policy_url', '#');
    $blocked_domains = get_option('wa_blocked_email_domains', '');

    ?>
    <div class="wrap">
        <h2>WA Chat Box Settings</h2>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="wa_chat_label">Header Label</label></th>
                    <td><input type="text" name="wa_chat_label" id="wa_chat_label" value="<?= esc_attr($label) ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="wa_chat_image">Agent Image URL</label></th>
                    <td><input type="text" name="wa_chat_image" id="wa_chat_image" value="<?= esc_url($image) ?>" class="regular-text">
                        <p class="description">Upload ke Media Library dan paste URL-nya.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="wa_admin_number">Admin WhatsApp Number</label></th>
                    <td><input type="text" name="wa_admin_number" id="wa_admin_number" value="<?= esc_attr($number) ?>" class="regular-text">
                        <p class="description">Tanpa +62 / 0. Contoh: 81234567890</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="wa_notification_emails">Notification Emails</label></th>
                    <td>
                        <textarea name="wa_notification_emails" id="wa_notification_emails" rows="4" class="large-text code"><?= esc_textarea($notification_emails) ?></textarea>
                        <p class="description">Email untuk menerima notifikasi submission baru. Untuk multiple email, pisahkan dengan koma. Default: <?= esc_html($admin_email) ?></p>
                        <p class="description">Contoh: admin@example.com, marketing@example.com, sales@example.com</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="wa_privacy_policy_url">Privacy Policy URL</label></th>
                    <td><input type="url" name="wa_privacy_policy_url" id="wa_privacy_policy_url" value="<?= esc_url($privacy_policy_url) ?>" class="regular-text">
                        <p class="description">URL lengkap ke halaman Privacy Policy. Contoh: https://yourdomain.com/privacy-policy</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="wa_blocked_email_domains">Blocked Email Domains</label></th>
                    <td>
                        <textarea name="wa_blocked_email_domains" id="wa_blocked_email_domains" rows="4" class="large-text code"><?= esc_textarea($blocked_domains) ?></textarea>
                        <p class="description">Domain email yang diblokir dari pengiriman form. Pisahkan dengan koma.</p>
                        <p class="description">Contoh: gmail.com, yahoo.com, hotmail.com</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Settings', 'primary', 'wa_chat_settings_submit'); ?>
        </form>
    </div>
    <?php
}