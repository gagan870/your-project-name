<?php
/**
 * Enqueue Parent and Child Theme Styles
 */
function mychildtheme_enqueue_styles() {

    // Parent theme style
    wp_enqueue_style(
        'parent-style',
        get_template_directory_uri() . '/style.css',
        [],
        wp_get_theme(get_template())->get('Version')
    );

    // Child theme style
    wp_enqueue_style(
        'child-style',
        get_stylesheet_directory_uri() . '/style.css',
        ['parent-style'], // dependency
        wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'mychildtheme_enqueue_styles');




// Include activator and email functions
require_once get_stylesheet_directory() . '/admindemo/includes/activator.php';
require_once get_stylesheet_directory() . '/admindemo/includes/email-functions.php';
require_once get_stylesheet_directory() . '/admindemo/my-custom-form.php';
require_once get_stylesheet_directory() . '/admindemo/includes/frontend-form-handler.php';

// table create one time create for adminpage 
add_action('admin_init', function() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'my_form_data';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
        my_form_create_table();
    }
});

// sweet aler2 load for myform page 
function my_form_enqueue_sweetalert() {
    $screen = get_current_screen();
    if ($screen && $screen->id === 'toplevel_page_my-form-page') {
        // SweetAlert2 CSS
        wp_enqueue_style('sweetalert2-css', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css');

        // SweetAlert2 JS
        wp_enqueue_script('sweetalert2-js', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), null, true);
    }
}
add_action('admin_enqueue_scripts', 'my_form_enqueue_sweetalert');