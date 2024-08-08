<?php
/*
Plugin Name: Video Revealer Form
Description: This is a plugin for collecting email and phone numbers, and revealing a video in return.
Version: 1.0
Author: Pasha Loguinov
*/

function video_revealer_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'video_revealer_submissions';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        phone varchar(255) NOT NULL,
        company varchar(255) NOT NULL,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'video_revealer_create_table');

function encrypt_data($data) {
    $encryption_key = base64_decode(ENCRYPTION_KEY);
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $encryption_key, 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

function decrypt_data($data) {
    $encryption_key = base64_decode(ENCRYPTION_KEY);
    list($encrypted_data, $iv) = array_pad(explode('::', base64_decode($data), 2), 2, null);
    return openssl_decrypt($encrypted_data, 'aes-256-cbc', $encryption_key, 0, $iv);
}

function update_youtube_url($url) {
    $sanitized_url = esc_url($url);
    update_option('my_youtube_video_url', $sanitized_url);
}

function get_youtube_url() {
    $youtube_url = get_option('my_youtube_video_url', '');
    
    preg_match('/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $youtube_url, $matches);
    
    if (isset($matches[1])) {
        $youtube_url = $matches[1];
    } else {
        $youtube_url = '';
    }
    
    return $youtube_url;
}

function video_revealer_form_shortcode() {
    ob_start();

    echo '<form id="video-revealer-form" method="post">
            <div id="video-revealer-greeting">
                <h1>Discover how the H4P (Home Equity Conversion Mortgage for Purchase) can boost your real estate business while providing exceptional value to your senior clients.</h1>
                <p>Please provide the following details, and you will receive a direct link to the webinar recording:</p>
                <div aria-live="polite" id="form-error-message"></div>
            </div>
            <input id="name" type="text" name="name" placeholder="Enter your name"><span aria-live="polite" class="error-message"></span>
            <input id="email" type="email" name="email" placeholder="Enter your email"><span aria-live="polite" class="error-message"></span>
            <input id="phone" type="text" name="phone" placeholder="Enter your phone number"><span aria-live="polite" class="error-message"></span>
            <input id="company" type="text" name="company" placeholder="Enter your company"><span aria-live="polite" class="error-message"></span>
            <input type="hidden" name="recaptcha_response" id="recaptcha-response">';
            wp_nonce_field('video_revealer_form_action', 'video_revealer_form_nonce');
    echo    '<div id="consent-container">
                <div class="checkbox-and-label">
                    <input type="checkbox" id="consent-checkbox" name="consent-checkbox">
                    <label for="consent-checkbox">By clicking this box, you agree to receive communication (Calls, Emails & Text) from Signet Mortgage.*</label>
                </div>
                <span aria-live="polite" class="error-message"></span> 
            </div>
            <p class="disclaimer">*You can be 100% sure we will never ever sell your contact information.</p>
            <button class="video-revealer-submit" type="submit" name="submit_video_revealer_form">
                <div class="spinner-border text-light" role="status" style="display:none;">
                    <span class="sr-only">Loading...</span>
                </div>
                Submit
            </button>
            <input type="hidden" name="action" value="video_revealer_form_submit">
          </form>';

    echo '<div id="video-container" style="display:none;">';
    echo '<iframe id="youtube-video" width="560" height="315" src="https://www.youtube-nocookie.com/embed/' . get_youtube_url() . '?enablejsapi=1" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
    echo '</div>';

    echo '<div id="finished-video-button-container" style="display:none;">
            <a href="https://signetmortgage.com/schedule-a-conversation/" target="_blank" id="schedule-conversation">Schedule a Conversation</a>
            <a href="https://signetmortgage.com/for-realtors/" target="_blank" id="realtors">Realtors</a>
          </div>';

    return ob_get_clean();
}
add_shortcode('video_revealer_form', 'video_revealer_form_shortcode');

add_action('wp_ajax_video_revealer_form_submit', 'handle_video_revealer_form_submission');
add_action('wp_ajax_nopriv_video_revealer_form_submit', 'handle_video_revealer_form_submission'); 

function handle_video_revealer_form_submission() {
    if (isset($_POST['action']) && $_POST['action'] == 'video_revealer_form_submit') {
        header('Content-Type: application/json');

        $errors = [];
    
        $recaptcha_secret = RECAPTCHA_SECRET_KEY; 
        $recaptcha_response = $_POST['recaptchaResponse'];
        $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
            'body' => [
                'secret' => $recaptcha_secret,
                'response' => $recaptcha_response
            ]
        ]);
        $response_body = wp_remote_retrieve_body($response);
        $result = json_decode($response_body, true);
    
        if (!$result['success'] || $result['score'] < 0.6) {
            $errors['recaptcha'] = 'reCAPTCHA verification failed. Please try again.';
        }
    
        if (!wp_verify_nonce($_POST['nonceValue'], 'video_revealer_form_action')) {
            $errors['nonce'] = 'Nonce verification failed. Please refresh the page and try again.';
        }

        if (isset($_POST['consentCheckbox']) && $_POST['consentCheckbox'] === 'off') {
            $errors['consentCheckbox'] = 'You must agree to the privacy policy to proceed.';
        }
        
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $phone = sanitize_text_field($_POST['phone']);
        $company = sanitize_text_field($_POST['company']);
    
        if (!is_email($email)) {
            $errors['email'] = 'Please enter a valid email address.';
        } elseif (!preg_match('/^(\+1\s?)?(\(?\d{3}\)?[\s.-]?)?\d{3}[\s.-]?\d{4}$/', $phone)) {
            $errors['phone'] = 'Please enter a valid phone number.';
        }

        if (empty($name)) {
            $errors['name'] = 'Please enter your name.';
        } elseif (!preg_match('/^[a-zA-Z\s]+$/', $name)) {
            $errors['name'] = 'Name can only contain letters and spaces.';
        }
        
        if (empty($company)) {
            $errors['company'] = 'Please enter your company name.';
        }
    
        if (!empty($errors)) {
            echo json_encode(['status' => 'error', 'errors' => $errors]);
            exit;
        }
    
        echo json_encode(['status' => 'success', 'message' => 'Form submitted successfully!']);

        $company = stripslashes(sanitize_text_field($_POST['company']));

        global $wpdb;
        $table_name = $wpdb->prefix . 'video_revealer_submissions';
        
        $encrypted_email = encrypt_data($email, base64_decode(ENCRYPTION_KEY));
        $encrypted_phone = encrypt_data($phone, base64_decode(ENCRYPTION_KEY));
        
        $data = array(
            'name' => $name,
            'email' => $encrypted_email, 
            'phone' => $encrypted_phone, 
            'company' => $company,
            'time' => current_time('mysql') 
        );
        $format = array('%s', '%s', '%s', '%s', '%s');
        
        $wpdb->insert($table_name, $data, $format);

        $emails_option = get_option('my_notification_emails', '');
        $emails_array = array_map('trim', explode(',', $emails_option));
        $valid_emails = array_filter($emails_array, function($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        });

        if (!empty($valid_emails)) {
            $to = $valid_emails;
            $subject = 'Someone asked to view the H4P webinar video';
            $message = "Name: $name\nEmail: $email\nPhone: $phone\nCompany: $company";
            wp_mail($to, $subject, $message);
        } else {
            $to = 'pasha@ewebsiteservices.com';
            $subject = 'Someone asked to view the H4P webinar video';
            $message = "Name: $name\nEmail: $email\nPhone: $phone\nCompany: $company\n\nNo valid email addresses were found in the notification emails list.";
            wp_mail($to, $subject, $message);
        }
        exit;
    }
}
add_action('wp_footer', 'handle_video_revealer_form_submission');

function video_revealer_admin_menu() {
    add_menu_page('Video Revealer Submissions', 'Video Revealer', 'manage_options', 'video-revealer-submissions', 'video_revealer_submissions_page');
}
add_action('admin_menu', 'video_revealer_admin_menu');

function video_revealer_submissions_page() {
    global $wpdb; 
    $table_name = $wpdb->prefix . 'video_revealer_submissions';
    
    if (isset($_POST['update_video_url'])) {
        $new_video_url = sanitize_text_field($_POST['video_url']);
        update_youtube_url($new_video_url);
    }

    if (isset($_POST['update_notification_emails'])) {
        $new_emails = sanitize_text_field($_POST['notification_emails']);
        update_option('my_notification_emails', $new_emails);
    }

    $submissions = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);

    $current_youtube_url = get_option('my_youtube_video_url', '');
    $current_emails = get_option('my_notification_emails', '');

    ?>
    <h2>Submissions</h2>
    <?php if (current_user_can('manage_options')): ?>
        <form id="update-video-url" method="post">
            <label for="video_url">Video URL:</label>
            <input type="text" id="video-url" name="video_url" placeholder="Enter new video URL" value="<?php echo esc_attr($current_youtube_url); ?>" required>
            <input type="submit" id="update-video-url-button" name="update_video_url" value="Update Video">
        </form>

        <form id="update-notification-emails" method="post">
            <label for="notification_emails">Notification Emails:</label>
            <input type="text" id="notification-emails" name="notification_emails" placeholder="Enter emails, separated by a comma and space" value="<?php echo esc_attr($current_emails); ?>" required>
            <input type="submit" id="update-notification-emails-button" name="update_notification_emails" value="Update Emails">
        </form>
    <?php endif; ?>
    <?php if (!empty($submissions)): ?>
        <form method="post">
            <table>
                <tr>
                    <th>Name</th>
                    <th>Company</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Timestamp</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($submissions as $submission): ?>
                    <tr>
                        <td><?php echo esc_html($submission['name']); ?></td>
                        <td><?php echo esc_html($submission['company']); ?></td>
                        <td><?php echo esc_html(decrypt_data($submission['email'])); ?></td>
                        <td><?php echo esc_html(decrypt_data($submission['phone'])); ?></td>
                        <td><?php echo esc_html($submission['time']); ?></td> 
                        <td>
                            <button type="submit" class="delete-button" name="delete_submission" value="<?php echo esc_attr($submission['id']); ?>">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </form>
    <?php else: ?>
        <div class="no-submissions">
            <p>No submissions yet.</p>
        </div>
    <?php endif;
}

function handle_delete_submission() {
    global $wpdb; 
    $table_name = $wpdb->prefix . 'video_revealer_submissions';

    if (isset($_POST['delete_submission'])) {
        $submission_id = intval($_POST['delete_submission']); 

        $wpdb->delete($table_name, ['id' => $submission_id], ['%d']);

        wp_redirect($_SERVER['REQUEST_URI']);
        exit;
    }
}
add_action('admin_init', 'handle_delete_submission');

add_action('wp_head', function() {
    echo '<script type="text/javascript">var ajaxurl = "' . admin_url('admin-ajax.php') . '";</script>';
});

function enqueue_scripts_and_styles() {
    wp_enqueue_script('jquery');

    if (is_page('realtors-h4p-invite')) {
        wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js?render=6Ld4fhUqAAAAAI8Kv9ZybK7LtlPqTNeYMaVy4BpA', array(), null, true);
        $inline_script = <<<EOD
        grecaptcha.ready(function() {
            grecaptcha.execute('6Ld4fhUqAAAAAI8Kv9ZybK7LtlPqTNeYMaVy4BpA', {action: 'submit'}).then(function(token) {
                document.getElementById('recaptchaResponse').value = token;
            });
        });
        EOD;
        wp_add_inline_script('google-recaptcha', $inline_script);

        wp_enqueue_script('video-revealer-form', plugins_url('/js/video-revealer-form.js', __FILE__), array('jquery'), '1.1', true);
        wp_localize_script('video-revealer-form', 'ajax_object', array('ajaxurl' => admin_url('admin-ajax.php')));

        wp_enqueue_style('video-revealer-form', plugin_dir_url(__FILE__) . 'css/style.css', array(), '1.1.0', 'all');
    }

}
add_action('wp_enqueue_scripts', 'enqueue_scripts_and_styles');

function enqueue_video_revealer_scripts() {
    if (is_page('realtors-h4p-invite')) {
        wp_enqueue_script('youtube-iframe-api', 'https://www.youtube.com/iframe_api', array(), null, true);

        wp_enqueue_script('video-revealer', plugin_dir_url(__FILE__) . 'js/youtube.js', array('youtube-iframe-api'), '1.1', true);
    }
}
add_action('wp_enqueue_scripts', 'enqueue_video_revealer_scripts');

function enqueue_admin_styles($hook_suffix) {
    if ($hook_suffix == 'toplevel_page_video-revealer-submissions') {
        wp_enqueue_style('video-revealer-form', plugin_dir_url(__FILE__) . 'css/submissions-style.css', array(), '1.0.0');
    }
}
add_action('admin_enqueue_scripts', 'enqueue_admin_styles');
