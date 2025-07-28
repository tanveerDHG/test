<?php
/**
 * Forminator Integration for Payment Link Generator
 * This file handles integration with Forminator forms
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class PaymentLinkForminator {
    
    public function __construct() {
        // Hook into Forminator form submission
        add_action('forminator_custom_form_submit_before_set_fields', array($this, 'handle_forminator_submission'), 10, 3);
        add_action('forminator_form_after_save_entry', array($this, 'process_form_entry'), 10, 2);
    }
    
    /**
     * Handle Forminator form submission
     */
    public function handle_forminator_submission($entry, $form_id, $field_data_array) {
        // Check if this form should generate payment links
        $payment_form_ids = $this->get_payment_form_ids();
        
        if (!in_array($form_id, $payment_form_ids)) {
            return;
        }
        
        $this->process_payment_form_submission($entry, $form_id, $field_data_array);
    }
    
    /**
     * Process form entry after it's saved
     */
    public function process_form_entry($entry_id, $form_id) {
        // Get form data
        $entry = Forminator_API::get_entry($form_id, $entry_id);
        
        if (!$entry) {
            return;
        }
        
        // Check if this is a payment form
        $payment_form_ids = $this->get_payment_form_ids();
        
        if (!in_array($form_id, $payment_form_ids)) {
            return;
        }
        
        // Generate payment link
        $payment_link = $this->generate_payment_link_from_entry($entry, $form_id);
        
        if ($payment_link) {
            // Store payment link in entry meta
            update_post_meta($entry_id, '_payment_link', $payment_link);
            
            // Send email notification with payment link
            $this->send_payment_link_notification($entry, $payment_link);
        }
    }
    
    /**
     * Get form IDs that should generate payment links
     */
    private function get_payment_form_ids() {
        // You can configure this in WordPress admin or hardcode specific form IDs
        $payment_forms = get_option('payment_link_forminator_forms', array());
        
        // Default fallback - you can set your Forminator form ID here
        if (empty($payment_forms)) {
            $payment_forms = array(); // Add your form IDs: array(123, 456)
        }
        
        return $payment_forms;
    }
    
    /**
     * Generate payment link from Forminator entry
     */
    private function generate_payment_link_from_entry($entry, $form_id) {
        global $wpdb;
        
        // Map Forminator fields to payment data
        $field_mapping = $this->get_field_mapping($form_id);
        
        $payment_data = array();
        
        foreach ($entry->meta_data as $field_id => $field_data) {
            $field_value = is_array($field_data['value']) ? implode(', ', $field_data['value']) : $field_data['value'];
            
            // Map fields based on configuration
            if (isset($field_mapping[$field_id])) {
                $payment_field = $field_mapping[$field_id];
                $payment_data[$payment_field] = sanitize_text_field($field_value);
            }
        }
        
        // Validate required fields
        $required_fields = array('item_name', 'item_price', 'payment_gateway');
        foreach ($required_fields as $field) {
            if (empty($payment_data[$field])) {
                error_log('Payment Link Generator: Missing required field - ' . $field);
                return false;
            }
        }
        
        // Calculate total cost
        $item_price = floatval($payment_data['item_price']);
        $discount = floatval($payment_data['discount'] ?? 0);
        $total_cost = max(0, $item_price - $discount);
        
        // Generate unique payment ID
        $payment_id = md5(uniqid(rand(), true));
        
        // Prepare data for database
        $form_data = array(
            'item_name' => $payment_data['item_name'],
            'item_price' => $item_price,
            'discount' => $discount,
            'item_description' => $payment_data['item_description'] ?? '',
            'item_category' => $payment_data['item_category'] ?? 'General',
            'payment_gateway' => $payment_data['payment_gateway'],
            'total_cost' => $total_cost,
            'forminator_entry_id' => $entry->entry_id,
            'forminator_form_id' => $form_id
        );
        
        // Store in payment links table
        $table_name = $wpdb->prefix . 'payment_links';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'payment_id' => $payment_id,
                'form_data' => serialize($form_data),
                'item_name' => $form_data['item_name'],
                'item_price' => $form_data['item_price'],
                'discount' => $form_data['discount'],
                'total_cost' => $form_data['total_cost'],
                'item_category' => $form_data['item_category'],
                'payment_gateway' => $form_data['payment_gateway'],
                'item_description' => $form_data['item_description'],
                'status' => 'pending'
            ),
            array('%s', '%s', '%s', '%f', '%f', '%f', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            return site_url('/checkout/' . $payment_id);
        }
        
        return false;
    }
    
    /**
     * Get field mapping for a specific form
     */
    private function get_field_mapping($form_id) {
        // Default field mapping - you can customize this based on your Forminator form fields
        $default_mapping = array(
            'name-1' => 'item_name',           // Text field for item name
            'number-1' => 'item_price',        // Number field for price
            'number-2' => 'discount',          // Number field for discount
            'textarea-1' => 'item_description', // Textarea for description
            'text-1' => 'item_category',       // Text field for category
            'select-1' => 'payment_gateway'    // Select field for payment gateway
        );
        
        // Get custom mapping for this form (can be stored in WordPress options)
        $custom_mapping = get_option('payment_link_field_mapping_' . $form_id, array());
        
        return array_merge($default_mapping, $custom_mapping);
    }
    
    /**
     * Send payment link notification email
     */
    private function send_payment_link_notification($entry, $payment_link) {
        // Get user email from form submission
        $user_email = '';
        
        foreach ($entry->meta_data as $field_id => $field_data) {
            if (strpos($field_id, 'email') !== false || $field_data['name'] === 'Email') {
                $user_email = $field_data['value'];
                break;
            }
        }
        
        if (empty($user_email)) {
            return;
        }
        
        // Email content
        $subject = 'Your Payment Link - Complete Your Purchase';
        $message = "
        <html>
        <head>
            <title>Payment Link</title>
        </head>
        <body>
            <h2>Thank you for your submission!</h2>
            <p>Please click the link below to complete your payment:</p>
            <p><a href='{$payment_link}' style='background: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px;'>Complete Payment</a></p>
            <p>Or copy and paste this link in your browser:</p>
            <p>{$payment_link}</p>
            <br>
            <p>If you have any questions, please contact us.</p>
        </body>
        </html>
        ";
        
        // Email headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        // Send email
        wp_mail($user_email, $subject, $message, $headers);
    }
}

// Initialize Forminator integration
new PaymentLinkForminator();

// Admin functions for configuration
add_action('admin_menu', 'payment_link_admin_menu');

function payment_link_admin_menu() {
    add_options_page(
        'Payment Link Settings',
        'Payment Links',
        'manage_options',
        'payment-link-settings',
        'payment_link_settings_page'
    );
}

function payment_link_settings_page() {
    if (isset($_POST['submit'])) {
        // Save form IDs
        $form_ids = array_map('intval', explode(',', $_POST['forminator_form_ids']));
        update_option('payment_link_forminator_forms', $form_ids);
        
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }
    
    $current_forms = get_option('payment_link_forminator_forms', array());
    $form_ids_string = implode(',', $current_forms);
    
    ?>
    <div class="wrap">
        <h1>Payment Link Settings</h1>
        
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th scope="row">Forminator Form IDs</th>
                    <td>
                        <input type="text" name="forminator_form_ids" value="<?php echo esc_attr($form_ids_string); ?>" class="regular-text" />
                        <p class="description">Enter comma-separated Forminator form IDs that should generate payment links (e.g., 123,456)</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
        
        <h2>Field Mapping Guide</h2>
        <p>Make sure your Forminator form has the following fields with these field IDs:</p>
        <ul>
            <li><strong>name-1</strong>: Item Name (Text field)</li>
            <li><strong>number-1</strong>: Item Price (Number field)</li>
            <li><strong>number-2</strong>: Discount (Number field)</li>
            <li><strong>textarea-1</strong>: Item Description (Textarea)</li>
            <li><strong>text-1</strong>: Item Category (Text field)</li>
            <li><strong>select-1</strong>: Payment Gateway (Select field with options: stripe, paypal, razorpay)</li>
            <li><strong>email-1</strong>: User Email (Email field)</li>
        </ul>
        
        <h2>Shortcode Usage</h2>
        <p>You can also use the standalone form with this shortcode:</p>
        <code>[payment_link_form]</code>
    </div>
    <?php
}
?>