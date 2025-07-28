<?php
/**
 * Plugin Name: WordPress Payment Forms
 * Description: Sales form with payment link generation and client payment processing
 * Version: 1.0.0
 * Author: Your Name
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WPF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPF_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Include required files
require_once WPF_PLUGIN_PATH . 'includes/class-payment-forms.php';
require_once WPF_PLUGIN_PATH . 'includes/class-database.php';
require_once WPF_PLUGIN_PATH . 'includes/class-payment-processor.php';
require_once WPF_PLUGIN_PATH . 'includes/class-admin.php';

// Initialize the plugin
function wpf_init() {
    new WPF_Payment_Forms();
    
    if (is_admin()) {
        new WPF_Admin();
    }
}
add_action('plugins_loaded', 'wpf_init');

// Activation hook
register_activation_hook(__FILE__, 'wpf_create_tables');

function wpf_create_tables() {
    WPF_Database::create_tables();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'wpf_deactivate');

function wpf_deactivate() {
    // Clean up if needed
}