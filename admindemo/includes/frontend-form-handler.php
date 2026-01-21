<?php
/**
 * File: includes/frontend-form-handler.php
 * Purpose: Handle frontend form submissions (popup / shortcode / page)
 *          - Save to DB
 *          - Send auto-confirmation email to user
 *          - Return JSON for SweetAlert2
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Include email function if not already loaded
require_once __DIR__ . '/email-functions.php';

// AJAX handler for frontend (logged-in + guests)
add_action('wp_ajax_frontend_my_form_submit',        'frontend_my_form_submit_handler');
add_action('wp_ajax_nopriv_frontend_my_form_submit', 'frontend_my_form_submit_handler');

function frontend_my_form_submit_handler() {
    // Security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'frontend_my_form_action')) {
        wp_send_json_error(['message' => 'Security check failed']);
        exit;
    }

    $name    = isset($_POST['name'])    ? sanitize_text_field($_POST['name'])    : '';
    $email   = isset($_POST['email'])   ? sanitize_email($_POST['email'])       : '';
    $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';

    // Basic validation
    if (empty($name) || empty($email) || !is_email($email)) {
        wp_send_json_error(['message' => 'Please fill Name and valid Email']);
        exit;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'my_form_data';

    $inserted = $wpdb->insert(
        $table_name,
        [
            'name'       => $name,
            'email'      => $email,
            'message'    => $message,
            'active'     => 1,                    // auto-active for frontend submissions
            'date_time'  => current_time('mysql'), // match your column name
        ],
        ['%s', '%s', '%s', '%d', '%s']
    );

    if ($inserted === false) {
        wp_send_json_error(['message' => 'Failed to save your data. Please try again.']);
        exit;
    }

    // Automatically send confirmation email
    $email_sent = my_form_send_email_to_user($name, $email, $message);

    // Response for SweetAlert
    wp_send_json_success([
        'message' => 'Thank you! We received your message.<br>Confirmation sent to: <strong>' . esc_html($email) . '</strong>',
        'email_sent' => $email_sent ? true : false
    ]);
}