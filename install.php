<?php
/**
 * Payment Link Generator Installation Script
 * Run this script after uploading the plugin files to automatically configure the plugin
 */

// Check if this is being run from WordPress
if (!defined('ABSPATH')) {
    // If not, try to load WordPress
    $wp_config_path = dirname(__FILE__) . '/../../wp-config.php';
    if (file_exists($wp_config_path)) {
        require_once $wp_config_path;
    } else {
        die('WordPress not found. Please run this from within WordPress or ensure wp-config.php is accessible.');
    }
}

echo "<h1>Payment Link Generator Installation</h1>";

// Activate the plugin if not already active
if (!is_plugin_active('payment-link-generator/payment-link-generator.php')) {
    $result = activate_plugin('payment-link-generator/payment-link-generator.php');
    if (is_wp_error($result)) {
        echo "<p style='color: red;'>Error activating plugin: " . $result->get_error_message() . "</p>";
    } else {
        echo "<p style='color: green;'>✓ Plugin activated successfully!</p>";
    }
} else {
    echo "<p style='color: blue;'>✓ Plugin is already active</p>";
}

// Flush rewrite rules
flush_rewrite_rules();
echo "<p style='color: green;'>✓ URL rewrite rules flushed</p>";

// Check if database table was created
global $wpdb;
$table_name = $wpdb->prefix . 'payment_links';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;

if ($table_exists) {
    echo "<p style='color: green;'>✓ Database table created successfully</p>";
} else {
    echo "<p style='color: red;'>✗ Database table not found. Please deactivate and reactivate the plugin.</p>";
}

// Check for Forminator plugin
if (is_plugin_active('forminator/forminator.php')) {
    echo "<p style='color: green;'>✓ Forminator plugin is active</p>";
} else {
    echo "<p style='color: orange;'>! Forminator plugin is not active. Install and activate it for full functionality.</p>";
}

// Check file permissions
$upload_dir = wp_upload_dir();
if (is_writable($upload_dir['basedir'])) {
    echo "<p style='color: green;'>✓ Upload directory is writable</p>";
} else {
    echo "<p style='color: red;'>✗ Upload directory is not writable. Check file permissions.</p>";
}

// Check if SSL is enabled
if (is_ssl()) {
    echo "<p style='color: green;'>✓ SSL is enabled</p>";
} else {
    echo "<p style='color: orange;'>! SSL is not enabled. Consider enabling SSL for secure payments.</p>";
}

echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>Go to <a href='" . admin_url('options-general.php?page=payment-link-settings') . "'>Settings → Payment Links</a> to configure your form IDs</li>";
echo "<li>Create a Forminator form with the required fields (see README.md for details)</li>";
echo "<li>Test the form submission to ensure payment links are generated</li>";
echo "<li>Configure your payment gateway APIs (Stripe, PayPal, etc.)</li>";
echo "</ol>";

echo "<h2>Test the Setup:</h2>";
echo "<p>Add this shortcode to any page or post to test the standalone form:</p>";
echo "<code>[payment_link_form]</code>";

echo "<h2>Useful Links:</h2>";
echo "<ul>";
echo "<li><a href='" . admin_url('options-general.php?page=payment-link-settings') . "'>Plugin Settings</a></li>";
echo "<li><a href='" . admin_url('plugins.php') . "'>Manage Plugins</a></li>";
echo "<li><a href='" . admin_url('options-permalink.php') . "'>Permalink Settings</a></li>";
echo "</ul>";

echo "<hr>";
echo "<p><strong>Installation completed!</strong> Check the items above and configure as needed.</p>";
?>