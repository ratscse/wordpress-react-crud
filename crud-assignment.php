<?php 

/**
 * Plugin Name: CRUD Assignment
 * Plugin URI: https://github.com/ratan
 * Description: Plugin to learn CRUD
 * Version: 1.0.0
 * Author: Ratan
 * Author URI: https://github.com/ratan
 */


 class Crud_Assignment
 {
    // Constructor for the class
    public function __construct( ) {
        register_activation_hook(__FILE__, [$this, 'register_wpdb_table']);
        add_action('init', [$this, 'init']);
    }

    // Initialize the plugin
    public function init() {
         add_action('admin_menu', [$this, 'admin_menu']);
         add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
         $this->register_constants();
         add_action('wp_ajax_add_new_item', [$this, 'add_new_item']);
         add_action('wp_ajax_show_all_item', [$this, 'show_all_item']);
         add_action('wp_ajax_delete_item', [$this, 'delete_item']);
         add_action('wp_ajax_edit_item', [$this, 'edit_item']);
         add_action('wp_ajax_update_item', [$this, 'update_item']);
    }

    // Register the constants plugin url and path
    private function register_constants()
    {
        define('CRUD_ASSIGNMENT_URL', plugin_dir_url(__FILE__));
        define('CRUD_ASSIGNMENT_PATH', plugin_dir_path(__FILE__));
    }

    // Function to add the admin menu
    public function admin_menu()
    {
        add_menu_page( 
            'CRUD Assignment', 
            'CRUD Assignment', 
            'manage_options', 
            'crud-assignment', 
            [$this, 'admin_menu_page_settings'], 
            'dashicons-settings', 
            7 
        );
    }

    // Callback function to display the admin menu page
    public function admin_menu_page_settings()
    {
        echo '<div id="crud-assignment-settings"></div>';
    }

    // Enqueue scripts and styles for the admin menu
    public function admin_enqueue_scripts($hook)
    {
        if($hook !== 'toplevel_page_crud-assignment')
        {
            return;
        }

        wp_enqueue_style('wp-components');

        $main_asset = require CRUD_ASSIGNMENT_PATH . 'assets/settingsOutput/main.asset.php';

        wp_enqueue_script( 
            'crud-assignment' , 
            CRUD_ASSIGNMENT_URL .'assets/settingsOutput/main.js',
            $main_asset['dependencies'],
            $main_asset['version'],     
            ["in_footer" => true]
        );
         
        // Localize script for AJAX calls
        wp_localize_script('crud-assignment',
        'crudAssignment',
        [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            '_ajax_nonce' => wp_create_nonce('crud-assignment-nonce'),
        ]);
    }

    // Create database table on plugin activation
    public function register_wpdb_table()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'crud_assignment';
        $charset_collate = $wpdb->get_charset_collate();

        $query = "CREATE TABLE IF NOT EXISTS $table_name (
            id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charset_collate;";

        if ( ! function_exists( 'dbDelta' ) ){
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        } 

        dbDelta( $query );
    }

    // Adding a new item via AJAX
    public function add_new_item()
    {
        check_ajax_referer('crud-assignment-nonce', 'nonce');

        global $wpdb;     

        $table_name = $wpdb->prefix . 'crud_assignment';

        if ( empty( $_POST['name'] ) || empty( $_POST['email'] ) ) {
            wp_send_json_error();
            return;
        }

        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);

        $data = array(
            'name' => $name,
            'email' => $email,
        );

        $format = array(
            '%s',
            '%s',
        );

        // Insert the data into the database
        $result = $wpdb->insert($table_name, $data, $format);

        if ( $result === false ) {
            wp_send_json_error();
        } else {
            $insert_id = $wpdb->insert_id;
            wp_send_json_success([
                'id' => $insert_id,
                'message' => 'Item added successfully',
            ]);
        }
    }

    // Showing all items via AJAX
    public function show_all_item()
    {
        check_ajax_referer('crud-assignment-nonce', 'nonce');

        global $wpdb;
        $table_name = $wpdb->prefix . 'crud_assignment';

        // Get all items from database
        $prepare_data = $wpdb->prepare("SELECT * FROM $table_name");
        $results = $wpdb->get_results( $prepare_data );

        if ( empty( $results ) ) {
            wp_send_json_error();
        } else {
            wp_send_json_success( $results );
        }
    }


    // Deleting an item via AJAX
    public function delete_item()
    {
        check_ajax_referer('crud-assignment-nonce', 'nonce');

        global $wpdb;
        $table_name = $wpdb->prefix . 'crud_assignment';

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        
        if ( $id > 0 ){
            $result = $wpdb->delete($table_name, ['id' => $id]);
            wp_send_json_success($result);
        } else {
            wp_send_json_error();
        }
    }

    // Edit an item via AJAX
    public function edit_item()
    {
        check_ajax_referer('crud-assignment-nonce', 'nonce');

        global $wpdb;
        $table_name = $wpdb->prefix . 'crud_assignment';

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if ( $id <= 0){ 
            wp_send_json_error();
            return;
        }

        $get_data = $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id);

        $result = $wpdb->get_row( $get_data);

        if ( empty( $result ) ) {
            wp_send_json_error();
            return;
        }

        wp_send_json_success($result);
    }


    // Update an item via AJAX
    public function update_item() {
        check_ajax_referer('crud-assignment-nonce', 'nonce');

        global $wpdb;
        $table_name = $wpdb->prefix . 'crud_assignment';

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';

        if ( $id <= 0 ) {
            wp_send_json_error();
            return;
        } 

        $data = [
            'name' => $name,
            'email' => $email,
            'updated_at' => current_time('mysql'),
        ];

        $format = [
            '%s',
            '%s',
            '%s',
        ];

        $where = [
            'id' => $id
        ];

        $where_format = [
            '%d'=> $id
        ];

        $result = $wpdb->update($table_name, $data, $where, $format, $where_format);

        if ( $result === false ) {
            wp_send_json_error();
        } else {
            $get_updated_data = $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id);
            $updated_data = $wpdb->get_row($get_updated_data);

            wp_send_json_success($updated_data);
        }
    }

 }

 new Crud_Assignment();