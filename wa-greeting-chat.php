<?php
/**
 * Plugin Name: WA Greeting Chat
 * Plugin URI: https://github.com/Gioidstar/wa-greeting-chat
 * Description: Floating WhatsApp chat form with greeting message and WP-Admin storage.
 * Version: 1.11
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
define('WA_GREETING_CHAT_VERSION', '1.11');
define('WA_GREETING_CHAT_FILE', __FILE__);
define('WA_GREETING_CHAT_PATH', plugin_dir_path(__FILE__));

// Load Composer autoloader
if (file_exists(WA_GREETING_CHAT_PATH . 'vendor/autoload.php')) {
    require_once WA_GREETING_CHAT_PATH . 'vendor/autoload.php';
}


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
        require_once WA_GREETING_CHAT_PATH . 'includes/class-google-sheets.php';

        $updater = new WA_Greeting_Chat_GitHub_Updater(WA_GREETING_CHAT_FILE);
        $updater->set_repository(
            WA_GREETING_CHAT_GITHUB_USERNAME,
            WA_GREETING_CHAT_GITHUB_REPO
        );

        // Uncomment baris di bawah jika menggunakan private repository
        // $updater->set_access_token('YOUR_GITHUB_TOKEN');
    }
});

// Enqueue frontend styles and scripts
add_action('wp_enqueue_scripts', function () {
    if (is_admin()) return;
    wp_enqueue_style('wa-greeting-chat-style', plugin_dir_url(__FILE__) . 'style.css', [], WA_GREETING_CHAT_VERSION);
    wp_enqueue_script('wa-greeting-chat-script', plugin_dir_url(__FILE__) . 'script.js', [], WA_GREETING_CHAT_VERSION, true);
    // Build service group tree (cached)
    $service_tree = get_transient('wa_service_tree');
    if ($service_tree === false) {
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
        set_transient('wa_service_tree', $service_tree, HOUR_IN_SECONDS);
    }

    wp_localize_script('wa-greeting-chat-script', 'waGreeting', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wa_greeting_nonce'),
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
  <svg version="1.2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 112.5" width="100" height="113">
    <g id="Layer 1">
        <path 
            d="m50 14.33c-23.02 0-41.67 18.65-41.67 41.67 0 7.37 1.94 14.29 5.32 20.29l-5.32 21.38 21.94-5.15q2.26 1.25 4.65 2.2 2.4 0.96 4.89 1.61 2.5 0.65 5.06 0.98 2.55 0.34 5.13 0.36c23.02 0 41.67-18.65 41.67-41.67 0-23.02-18.65-41.67-41.67-41.67z"
            fill="#25d366"
            stroke="#ffffff"
            stroke-width="8"
        />
        
        <path 
            fill-rule="evenodd"
            d="m40.56 39.13c-0.75-1.67-1.54-1.71-2.25-1.73l-1.91-0.03c-0.67 0-1.75 0.25-2.67 1.26-0.92 1-3.5 3.41-3.5 8.33 0 4.91 3.58 9.67 4.08 10.33 0.5 0.67 6.92 11.08 17.09 15.09 8.45 3.33 10.16 2.66 12 2.5 1.83-0.17 5.91-2.42 6.75-4.75 0.83-2.34 0.83-4.34 0.58-4.75-0.25-0.42-0.92-0.67-1.92-1.17-1-0.5-5.91-2.92-6.83-3.25-0.92-0.33-1.58-0.5-2.25 0.5-0.67 1-2.58 3.25-3.17 3.92-0.58 0.66-1.16 0.75-2.16 0.25-1-0.5-4.23-1.57-8.05-4.96-2.97-2.65-4.97-5.92-5.56-6.92-0.58-1-0.06-1.54 0.44-2.04 0.46-0.44 1-1.17 1.5-1.75 0.5-0.58 0.67-1  1-1.67 0.33-0.66 0.17-1.25-0.08-1.75-0.25-0.5-2.05-5.37-2.9-7.37"
            fill="#ffffff"
        />
    </g>
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
    <div class="wa-phone-wrap">
      <div class="wa-country-select" id="wa-country-select">
        <div class="wa-country-selected" id="wa-country-selected">
          <span class="wa-country-flag" id="wa-country-flag">🇮🇩</span>
          <span class="wa-country-arrow">▾</span>
        </div>
        <div class="wa-country-dropdown" id="wa-country-dropdown">
          <input type="text" class="wa-country-search" id="wa-country-search" placeholder="Search country...">
          <ul class="wa-country-list" id="wa-country-list"></ul>
        </div>
      </div>
      <input type="hidden" id="wa-country-code" value="62">
      <input id="wa-number" type="tel" placeholder="81234567890">
    </div>
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

// AJAX endpoint to get fresh nonce (not affected by page cache)
add_action('wp_ajax_wa_greeting_nonce', 'wa_greeting_get_nonce');
add_action('wp_ajax_nopriv_wa_greeting_nonce', 'wa_greeting_get_nonce');

function wa_greeting_get_nonce() {
    wp_send_json_success(['nonce' => wp_create_nonce('wa_greeting_nonce')]);
}

// Save submission to custom post type
add_action('wp_ajax_wa_greeting_save', 'wa_greeting_save_submission');
add_action('wp_ajax_nopriv_wa_greeting_save', 'wa_greeting_save_submission');

function wa_greeting_save_submission() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wa_greeting_nonce')) {
        wp_send_json_error(['message' => 'Invalid request.']);
        return;
    }

    // Check if all necessary fields are set
    if (empty($_POST['name']) || empty($_POST['email']) || empty($_POST['number']) || empty($_POST['plugin'])) {
        wp_send_json_error(['message' => 'Required fields are missing']);
        return;
    }

    // Additional validation for hyphen-only values
    if (trim($_POST['name']) === '-' || trim($_POST['company']) === '-') {
        wp_send_json_error(['message' => 'Please enter a valid Name and Company name.']);
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
        'number'        => ltrim(sanitize_text_field($_POST['number']), '0'),
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

    // Save to Google Sheets if enabled
    require_once WA_GREETING_CHAT_PATH . 'includes/class-google-sheets.php';
    $gsheets = new WA_Greeting_Chat_Google_Sheets();
    if ($gsheets->is_enabled()) {
        $gsheets->append_data($data);
    }

    wp_send_json_success([
        'id' => $post_id,
        'admin_wa' => get_option('wa_admin_number', ''),
    ]);
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

// Redirect default CPT list to Dashboard page
add_action('admin_init', function () {
    global $pagenow;
    if ($pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'wa_submission' && !isset($_GET['page'])) {
        wp_redirect(admin_url('edit.php?post_type=wa_submission&page=wa-dashboard'));
        exit;
    }
});

// Handle Excel export via admin_post (clean, no prior output)
add_action('admin_post_wa_export_excel', 'wa_handle_excel_export');

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
    echo '<div class="wa-detail-header">';
    echo '<img src="' . esc_url(plugin_dir_url(WA_GREETING_CHAT_FILE) . 'assets/icon.svg') . '" alt="WA Greeting Chat" class="wa-detail-logo">';
    echo '<div class="wa-detail-header-text">';
    echo '<strong>' . esc_html(get_post_meta($post->ID, 'name', true)) . '</strong>';
    echo '<span>' . esc_html(get_the_date('d M Y, H:i', $post)) . '</span>';
    echo '</div>';
    echo '</div>';
    echo '<table class="form-table">';
    foreach ($fields as $field) {
        $value = get_post_meta($post->ID, $field, true);
        $label = isset($labels[$field]) ? $labels[$field] : ucfirst($field);
        echo '<tr>';
        echo '<th><label>' . esc_html($label) . '</label></th>';
        if ($field === 'message') {
            echo '<td><textarea class="large-text" rows="4" readonly>' . esc_textarea($value) . '</textarea></td>';
        } elseif ($field === 'email') {
            echo '<td><a href="mailto:' . esc_attr($value) . '">' . esc_html($value) . '</a></td>';
        } elseif ($field === 'url' && !empty($value)) {
            echo '<td><a href="' . esc_url($value) . '" target="_blank">' . esc_html($value) . '</a></td>';
        } else {
            echo '<td><input type="text" value="' . esc_attr($value) . '" class="regular-text" readonly></td>';
        }
        echo '</tr>';
    }
    echo '</table>';
}

// Register admin menu pages
add_action('admin_menu', function () {
    // Dashboard page
    add_submenu_page(
        'edit.php?post_type=wa_submission',
        'Dashboard',
        'Dashboard',
        'read',
        'wa-dashboard',
        'wa_render_dashboard_page'
    );

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

// Remove default CPT submenu and reorder: Dashboard, Services, All Submissions, Settings
add_action('admin_menu', function () {
    global $submenu;
    $parent = 'edit.php?post_type=wa_submission';

    // Remove default "All Submissions" CPT link
    remove_submenu_page($parent, $parent);

    if (!isset($submenu[$parent])) return;

    // Define desired order: Dashboard, Services, All Submissions, Settings
    $order_map = [
        'wa-dashboard'    => 0,
        'wa_service'      => 1, // taxonomy slug (partial match)
        'wa-submissions'  => 2,
        'wa-chat-settings'=> 3,
    ];
    $sorted = [];
    $rest   = [];

    foreach ($submenu[$parent] as $item) {
        $slug = $item[2];
        $placed = false;
        foreach ($order_map as $key => $pos) {
            if ($slug === $key || strpos($slug, 'taxonomy=' . $key) !== false) {
                $sorted[$pos] = $item;
                $placed = true;
                break;
            }
        }
        if (!$placed) {
            $rest[] = $item;
        }
    }
    ksort($sorted);
    $submenu[$parent] = array_values(array_merge($sorted, $rest));
}, 999);

// Enqueue admin styles/scripts for custom pages
add_action('admin_enqueue_scripts', function ($hook) {
    $is_submissions = strpos($hook, 'wa-submissions') !== false;
    $is_dashboard   = strpos($hook, 'wa-dashboard') !== false;
    $is_settings    = strpos($hook, 'wa-chat-settings') !== false;
    $is_detail      = ($hook === 'post.php' && isset($_GET['post']) && isset($_GET['action']) && $_GET['action'] === 'edit' && get_post_type((int) $_GET['post']) === 'wa_submission');

    if (!$is_submissions && !$is_dashboard && !$is_settings && !$is_detail) {
        return;
    }

    wp_enqueue_style(
        'wa-admin-style',
        plugin_dir_url(WA_GREETING_CHAT_FILE) . 'admin/admin-style.css',
        [],
        WA_GREETING_CHAT_VERSION
    );

    if ($is_submissions) {
        wp_enqueue_script(
            'wa-admin-script',
            plugin_dir_url(WA_GREETING_CHAT_FILE) . 'admin/admin-script.js',
            [],
            WA_GREETING_CHAT_VERSION,
            true
        );
    }

    if ($is_dashboard) {
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js',
            [],
            '4.4.7',
            true
        );
        wp_enqueue_script(
            'wa-dashboard-script',
            plugin_dir_url(WA_GREETING_CHAT_FILE) . 'admin/dashboard.js',
            ['chartjs'],
            WA_GREETING_CHAT_VERSION,
            true
        );

        // Dashboard data with transient cache (5 min TTL)
        $dashboard_data = get_transient('wa_dashboard_data');
        if ($dashboard_data === false) {
            $dashboard_data = wa_build_dashboard_data();
            set_transient('wa_dashboard_data', $dashboard_data, 5 * MINUTE_IN_SECONDS);
        }

        wp_localize_script('wa-dashboard-script', 'waDashboard', $dashboard_data);
    }
});

// Build dashboard data (cached via transient)
function wa_build_dashboard_data() {
    global $wpdb;

    // Single query for all summary counts (replaces 4 separate WP_Query calls)
    $now = current_time('mysql');
    $year = date('Y', strtotime($now));
    $month = date('m', strtotime($now));
    $day = date('d', strtotime($now));
    $week_start = date('Y-m-d 00:00:00', strtotime('monday this week', strtotime($now)));

    $summary = $wpdb->get_row($wpdb->prepare(
        "SELECT
            COUNT(*) AS total_all,
            SUM(CASE WHEN YEAR(post_date) = %d AND MONTH(post_date) = %d THEN 1 ELSE 0 END) AS total_month,
            SUM(CASE WHEN post_date >= %s THEN 1 ELSE 0 END) AS total_week,
            SUM(CASE WHEN YEAR(post_date) = %d AND MONTH(post_date) = %d AND DAY(post_date) = %d THEN 1 ELSE 0 END) AS total_today
         FROM {$wpdb->posts}
         WHERE post_type = 'wa_submission' AND post_status = 'publish'",
        $year, $month, $week_start, $year, $month, $day
    ));

    // Service group distribution
    $service_groups = $wpdb->get_results(
        "SELECT pm.meta_value AS label, COUNT(*) AS total
         FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE pm.meta_key = 'service_group'
           AND pm.meta_value != ''
           AND p.post_type = 'wa_submission'
           AND p.post_status = 'publish'
         GROUP BY pm.meta_value
         ORDER BY total DESC"
    );

    // Monthly trend (last 12 months)
    $monthly_trend = $wpdb->get_results(
        "SELECT YEAR(post_date) AS y, MONTH(post_date) AS m, COUNT(*) AS total
         FROM {$wpdb->posts}
         WHERE post_type = 'wa_submission'
           AND post_status = 'publish'
           AND post_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
         GROUP BY YEAR(post_date), MONTH(post_date)
         ORDER BY y ASC, m ASC"
    );

    // Top 10 companies
    $top_companies = $wpdb->get_results(
        "SELECT pm.meta_value AS company, COUNT(*) AS total
         FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE pm.meta_key = 'company'
           AND pm.meta_value != ''
           AND p.post_type = 'wa_submission'
           AND p.post_status = 'publish'
         GROUP BY pm.meta_value
         ORDER BY total DESC
         LIMIT 10"
    );

    // Build monthly trend labels
    $months_data = [];
    for ($i = 11; $i >= 0; $i--) {
        $date = strtotime("-{$i} months");
        $key = date('Y-n', $date);
        $months_data[$key] = ['label' => date('M Y', $date), 'total' => 0];
    }
    foreach ($monthly_trend as $row) {
        $key = $row->y . '-' . $row->m;
        if (isset($months_data[$key])) {
            $months_data[$key]['total'] = (int) $row->total;
        }
    }

    return [
        'summary' => [
            'total_all'   => (int) $summary->total_all,
            'total_month' => (int) $summary->total_month,
            'total_week'  => (int) $summary->total_week,
            'total_today' => (int) $summary->total_today,
        ],
        'serviceGroups' => array_map(function ($row) {
            return ['label' => $row->label, 'total' => (int) $row->total];
        }, $service_groups),
        'monthlyTrend' => [
            'labels' => array_column(array_values($months_data), 'label'),
            'data'   => array_column(array_values($months_data), 'total'),
        ],
        'topCompanies' => array_map(function ($row) {
            return ['company' => $row->company, 'total' => (int) $row->total];
        }, $top_companies),
    ];
}

// Invalidate caches when data changes
add_action('save_post_wa_submission', function () {
    delete_transient('wa_dashboard_data');
});
add_action('delete_post', function ($post_id) {
    if (get_post_type($post_id) === 'wa_submission') {
        delete_transient('wa_dashboard_data');
    }
});
add_action('created_wa_service', function () { delete_transient('wa_service_tree'); });
add_action('edited_wa_service', function () { delete_transient('wa_service_tree'); });
add_action('delete_wa_service', function () { delete_transient('wa_service_tree'); });

// Render dashboard page
function wa_render_dashboard_page() {
    ?>
    <div class="wrap wa-dashboard-wrap">
        <h1 class="wp-heading-inline">Dashboard</h1>
        <p class="description">Overview of WhatsApp chat submissions analytics.</p>

        <div class="wa-summary-cards">
            <div class="wa-card">
                <span class="wa-card-icon dashicons dashicons-format-chat"></span>
                <div class="wa-card-content">
                    <h3 id="card-total-all">0</h3>
                    <p>Total Submissions</p>
                </div>
            </div>
            <div class="wa-card">
                <span class="wa-card-icon dashicons dashicons-calendar-alt"></span>
                <div class="wa-card-content">
                    <h3 id="card-total-month">0</h3>
                    <p>This Month</p>
                </div>
            </div>
            <div class="wa-card">
                <span class="wa-card-icon dashicons dashicons-clock"></span>
                <div class="wa-card-content">
                    <h3 id="card-total-week">0</h3>
                    <p>This Week</p>
                </div>
            </div>
            <div class="wa-card">
                <span class="wa-card-icon dashicons dashicons-yes-alt"></span>
                <div class="wa-card-content">
                    <h3 id="card-total-today">0</h3>
                    <p>Today</p>
                </div>
            </div>
        </div>

        <div class="wa-charts-row">
            <div class="wa-chart-box">
                <h2>Service Group Distribution</h2>
                <div class="wa-chart-container wa-chart-doughnut">
                    <canvas id="wa-service-chart"></canvas>
                </div>
                <p class="wa-chart-empty" id="wa-service-empty" style="display:none;">No data available.</p>
            </div>
            <div class="wa-chart-box">
                <h2>Monthly Submission Trend</h2>
                <div class="wa-chart-container wa-chart-line">
                    <canvas id="wa-monthly-chart"></canvas>
                </div>
            </div>
        </div>

        <div class="wa-top-companies-box">
            <h2>Top 10 Companies</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width:50px;">No</th>
                        <th>Company</th>
                        <th style="width:120px;">Submissions</th>
                    </tr>
                </thead>
                <tbody id="wa-top-companies-body">
                    <tr><td colspan="3">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

// Render custom submissions page
function wa_render_submissions_page() {
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

        <form method="get" action="<?= esc_url(admin_url('edit.php')) ?>">
            <input type="hidden" name="post_type" value="wa_submission" />
            <input type="hidden" name="page" value="wa-submissions" />
            <?php
            $table->search_box('Search Submissions', 'wa-submission-search');
            $table->display();
            ?>
        </form>
    </div>
    <?php
}

// Handle Excel export (CSV format, compatible with Excel)
function wa_handle_excel_export() {
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

    // Service group filter
    $service_filter = isset($_GET['service_group_filter']) ? sanitize_text_field($_GET['service_group_filter']) : '';
    if (!empty($service_filter)) {
        $args['meta_query'][] = ['key' => 'service_group', 'value' => $service_filter, 'compare' => '='];
    }

    // Search filter
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    if (!empty($search)) {
        $search_query = [
            'relation' => 'OR',
            ['key' => 'name',          'value' => $search, 'compare' => 'LIKE'],
            ['key' => 'email',         'value' => $search, 'compare' => 'LIKE'],
            ['key' => 'company',       'value' => $search, 'compare' => 'LIKE'],
            ['key' => 'message',       'value' => $search, 'compare' => 'LIKE'],
            ['key' => 'number',        'value' => $search, 'compare' => 'LIKE'],
            ['key' => 'service_group', 'value' => $search, 'compare' => 'LIKE'],
            ['key' => 'plugin',        'value' => $search, 'compare' => 'LIKE'],
        ];
        if (!empty($service_filter)) {
            $args['meta_query']['relation'] = 'AND';
            $args['meta_query'][] = $search_query;
        } else {
            $args['meta_query'] = $search_query;
        }
    }

    $query = new WP_Query($args);

    // Generate filename
    $filename = 'wa-submissions';
    if ($date_from) $filename .= '-from-' . $date_from;
    if ($date_to)   $filename .= '-to-' . $date_to;
    $filename .= '-' . date('Ymd-His') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    // UTF-8 BOM so Excel reads encoding correctly
    fwrite($output, "\xEF\xBB\xBF");

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

        // Handle Google Sheets settings
        update_option('wa_gsheets_enabled', isset($_POST['wa_gsheets_enabled']) ? 'yes' : 'no');
        update_option('wa_gsheets_spreadsheet_id', sanitize_text_field($_POST['wa_gsheets_spreadsheet_id']));
        update_option('wa_gsheets_sheet_name', sanitize_text_field($_POST['wa_gsheets_sheet_name']));
        update_option('wa_gsheets_credentials', wp_unslash($_POST['wa_gsheets_credentials'])); // Preserves backslashes in JSON

        // Handle column mapping
        $mapping = [
            'date'          => intval($_POST['wa_gs_map_date']),
            'name'          => intval($_POST['wa_gs_map_name']),
            'email'         => intval($_POST['wa_gs_map_email']),
            'company'       => intval($_POST['wa_gs_map_company']),
            'service_group' => intval($_POST['wa_gs_map_service_group']),
            'service'       => intval($_POST['wa_gs_map_service']),
            'number'        => intval($_POST['wa_gs_map_number']),
            'message'       => intval($_POST['wa_gs_map_message']),
            'url'           => intval($_POST['wa_gs_map_url']),
        ];
        update_option('wa_gsheets_mapping', $mapping);

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

    $gsheets_enabled = get_option('wa_gsheets_enabled', 'no');
    $gsheets_spreadsheet_id = get_option('wa_gsheets_spreadsheet_id', '');
    $gsheets_sheet_name = get_option('wa_gsheets_sheet_name', 'Sheet1');
    $gsheets_credentials = get_option('wa_gsheets_credentials', '');
    $gsheets_mapping = get_option('wa_gsheets_mapping', [
        'date'          => 1,
        'name'          => 2,
        'email'         => 3,
        'company'       => 4,
        'service_group' => 5,
        'service'       => 6,
        'number'        => 7,
        'message'       => 8,
        'url'           => 9,
    ]);

    ?>
    <div class="wrap wa-settings-wrap">
        <div class="wa-plugin-header">
            <img src="<?= esc_url(plugin_dir_url(WA_GREETING_CHAT_FILE) . 'assets/logo.svg') ?>" alt="WA Greeting Chat" class="wa-plugin-logo">
            <span class="wa-plugin-version">v<?= WA_GREETING_CHAT_VERSION ?></span>
        </div>
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
                        <p class="description">Contoh: +6281234567890</p>
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
                <tr>
                    <th colspan="2"><h3>Google Sheets Integration</h3></th>
                </tr>
                <tr>
                    <th><label for="wa_gsheets_enabled">Enable Google Sheets</label></th>
                    <td>
                        <input type="checkbox" name="wa_gsheets_enabled" id="wa_gsheets_enabled" value="yes" <?= checked($gsheets_enabled, 'yes') ?>>
                        <p class="description">Simpan setiap submission ke Google Spreadsheet.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="wa_gsheets_spreadsheet_id">Spreadsheet ID</label></th>
                    <td>
                        <input type="text" name="wa_gsheets_spreadsheet_id" id="wa_gsheets_spreadsheet_id" value="<?= esc_attr($gsheets_spreadsheet_id) ?>" class="regular-text">
                        <p class="description">ID Spreadsheet bisa ditemukan di URL: https://docs.google.com/spreadsheets/d/<b>SPREADSHEET_ID</b>/edit</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="wa_gsheets_sheet_name">Sheet Name</label></th>
                    <td>
                        <input type="text" name="wa_gsheets_sheet_name" id="wa_gsheets_sheet_name" value="<?= esc_attr($gsheets_sheet_name) ?>" class="regular-text">
                        <p class="description">Nama sheet (tab), contoh: Sheet1</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="wa_gsheets_credentials">Google API JSON Credentials</label></th>
                    <td>
                        <textarea name="wa_gsheets_credentials" id="wa_gsheets_credentials" rows="8" class="large-text code"><?= esc_textarea($gsheets_credentials) ?></textarea>
                        <p class="description">Paste seluruh isi file .json credentials Anda di sini.</p>
                    </td>
                </tr>
                <tr>
                    <th>Column Mapping</th>
                    <td>
                        <p class="description">Tentukan urutan kolom di Google Sheets (1 = Kolom A, 2 = Kolom B, dst.). Isi 0 untuk tidak mengirim kolom tersebut.</p>
                        <table class="wa-mapping-table">
                            <tr><td>Date</td><td><input type="number" name="wa_gs_map_date" value="<?= $gsheets_mapping['date'] ?>" min="0" style="width:60px"></td></tr>
                            <tr><td>Name</td><td><input type="number" name="wa_gs_map_name" value="<?= $gsheets_mapping['name'] ?>" min="0" style="width:60px"></td></tr>
                            <tr><td>Email</td><td><input type="number" name="wa_gs_map_email" value="<?= $gsheets_mapping['email'] ?>" min="0" style="width:60px"></td></tr>
                            <tr><td>Company</td><td><input type="number" name="wa_gs_map_company" value="<?= $gsheets_mapping['company'] ?>" min="0" style="width:60px"></td></tr>
                            <tr><td>Service Group</td><td><input type="number" name="wa_gs_map_service_group" value="<?= $gsheets_mapping['service_group'] ?>" min="0" style="width:60px"></td></tr>
                            <tr><td>Service</td><td><input type="number" name="wa_gs_map_service" value="<?= $gsheets_mapping['service'] ?>" min="0" style="width:60px"></td></tr>
                            <tr><td>Phone Number</td><td><input type="number" name="wa_gs_map_number" value="<?= $gsheets_mapping['number'] ?>" min="0" style="width:60px"></td></tr>
                            <tr><td>Message</td><td><input type="number" name="wa_gs_map_message" value="<?= $gsheets_mapping['message'] ?>" min="0" style="width:60px"></td></tr>
                            <tr><td>Page URL</td><td><input type="number" name="wa_gs_map_url" value="<?= $gsheets_mapping['url'] ?>" min="0" style="width:60px"></td></tr>
                        </table>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Settings', 'primary', 'wa_chat_settings_submit'); ?>
        </form>
    </div>
    <?php
}