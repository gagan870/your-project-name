<?php
if (!defined('ABSPATH')) exit;

function my_form_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'my_form_data';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        email varchar(100) NOT NULL,
        message longtext,
        active tinyint(1) DEFAULT 0,
        submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}