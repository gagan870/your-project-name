<?php
if (!defined('ABSPATH')) exit;

function my_form_send_email_to_user($name, $email, $message) {
    if (!is_email($email)) return false;

    $subject = "Thank You, {$name}! We Received Your Message";

    $body = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial; background: #f6f6f6; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 40px; text-align: center; }
            .content { padding: 30px; }
            .message { background: #f9f9f9; padding: 20px; border-left: 5px solid #667eea; margin: 20px 0; }
            .footer { background: #eee; padding: 20px; text-align: center; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Hi ' . esc_html($name) . '!</h1>
                <p>Thank you for your message</p>
            </div>
            <div class="content">
                <p>Dear <strong>' . esc_html($name) . '</strong>,</p>
                <p>We have received your message on <strong>' . date('F j, Y') . '</strong>:</p>
                <p>A custom message from admin:</p>
                <div class="message-box">' . nl2br(esc_html($message)) . '</div>
                <p>We will get back to you soon!</p>
                <p>Best regards,<br><strong>My Form Team</strong></p>
            </div>
            <div class="footer">
                &copy; ' . date('Y') . ' My Form. All rights reserved.
            </div>
        </div>
    </body>
    </html>';

    $headers = ['Content-Type: text/html; charset=UTF-8'];

    return wp_mail($email, $subject, $body, $headers);
}