<?php
if (!defined('ABSPATH')) exit;

// Enqueue SweetAlert2
function my_form_enqueue_assets() {
    $screen = get_current_screen();
    if ($screen && $screen->id === 'toplevel_page_my-form-page') {
        wp_enqueue_style('sweetalert2-css', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css');
        wp_enqueue_script('sweetalert2-js', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), null, true);

        wp_localize_script('sweetalert2-js', 'myFormAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'delete_nonce' => wp_create_nonce('my_form_delete_record'),
            'send_nonce'   => wp_create_nonce('my_form_send_confirmation')
        ));

        // Delete Button JavaScript
        wp_add_inline_script('sweetalert2-js', '
        jQuery(function($) {
            $(".delete-record-btn").on("click", function(e) {
                e.preventDefault();
                const recordId = $(this).data("id");
                const row = $(this).closest("tr");

                Swal.fire({
                    title: "Are you sure?",
                    text: "This record will be permanently deleted!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Yes, Delete it!",
                    cancelButtonText: "Cancel",
                    confirmButtonColor: "#d33"
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: "Deleting...",
                            allowOutsideClick: false,
                            didOpen: () => { Swal.showLoading(); }
                        });

                        fetch(myFormAjax.ajax_url, {
                            method: "POST",
                            headers: { "Content-Type": "application/x-www-form-urlencoded" },
                            body: new URLSearchParams({
                                action: "my_form_delete_record",
                                nonce: myFormAjax.delete_nonce,
                                record_id: recordId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                row.fadeOut(400, function() { $(this).remove(); });
                                Swal.fire("Deleted!", "Record has been deleted.", "success");
                            } else {
                                Swal.fire("Error!", data.data || "Failed to delete.", "error");
                            }
                        })
                        .catch(() => {
                            Swal.fire("Error!", "Network error.", "error");
                        });
                    }
                });
            });
        });
        ');
    }
}
add_action('admin_enqueue_scripts', 'my_form_enqueue_assets');

// AJAX Delete Handler
add_action('wp_ajax_my_form_delete_record', 'my_form_delete_record_callback');
function my_form_delete_record_callback() {
    check_ajax_referer('my_form_delete_record', 'nonce');

    global $wpdb;
    $table_name = $wpdb->prefix . 'my_form_data';
    $record_id = absint($_POST['record_id']);

    if ($record_id > 0 && $wpdb->delete($table_name, ['id' => $record_id], ['%d'])) {
        wp_send_json_success();
    } else {
        wp_send_json_error('Record not found or already deleted.');
    }
}

// AJAX Send Confirmation Email Handler
add_action('wp_ajax_my_form_send_confirmation', 'my_form_send_confirmation_callback');
function my_form_send_confirmation_callback() {
    check_ajax_referer('my_form_send_confirmation', 'nonce');

    $name    = sanitize_text_field($_POST['name']);
    $email   = sanitize_email($_POST['email']);
    $message = sanitize_textarea_field($_POST['message']);

    $sent = my_form_send_email_to_user($name, $email, $message);

    if ($sent) {
        wp_send_json_success();
    } else {
        wp_send_json_error('Failed to send email.');
    }
}

// Admin Menu
add_action('admin_menu', function() {
    add_menu_page('My Form', 'My Form', 'manage_options', 'my-form-page', 'my_form_page_content', 'dashicons-feedback', 82);
});

function my_form_page_content() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'my_form_data';

    $edit_data = null;
    if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'edit') {
        $edit_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", absint($_GET['id'])));
    }

    $alert_script = '';

    if (isset($_POST['save_my_form']) && current_user_can('manage_options')) {
        $name    = sanitize_text_field($_POST['name']);
        $email   = sanitize_email($_POST['email']);
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $active  = isset($_POST['active']) ? 1 : 0;
        $record_id = absint($_POST['record_id'] ?? 0);

        if (!empty($name) && !empty($email) && is_email($email)) {
            $data = ['name' => $name, 'email' => $email, 'message' => $message, 'active' => $active];

            if ($record_id > 0) {
                $wpdb->update($table_name, $data, ['id' => $record_id]);
                $action = 'updated';
            } else {
                $wpdb->insert($table_name, $data);
                $action = 'saved';
            }

            $alert_script = "Swal.fire({
                title: 'Data {$action} Successfully!',
                html: `
                    <p><strong>Name:</strong> " . esc_js($name) . "</p>
                    <p><strong>Saved Email:</strong> " . esc_js($email) . "</p>
                    <p>Send confirmation email to:</p>
                    <input type=\"email\" id=\"send-email\" class=\"swal2-input\" placeholder=\"recipient@example.com\" value=\"" . esc_js($email) . "\">
                `,
                icon: 'success',
                showCancelButton: true,
                confirmButtonText: 'Send Email',
                cancelButtonText: 'Skip',
                preConfirm: () => {
                    const sendTo = document.getElementById('send-email').value.trim();
                    if (sendTo && !/^\S+@\S+\.\S+$/.test(sendTo)) {
                        Swal.showValidationMessage('Invalid email address');
                        return false;
                    }
                    return sendTo || false;
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    Swal.fire({
                        title: 'Sending...',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    fetch(myFormAjax.ajax_url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action: 'my_form_send_confirmation',
                            nonce: myFormAjax.send_nonce,
                            name: '" . esc_js($name) . "',
                            email: result.value,
                            message: '" . esc_js($message) . "'
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Sent!', 'Email sent to ' + result.value, 'success');
                        } else {
                            Swal.fire('Error!', data.data || 'Failed to send.', 'error');
                        }
                    })
                    .catch(() => {
                        Swal.fire('Error!', 'Network error.', 'error');
                    });
                } else if (result.isDismissed) {
                    Swal.fire('Skipped', 'Email was not sent.', 'info');
                }
            });";
        } else {
            $alert_script = "Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Please fill valid Name and Email.',
                confirmButtonText: 'OK'
            });";
        }

        if ($alert_script) {
            wp_add_inline_script('sweetalert2-js', "document.addEventListener('DOMContentLoaded', function() { $alert_script });");
        }
    }

    $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY submitted_at DESC");
    ?>
    <div class="wrap">
        <h1><?php echo $edit_data ? 'Edit Record' : 'My Form'; ?></h1>

        <form method="post">
            <input type="hidden" name="record_id" value="<?php echo $edit_data ? esc_attr($edit_data->id) : '0'; ?>">

            <table class="form-table">
                <tr>
                    <th><label for="name">Name *</label></th>
                    <td><input type="text" name="name" class="regular-text" value="<?php echo $edit_data ? esc_attr($edit_data->name) : ''; ?>" required></td>
                </tr>
                <tr>
                    <th><label for="email">Email *</label></th>
                    <td><input type="email" name="email" class="regular-text" value="<?php echo $edit_data ? esc_attr($edit_data->email) : ''; ?>" required></td>
                </tr>
                <tr>
                    <th><label for="message">Message</label></th>
                    <td><textarea name="message" rows="5" class="large-text"><?php echo $edit_data ? esc_textarea($edit_data->message) : ''; ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="active">Active</label></th>
                    <td><label><input type="checkbox" name="active" value="1" <?php checked(1, $edit_data ? $edit_data->active : 0); ?>> Yes</label></td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="save_my_form" class="button button-primary" value="<?php echo $edit_data ? 'Update Data' : 'Save Data'; ?>">
                <?php if ($edit_data): ?>
                    <a href="<?php echo admin_url('admin.php?page=my-form-page'); ?>" class="button">Cancel</a>
                <?php endif; ?>
            </p>
        </form>

        <hr>

        <h2>All Saved Data</h2>
        <?php if ($results): ?>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th><th>Name</th><th>Email</th><th>Message</th><th>Active</th><th>Date & Time</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row): ?>
                        <tr>
                            <td><?php echo esc_html($row->id); ?></td>
                            <td><?php echo esc_html($row->name); ?></td>
                            <td><?php echo esc_html($row->email); ?></td>
                            <td><?php echo esc_html($row->message); ?></td>
                            <td><?php echo $row->active ? 'Yes' : 'No'; ?></td>
                            <td><?php echo esc_html($row->submitted_at); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=my-form-page&action=edit&id=' . $row->id); ?>">Edit</a> |
                                <button type="button" class="button delete-record-btn" data-id="<?php echo esc_attr($row->id); ?>">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No data saved yet.</p>
        <?php endif; ?>
    </div>
    <?php
    
}