<?php
function show_simple_custom_form_shortcode(){
ob_start(); // start collecting HTML

if( isset($_POST['simple_form_submitted'])){
    $username = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $message = sanitize_textarea_field($_POST['message']);
  
    // Very basic validation
        if (!empty($name) && !empty($email) && is_email($email)) {

            global $wpdb;
            $table = $wpdb->prefix . 'my_form_data';

            $wpdb->insert(
                $table,
                [
                    'name'      => $name,
                    'email'     => $email,
                    'message'   => $message,
                    'active'    => 1,
                    'date_time' => current_time('mysql')
                ],
                ['%s', '%s', '%s', '%d', '%s']
            );

            // Optional: send your email
            if (function_exists('my_form_send_email_to_user')) {
                my_form_send_email_to_user($name, $email, $message);
            }

            echo '<div style="background:#d4edda; color:#155724; padding:15px; margin:20px 0; border:1px solid #c3e6cb; text-align:center;">';
            echo '<strong>Thank you!</strong> Your message has been sent.';
            echo '</div>';
        } else {
            echo '<div style="background:#f8d7da; color:#721c24; padding:15px; margin:20px 0; border:1px solid #f5c6cb; text-align:center;">';
            echo 'Please fill name and valid email.';
            echo '</div>';
        }

}

?>


<form method="POST" style="display:flex; flex-direction:column; gap:15px;">
  <input type="text" placeholder="name" name="name" required style="padding:12px; font-size:16px;">
  <input type="email" placeholder="email" name="email" required style="padding:12px; font-size:16px;">
  <textarea type="message" placeholder="your messgage" name="message" required style="padding:12px; font-size:16px;"></textarea>
  <button type="submit" name="simple_form_submitted" value="1" required style="padding:12px; font-size:16px;">
                SEND
 </button>
</form>

<?php
return ob_get_clean(); // return collected HTML
}
add_shortcode('simple_custom_form','show_simple_custom_form_shortcode');

