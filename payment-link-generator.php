<?php
/**
 * Plugin Name: Payment Link Generator
 * Plugin URI: https://yoursite.com
 * Description: Generate payment links from Forminator form submissions with checkout functionality
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class PaymentLinkGenerator {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'payment_links';
        
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_generate_payment_link', array($this, 'handle_form_submission'));
        add_action('wp_ajax_nopriv_generate_payment_link', array($this, 'handle_form_submission'));
        add_action('wp_ajax_process_payment', array($this, 'process_payment'));
        add_action('wp_ajax_nopriv_process_payment', array($this, 'process_payment'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'handle_checkout_page'));
        
        register_activation_hook(__FILE__, array($this, 'create_tables'));
    }
    
    public function init() {
        add_rewrite_rule(
            '^checkout/([^/]+)/?$',
            'index.php?payment_checkout=1&payment_id=$matches[1]',
            'top'
        );
        flush_rewrite_rules();
    }
    
    public function add_query_vars($vars) {
        $vars[] = 'payment_checkout';
        $vars[] = 'payment_id';
        return $vars;
    }
    
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->table_name} (
            id int(11) NOT NULL AUTO_INCREMENT,
            payment_id varchar(32) NOT NULL UNIQUE,
            form_data longtext NOT NULL,
            item_name varchar(255) NOT NULL,
            item_price decimal(10,2) NOT NULL,
            discount decimal(10,2) DEFAULT 0,
            total_cost decimal(10,2) NOT NULL,
            item_category varchar(100) NOT NULL,
            payment_gateway varchar(50) NOT NULL,
            item_description text,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY payment_id (payment_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('payment-link-js', plugin_dir_url(__FILE__) . 'assets/payment-link.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('payment-link-css', plugin_dir_url(__FILE__) . 'assets/payment-link.css', array(), '1.0.0');
        
        wp_localize_script('payment-link-js', 'paymentLinkAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('payment_link_nonce'),
            'site_url' => site_url()
        ));
    }
    
    public function handle_form_submission() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'payment_link_nonce')) {
            wp_die('Security check failed');
        }
        
        global $wpdb;
        
        // Generate unique payment ID
        $payment_id = $this->generate_payment_id();
        
        // Sanitize and process form data
        $form_data = array(
            'item_name' => sanitize_text_field($_POST['item_name']),
            'item_price' => floatval($_POST['item_price']),
            'discount' => floatval($_POST['discount']),
            'item_description' => sanitize_textarea_field($_POST['item_description']),
            'item_category' => sanitize_text_field($_POST['item_category']),
            'payment_gateway' => sanitize_text_field($_POST['payment_gateway']),
            'total_cost' => floatval($_POST['total_cost'])
        );
        
        // Store in database
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'payment_id' => $payment_id,
                'form_data' => serialize($form_data),
                'item_name' => $form_data['item_name'],
                'item_price' => $form_data['item_price'],
                'discount' => $form_data['discount'],
                'total_cost' => $form_data['total_cost'],
                'item_category' => $form_data['item_category'],
                'payment_gateway' => $form_data['payment_gateway'],
                'item_description' => $form_data['item_description']
            ),
            array('%s', '%s', '%s', '%f', '%f', '%f', '%s', '%s', '%s')
        );
        
        if ($result) {
            $checkout_url = site_url('/checkout/' . $payment_id);
            wp_send_json_success(array(
                'payment_id' => $payment_id,
                'checkout_url' => $checkout_url,
                'message' => 'Payment link generated successfully!'
            ));
        } else {
            wp_send_json_error('Failed to generate payment link');
        }
    }
    
    public function handle_checkout_page() {
        $payment_checkout = get_query_var('payment_checkout');
        $payment_id = get_query_var('payment_id');
        
        if ($payment_checkout && $payment_id) {
            $this->display_checkout_page($payment_id);
            exit;
        }
    }
    
    public function display_checkout_page($payment_id) {
        global $wpdb;
        
        $payment_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE payment_id = %s",
            $payment_id
        ));
        
        if (!$payment_data) {
            wp_die('Payment link not found or expired.');
        }
        
        $form_data = unserialize($payment_data->form_data);
        
        // Load checkout template
        include plugin_dir_path(__FILE__) . 'templates/checkout-page.php';
    }
    
    public function process_payment() {
        if (!wp_verify_nonce($_POST['nonce'], 'payment_link_nonce')) {
            wp_die('Security check failed');
        }
        
        global $wpdb;
        
        $payment_id = sanitize_text_field($_POST['payment_id']);
        $gateway = sanitize_text_field($_POST['gateway']);
        
        $payment_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE payment_id = %s",
            $payment_id
        ));
        
        if (!$payment_data) {
            wp_send_json_error('Payment not found');
        }
        
        // Process payment based on gateway
        $result = $this->process_gateway_payment($payment_data, $gateway, $_POST);
        
        if ($result['success']) {
            // Update payment status
            $wpdb->update(
                $this->table_name,
                array('status' => 'completed'),
                array('payment_id' => $payment_id),
                array('%s'),
                array('%s')
            );
            
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    private function process_gateway_payment($payment_data, $gateway, $post_data) {
        switch ($gateway) {
            case 'stripe':
                return $this->process_stripe_payment($payment_data, $post_data);
            case 'paypal':
                return $this->process_paypal_payment($payment_data, $post_data);
            case 'razorpay':
                return $this->process_razorpay_payment($payment_data, $post_data);
            default:
                return array('success' => false, 'message' => 'Invalid payment gateway');
        }
    }
    
    private function process_stripe_payment($payment_data, $post_data) {
        // Stripe payment processing logic
        // This would integrate with Stripe API
        return array(
            'success' => true,
            'message' => 'Payment processed successfully with Stripe',
            'transaction_id' => 'stripe_' . uniqid()
        );
    }
    
    private function process_paypal_payment($payment_data, $post_data) {
        // PayPal payment processing logic
        // This would integrate with PayPal API
        return array(
            'success' => true,
            'message' => 'Payment processed successfully with PayPal',
            'transaction_id' => 'paypal_' . uniqid()
        );
    }
    
    private function process_razorpay_payment($payment_data, $post_data) {
        // Razorpay payment processing logic
        // This would integrate with Razorpay API
        return array(
            'success' => true,
            'message' => 'Payment processed successfully with Razorpay',
            'transaction_id' => 'razorpay_' . uniqid()
        );
    }
    
    private function generate_payment_id() {
        return md5(uniqid(rand(), true));
    }
}

// Initialize the plugin
new PaymentLinkGenerator();

// Include Forminator integration
if (file_exists(plugin_dir_path(__FILE__) . 'forminator-integration.php')) {
    require_once plugin_dir_path(__FILE__) . 'forminator-integration.php';
}

// Shortcode for the form
function payment_link_form_shortcode($atts) {
    $atts = shortcode_atts(array(
        'form_id' => '',
    ), $atts);
    
    ob_start();
    include plugin_dir_path(__FILE__) . 'templates/payment-form.php';
    return ob_get_clean();
}
add_shortcode('payment_link_form', 'payment_link_form_shortcode');
?>