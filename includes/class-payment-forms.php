<?php

class WPF_Payment_Forms {
    
    private $payment_processor;
    
    public function __construct() {
        $this->payment_processor = new WPF_Payment_Processor();
        
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_wpf_submit_sales_form', array($this, 'handle_sales_form_submission'));
        add_action('wp_ajax_nopriv_wpf_submit_sales_form', array($this, 'handle_sales_form_submission'));
        add_action('wp_ajax_wpf_process_payment', array($this, 'handle_payment_processing'));
        add_action('wp_ajax_nopriv_wpf_process_payment', array($this, 'handle_payment_processing'));
        add_action('template_redirect', array($this, 'handle_payment_page'));
        
        // Add shortcodes
        add_shortcode('wpf_sales_form', array($this, 'sales_form_shortcode'));
        add_shortcode('wpf_payment_form', array($this, 'payment_form_shortcode'));
    }
    
    public function init() {
        // Initialize plugin
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('wpf-forms', WPF_PLUGIN_URL . 'assets/forms.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('wpf-forms', WPF_PLUGIN_URL . 'assets/forms.css', array(), '1.0.0');
        
        wp_localize_script('wpf-forms', 'wpf_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpf_nonce')
        ));
    }
    
    /**
     * Sales form shortcode
     */
    public function sales_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => 'Create Invoice & Payment Link',
            'currency' => 'USD'
        ), $atts);
        
        ob_start();
        ?>
        <div class="wpf-sales-form-container">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            
            <form id="wpf-sales-form" class="wpf-form">
                <?php wp_nonce_field('wpf_sales_form', 'wpf_nonce'); ?>
                
                <div class="wpf-section">
                    <h4>Salesperson Information</h4>
                    <div class="wpf-row">
                        <div class="wpf-col">
                            <label for="salesperson_name">Your Name *</label>
                            <input type="text" id="salesperson_name" name="salesperson_name" required>
                        </div>
                        <div class="wpf-col">
                            <label for="salesperson_email">Your Email *</label>
                            <input type="email" id="salesperson_email" name="salesperson_email" required>
                        </div>
                    </div>
                </div>
                
                <div class="wpf-section">
                    <h4>Client Information</h4>
                    <div class="wpf-row">
                        <div class="wpf-col">
                            <label for="client_name">Client Name *</label>
                            <input type="text" id="client_name" name="client_name" required>
                        </div>
                        <div class="wpf-col">
                            <label for="client_email">Client Email *</label>
                            <input type="email" id="client_email" name="client_email" required>
                        </div>
                    </div>
                    <div class="wpf-row">
                        <div class="wpf-col">
                            <label for="client_phone">Client Phone</label>
                            <input type="tel" id="client_phone" name="client_phone">
                        </div>
                    </div>
                </div>
                
                <div class="wpf-section">
                    <h4>Service & Payment Details</h4>
                    <div class="wpf-row">
                        <div class="wpf-col-full">
                            <label for="service_description">Service Description *</label>
                            <textarea id="service_description" name="service_description" rows="4" required placeholder="Describe the service or product being sold"></textarea>
                        </div>
                    </div>
                    <div class="wpf-row">
                        <div class="wpf-col">
                            <label for="amount">Amount *</label>
                            <input type="number" id="amount" name="amount" step="0.01" min="0.01" required>
                        </div>
                        <div class="wpf-col">
                            <label for="currency">Currency</label>
                            <select id="currency" name="currency">
                                <option value="USD" <?php selected($atts['currency'], 'USD'); ?>>USD ($)</option>
                                <option value="EUR" <?php selected($atts['currency'], 'EUR'); ?>>EUR (€)</option>
                                <option value="GBP" <?php selected($atts['currency'], 'GBP'); ?>>GBP (£)</option>
                                <option value="INR" <?php selected($atts['currency'], 'INR'); ?>>INR (₹)</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="wpf-section">
                    <h4>Terms & Conditions</h4>
                    <div class="wpf-conditions">
                        <label class="wpf-checkbox">
                            <input type="checkbox" id="conditions_accepted" name="conditions_accepted" required>
                            <span class="checkmark"></span>
                            I confirm that all information is correct and agree to generate a payment link for the client
                        </label>
                    </div>
                </div>
                
                <div class="wpf-submit-section">
                    <button type="submit" class="wpf-submit-btn">Generate Payment Link</button>
                </div>
                
                <div id="wpf-result" class="wpf-result" style="display: none;"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Payment form shortcode
     */
    public function payment_form_shortcode($atts) {
        $form_id = isset($_GET['form_id']) ? sanitize_text_field($_GET['form_id']) : '';
        $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
        
        if (empty($form_id) || empty($token)) {
            return '<p>Invalid payment link.</p>';
        }
        
        if (!$this->payment_processor->validate_payment_token($form_id, $token)) {
            return '<p>Invalid or expired payment link.</p>';
        }
        
        $form_data = WPF_Database::get_form_by_id($form_id);
        
        if (!$form_data || $form_data->payment_status === 'completed') {
            return '<p>This invoice has already been paid or is invalid.</p>';
        }
        
        ob_start();
        ?>
        <div class="wpf-payment-form-container">
            <h3>Payment for Invoice #<?php echo esc_html($form_data->form_id); ?></h3>
            
            <div class="wpf-invoice-details">
                <h4>Invoice Details</h4>
                <table class="wpf-invoice-table">
                    <tr><td><strong>Client:</strong></td><td><?php echo esc_html($form_data->client_name); ?></td></tr>
                    <tr><td><strong>Service:</strong></td><td><?php echo esc_html($form_data->service_description); ?></td></tr>
                    <tr><td><strong>Amount:</strong></td><td><?php echo esc_html($form_data->currency . ' ' . number_format($form_data->amount, 2)); ?></td></tr>
                    <tr><td><strong>Salesperson:</strong></td><td><?php echo esc_html($form_data->salesperson_name); ?></td></tr>
                </table>
            </div>
            
            <form id="wpf-payment-form" class="wpf-form">
                <?php wp_nonce_field('wpf_payment_form', 'wpf_payment_nonce'); ?>
                <input type="hidden" name="form_id" value="<?php echo esc_attr($form_data->form_id); ?>">
                <input type="hidden" name="token" value="<?php echo esc_attr($token); ?>">
                
                <div class="wpf-section">
                    <h4>Payment Method</h4>
                    <div class="wpf-payment-methods">
                        <label class="wpf-radio">
                            <input type="radio" name="payment_method" value="credit_card" checked>
                            <span class="radio-mark"></span>
                            Credit/Debit Card
                        </label>
                        <label class="wpf-radio">
                            <input type="radio" name="payment_method" value="paypal">
                            <span class="radio-mark"></span>
                            PayPal
                        </label>
                        <label class="wpf-radio">
                            <input type="radio" name="payment_method" value="bank_transfer">
                            <span class="radio-mark"></span>
                            Bank Transfer
                        </label>
                    </div>
                </div>
                
                <div id="credit_card_fields" class="wpf-payment-fields">
                    <div class="wpf-row">
                        <div class="wpf-col-full">
                            <label for="card_number">Card Number *</label>
                            <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
                        </div>
                    </div>
                    <div class="wpf-row">
                        <div class="wpf-col">
                            <label for="expiry_date">Expiry Date *</label>
                            <input type="text" id="expiry_date" name="expiry_date" placeholder="MM/YY" maxlength="5">
                        </div>
                        <div class="wpf-col">
                            <label for="cvv">CVV *</label>
                            <input type="text" id="cvv" name="cvv" placeholder="123" maxlength="4">
                        </div>
                    </div>
                    <div class="wpf-row">
                        <div class="wpf-col-full">
                            <label for="cardholder_name">Cardholder Name *</label>
                            <input type="text" id="cardholder_name" name="cardholder_name" value="<?php echo esc_attr($form_data->client_name); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="wpf-submit-section">
                    <button type="submit" class="wpf-pay-btn">
                        Pay <?php echo esc_html($form_data->currency . ' ' . number_format($form_data->amount, 2)); ?>
                    </button>
                </div>
                
                <div id="wpf-payment-result" class="wpf-result" style="display: none;"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Handle sales form submission
     */
    public function handle_sales_form_submission() {
        check_ajax_referer('wpf_nonce', 'wpf_nonce');
        
        $form_data = array(
            'form_id' => 'INV_' . time() . '_' . rand(1000, 9999),
            'salesperson_name' => sanitize_text_field($_POST['salesperson_name']),
            'salesperson_email' => sanitize_email($_POST['salesperson_email']),
            'client_name' => sanitize_text_field($_POST['client_name']),
            'client_email' => sanitize_email($_POST['client_email']),
            'client_phone' => sanitize_text_field($_POST['client_phone']),
            'service_description' => sanitize_textarea_field($_POST['service_description']),
            'amount' => floatval($_POST['amount']),
            'currency' => sanitize_text_field($_POST['currency']),
            'conditions_accepted' => isset($_POST['conditions_accepted']) ? 1 : 0
        );
        
        // Validate required fields
        if (empty($form_data['salesperson_name']) || empty($form_data['salesperson_email']) || 
            empty($form_data['client_name']) || empty($form_data['client_email']) || 
            empty($form_data['service_description']) || $form_data['amount'] <= 0 || 
            !$form_data['conditions_accepted']) {
            
            wp_send_json_error('Please fill in all required fields and accept the conditions.');
        }
        
        // Insert form data
        $insert_id = WPF_Database::insert_sales_form($form_data);
        
        if ($insert_id) {
            // Generate payment link
            $payment_link = $this->payment_processor->generate_payment_link(
                $form_data['form_id'], 
                $form_data['amount'], 
                $form_data['currency'], 
                $form_data['service_description']
            );
            
            if ($payment_link) {
                // Send email to salesperson with payment link
                $this->send_payment_link_email($form_data, $payment_link);
                
                wp_send_json_success(array(
                    'message' => 'Payment link generated successfully!',
                    'form_id' => $form_data['form_id'],
                    'payment_link' => $payment_link
                ));
            } else {
                wp_send_json_error('Failed to generate payment link.');
            }
        } else {
            wp_send_json_error('Failed to save form data.');
        }
    }
    
    /**
     * Handle payment processing
     */
    public function handle_payment_processing() {
        check_ajax_referer('wpf_payment_form', 'wpf_payment_nonce');
        
        $form_id = sanitize_text_field($_POST['form_id']);
        $token = sanitize_text_field($_POST['token']);
        $payment_method = sanitize_text_field($_POST['payment_method']);
        
        if (!$this->payment_processor->validate_payment_token($form_id, $token)) {
            wp_send_json_error('Invalid payment token.');
        }
        
        $payment_data = array(
            'payment_method' => $payment_method,
            'card_number' => isset($_POST['card_number']) ? sanitize_text_field($_POST['card_number']) : '',
            'cardholder_name' => isset($_POST['cardholder_name']) ? sanitize_text_field($_POST['cardholder_name']) : ''
        );
        
        $result = $this->payment_processor->process_payment($form_id, $payment_data);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Send payment link email to salesperson
     */
    private function send_payment_link_email($form_data, $payment_link) {
        $subject = 'Payment Link Generated - Invoice #' . $form_data['form_id'];
        $message = '
        <html>
        <body>
            <h2>Payment Link Generated</h2>
            <p>Hi ' . esc_html($form_data['salesperson_name']) . ',</p>
            <p>Your payment link has been generated successfully. Please share this link with your client:</p>
            
            <div style="background: #f5f5f5; padding: 15px; margin: 20px 0; border-radius: 5px;">
                <strong>Payment Link:</strong><br>
                <a href="' . esc_url($payment_link) . '" style="color: #0073aa; text-decoration: none;">' . esc_url($payment_link) . '</a>
            </div>
            
            <h3>Invoice Details:</h3>
            <table border="1" cellpadding="10" style="border-collapse: collapse;">
                <tr><td><strong>Client:</strong></td><td>' . esc_html($form_data['client_name']) . '</td></tr>
                <tr><td><strong>Email:</strong></td><td>' . esc_html($form_data['client_email']) . '</td></tr>
                <tr><td><strong>Service:</strong></td><td>' . esc_html($form_data['service_description']) . '</td></tr>
                <tr><td><strong>Amount:</strong></td><td>' . esc_html($form_data['currency'] . ' ' . $form_data['amount']) . '</td></tr>
            </table>
            
            <p>You will receive a notification when the payment is completed.</p>
            
            <p>Best regards,<br>Payment System</p>
        </body>
        </html>';
        
        wp_mail($form_data['salesperson_email'], $subject, $message, array('Content-Type: text/html; charset=UTF-8'));
    }
    
    /**
     * Handle payment page routing
     */
    public function handle_payment_page() {
        if (isset($_GET['wpf_action']) && $_GET['wpf_action'] === 'pay') {
            // This would typically redirect to a dedicated payment page
            // For now, we'll use the shortcode on any page
        }
    }
}