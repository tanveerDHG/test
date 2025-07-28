<?php

class WPF_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Payment Forms',
            'Payment Forms',
            'manage_options',
            'wpf-payment-forms',
            array($this, 'admin_page'),
            'dashicons-money-alt',
            30
        );
        
        add_submenu_page(
            'wpf-payment-forms',
            'All Forms',
            'All Forms',
            'manage_options',
            'wpf-payment-forms',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'wpf-payment-forms',
            'Settings',
            'Settings',
            'manage_options',
            'wpf-settings',
            array($this, 'settings_page')
        );
    }
    
    public function register_settings() {
        register_setting('wpf_settings', 'wpf_payment_settings');
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'wpf-') !== false) {
            wp_enqueue_style('wpf-admin', WPF_PLUGIN_URL . 'assets/admin.css');
            wp_enqueue_script('wpf-admin', WPF_PLUGIN_URL . 'assets/admin.js', array('jquery'), '1.0.0', true);
        }
    }
    
    public function admin_page() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpf_sales_forms';
        $forms = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
        
        ?>
        <div class="wrap">
            <h1>Payment Forms</h1>
            
            <div class="wpf-admin-stats">
                <div class="wpf-stat-box">
                    <h3><?php echo count($forms); ?></h3>
                    <p>Total Forms</p>
                </div>
                <div class="wpf-stat-box">
                    <h3><?php echo count(array_filter($forms, function($f) { return $f->payment_status === 'completed'; })); ?></h3>
                    <p>Paid Invoices</p>
                </div>
                <div class="wpf-stat-box">
                    <h3><?php echo count(array_filter($forms, function($f) { return $f->payment_status === 'pending'; })); ?></h3>
                    <p>Pending Payments</p>
                </div>
                <div class="wpf-stat-box">
                    <h3><?php 
                    $total = array_sum(array_map(function($f) { 
                        return $f->payment_status === 'completed' ? $f->amount : 0; 
                    }, $forms));
                    echo '$' . number_format($total, 2);
                    ?></h3>
                    <p>Total Revenue</p>
                </div>
            </div>
            
            <h2>Recent Forms</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Form ID</th>
                        <th>Client</th>
                        <th>Salesperson</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($forms)): ?>
                        <tr>
                            <td colspan="7">
                                <p style="text-align: center; padding: 20px;">
                                    No forms found. Use the shortcode <code>[wpf_sales_form]</code> to display the sales form on any page.
                                </p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($forms as $form): ?>
                            <tr>
                                <td><strong><?php echo esc_html($form->form_id); ?></strong></td>
                                <td>
                                    <?php echo esc_html($form->client_name); ?><br>
                                    <small><?php echo esc_html($form->client_email); ?></small>
                                </td>
                                <td>
                                    <?php echo esc_html($form->salesperson_name); ?><br>
                                    <small><?php echo esc_html($form->salesperson_email); ?></small>
                                </td>
                                <td><strong><?php echo esc_html($form->currency . ' ' . number_format($form->amount, 2)); ?></strong></td>
                                <td>
                                    <span class="wpf-status wpf-status-<?php echo $form->payment_status; ?>">
                                        <?php echo ucfirst($form->payment_status); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y g:i A', strtotime($form->created_at)); ?></td>
                                <td>
                                    <?php if ($form->payment_link): ?>
                                        <a href="<?php echo esc_url($form->payment_link); ?>" target="_blank" class="button button-small">View Payment Link</a>
                                    <?php endif; ?>
                                    <button class="button button-small wpf-view-details" data-form-id="<?php echo $form->form_id; ?>">Details</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <div class="wpf-shortcodes">
                <h2>Shortcodes</h2>
                <div class="wpf-shortcode-box">
                    <h4>Sales Form</h4>
                    <code>[wpf_sales_form]</code>
                    <p>Use this shortcode to display the sales form where salespeople can create invoices and payment links.</p>
                </div>
                <div class="wpf-shortcode-box">
                    <h4>Payment Form</h4>
                    <code>[wpf_payment_form]</code>
                    <p>Use this shortcode on a page where clients will make payments (usually /payment/ page).</p>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function settings_page() {
        $settings = get_option('wpf_payment_settings', array());
        
        if (isset($_POST['submit'])) {
            $settings = array(
                'currency' => sanitize_text_field($_POST['currency']),
                'email_from_name' => sanitize_text_field($_POST['email_from_name']),
                'email_from_address' => sanitize_email($_POST['email_from_address']),
                'payment_success_message' => sanitize_textarea_field($_POST['payment_success_message']),
                'terms_conditions' => sanitize_textarea_field($_POST['terms_conditions'])
            );
            
            update_option('wpf_payment_settings', $settings);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        
        ?>
        <div class="wrap">
            <h1>Payment Forms Settings</h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('wpf_settings', 'wpf_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Default Currency</th>
                        <td>
                            <select name="currency">
                                <option value="USD" <?php selected(isset($settings['currency']) ? $settings['currency'] : 'USD', 'USD'); ?>>USD ($)</option>
                                <option value="EUR" <?php selected(isset($settings['currency']) ? $settings['currency'] : '', 'EUR'); ?>>EUR (€)</option>
                                <option value="GBP" <?php selected(isset($settings['currency']) ? $settings['currency'] : '', 'GBP'); ?>>GBP (£)</option>
                                <option value="INR" <?php selected(isset($settings['currency']) ? $settings['currency'] : '', 'INR'); ?>>INR (₹)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Email From Name</th>
                        <td>
                            <input type="text" name="email_from_name" value="<?php echo esc_attr(isset($settings['email_from_name']) ? $settings['email_from_name'] : get_bloginfo('name')); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Email From Address</th>
                        <td>
                            <input type="email" name="email_from_address" value="<?php echo esc_attr(isset($settings['email_from_address']) ? $settings['email_from_address'] : get_option('admin_email')); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Payment Success Message</th>
                        <td>
                            <textarea name="payment_success_message" rows="4" cols="50"><?php echo esc_textarea(isset($settings['payment_success_message']) ? $settings['payment_success_message'] : 'Thank you for your payment! Your transaction has been completed successfully.'); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Terms & Conditions</th>
                        <td>
                            <textarea name="terms_conditions" rows="6" cols="50"><?php echo esc_textarea(isset($settings['terms_conditions']) ? $settings['terms_conditions'] : 'By submitting this form, you agree to generate a payment link for the specified client and amount.'); ?></textarea>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <div class="wpf-setup-guide">
                <h2>Setup Guide</h2>
                <ol>
                    <li><strong>Create a Sales Page:</strong> Create a new page and add the shortcode <code>[wpf_sales_form]</code> where your salespeople can create invoices.</li>
                    <li><strong>Create a Payment Page:</strong> Create a page with slug "payment" and add the shortcode <code>[wpf_payment_form]</code> where clients will make payments.</li>
                    <li><strong>Configure Email:</strong> Make sure your WordPress site can send emails for notifications to work properly.</li>
                    <li><strong>Test the Flow:</strong> Create a test invoice, generate a payment link, and test the complete payment process.</li>
                </ol>
                
                <h3>Integration with Payment Gateways</h3>
                <p>This plugin currently includes a demo payment processor. For production use, you should integrate with real payment gateways like:</p>
                <ul>
                    <li>Stripe</li>
                    <li>PayPal</li>
                    <li>Square</li>
                    <li>Razorpay (for India)</li>
                </ul>
                <p>Contact your developer to integrate with your preferred payment gateway.</p>
            </div>
        </div>
        <?php
    }
}