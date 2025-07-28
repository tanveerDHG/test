<?php

class WPF_Payment_Processor {
    
    private $gateway_settings;
    
    public function __construct() {
        $this->gateway_settings = get_option('wpf_payment_settings', array());
    }
    
    /**
     * Generate payment link for a form submission
     */
    public function generate_payment_link($form_id, $amount, $currency = 'USD', $description = '') {
        $form_data = WPF_Database::get_form_by_id($form_id);
        
        if (!$form_data) {
            return false;
        }
        
        // For demo purposes, we'll create a simple payment link
        // In production, integrate with actual payment gateways like Stripe, PayPal, etc.
        $payment_link = add_query_arg(array(
            'wpf_action' => 'pay',
            'form_id' => $form_id,
            'token' => $this->generate_payment_token($form_id)
        ), home_url('/payment/'));
        
        // Update the form with payment link
        WPF_Database::update_payment_link($form_id, $payment_link);
        
        return $payment_link;
    }
    
    /**
     * Generate secure payment token
     */
    private function generate_payment_token($form_id) {
        return wp_hash($form_id . time() . wp_salt());
    }
    
    /**
     * Process payment (demo implementation)
     */
    public function process_payment($form_id, $payment_data) {
        $form_data = WPF_Database::get_form_by_id($form_id);
        
        if (!$form_data) {
            return array('success' => false, 'message' => 'Invalid form ID');
        }
        
        // Demo payment processing - replace with actual gateway integration
        $transaction_id = 'TXN_' . time() . '_' . rand(1000, 9999);
        
        // Simulate payment processing
        $payment_successful = true; // In real implementation, this would come from payment gateway
        
        if ($payment_successful) {
            // Update payment status
            WPF_Database::update_payment_status($form_id, 'completed');
            
            // Insert transaction record
            $transaction_data = array(
                'form_id' => $form_id,
                'transaction_id' => $transaction_id,
                'payment_method' => $payment_data['payment_method'],
                'amount_paid' => $form_data->amount,
                'payment_date' => current_time('mysql'),
                'status' => 'completed',
                'gateway_response' => json_encode($payment_data)
            );
            
            WPF_Database::insert_transaction($transaction_data);
            
            // Send confirmation emails
            $this->send_payment_confirmation($form_data, $transaction_id);
            
            return array(
                'success' => true, 
                'message' => 'Payment completed successfully',
                'transaction_id' => $transaction_id
            );
        } else {
            return array('success' => false, 'message' => 'Payment failed');
        }
    }
    
    /**
     * Send payment confirmation emails
     */
    private function send_payment_confirmation($form_data, $transaction_id) {
        // Email to client
        $client_subject = 'Payment Confirmation - Invoice #' . $form_data->form_id;
        $client_message = $this->get_client_email_template($form_data, $transaction_id);
        
        wp_mail($form_data->client_email, $client_subject, $client_message, array('Content-Type: text/html; charset=UTF-8'));
        
        // Email to salesperson
        $sales_subject = 'Payment Received - Invoice #' . $form_data->form_id;
        $sales_message = $this->get_sales_email_template($form_data, $transaction_id);
        
        wp_mail($form_data->salesperson_email, $sales_subject, $sales_message, array('Content-Type: text/html; charset=UTF-8'));
    }
    
    /**
     * Client email template
     */
    private function get_client_email_template($form_data, $transaction_id) {
        $template = '
        <html>
        <body>
            <h2>Payment Confirmation</h2>
            <p>Dear ' . esc_html($form_data->client_name) . ',</p>
            <p>Thank you for your payment. Here are the details:</p>
            
            <table border="1" cellpadding="10" style="border-collapse: collapse;">
                <tr><td><strong>Invoice ID:</strong></td><td>' . esc_html($form_data->form_id) . '</td></tr>
                <tr><td><strong>Transaction ID:</strong></td><td>' . esc_html($transaction_id) . '</td></tr>
                <tr><td><strong>Service Description:</strong></td><td>' . esc_html($form_data->service_description) . '</td></tr>
                <tr><td><strong>Amount Paid:</strong></td><td>' . esc_html($form_data->currency . ' ' . $form_data->amount) . '</td></tr>
                <tr><td><strong>Payment Date:</strong></td><td>' . current_time('F j, Y g:i A') . '</td></tr>
            </table>
            
            <p>If you have any questions, please contact ' . esc_html($form_data->salesperson_name) . ' at ' . esc_html($form_data->salesperson_email) . '</p>
            
            <p>Best regards,<br>Payment Team</p>
        </body>
        </html>';
        
        return $template;
    }
    
    /**
     * Sales email template
     */
    private function get_sales_email_template($form_data, $transaction_id) {
        $template = '
        <html>
        <body>
            <h2>Payment Received</h2>
            <p>Hi ' . esc_html($form_data->salesperson_name) . ',</p>
            <p>Great news! Payment has been received for your invoice.</p>
            
            <table border="1" cellpadding="10" style="border-collapse: collapse;">
                <tr><td><strong>Client:</strong></td><td>' . esc_html($form_data->client_name) . '</td></tr>
                <tr><td><strong>Invoice ID:</strong></td><td>' . esc_html($form_data->form_id) . '</td></tr>
                <tr><td><strong>Transaction ID:</strong></td><td>' . esc_html($transaction_id) . '</td></tr>
                <tr><td><strong>Amount:</strong></td><td>' . esc_html($form_data->currency . ' ' . $form_data->amount) . '</td></tr>
                <tr><td><strong>Payment Date:</strong></td><td>' . current_time('F j, Y g:i A') . '</td></tr>
            </table>
            
            <p>The client has been sent a confirmation email.</p>
            
            <p>Best regards,<br>Payment System</p>
        </body>
        </html>';
        
        return $template;
    }
    
    /**
     * Validate payment token
     */
    public function validate_payment_token($form_id, $token) {
        // Simple token validation - in production, use more secure validation
        $form_data = WPF_Database::get_form_by_id($form_id);
        return $form_data && !empty($token);
    }
}