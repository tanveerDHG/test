<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout - <?php echo esc_html($payment_data->item_name); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .checkout-container {
            padding: 40px 0;
        }
        
        .checkout-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .checkout-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .checkout-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 300;
        }
        
        .checkout-body {
            padding: 40px;
        }
        
        .order-summary {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .summary-item:last-child {
            border-bottom: none;
        }
        
        .summary-item.total {
            font-weight: bold;
            font-size: 1.2rem;
            color: #28a745;
            border-top: 2px solid #dee2e6;
            margin-top: 15px;
            padding-top: 20px;
        }
        
        .payment-methods {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 30px;
        }
        
        .payment-method {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .payment-method:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }
        
        .payment-method.selected {
            border-color: #667eea;
            background: #f8f9ff;
        }
        
        .payment-method input[type="radio"] {
            margin-right: 15px;
            transform: scale(1.2);
        }
        
        .payment-icon {
            font-size: 1.5rem;
            margin-right: 15px;
            width: 30px;
            text-align: center;
        }
        
        .stripe-icon { color: #635bff; }
        .paypal-icon { color: #0070ba; }
        .razorpay-icon { color: #528ff0; }
        
        .pay-button {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            color: white;
            padding: 15px 40px;
            font-size: 1.2rem;
            border-radius: 50px;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .pay-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(40, 167, 69, 0.3);
        }
        
        .security-notice {
            text-align: center;
            margin-top: 20px;
            color: #6c757d;
        }
        
        .loading {
            display: none;
            text-align: center;
            margin-top: 20px;
        }
        
        .success-message {
            display: none;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        
        .error-message {
            display: none;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container checkout-container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="checkout-card">
                    <div class="checkout-header">
                        <h1><i class="fas fa-shopping-cart me-3"></i>Checkout</h1>
                        <p class="mb-0">Complete your purchase securely</p>
                    </div>
                    
                    <div class="checkout-body">
                        <!-- Order Summary -->
                        <div class="order-summary">
                            <h3 class="mb-4"><i class="fas fa-receipt me-2"></i>Order Summary</h3>
                            
                            <div class="summary-item">
                                <span><strong>Item Name:</strong></span>
                                <span><?php echo esc_html($payment_data->item_name); ?></span>
                            </div>
                            
                            <div class="summary-item">
                                <span><strong>Category:</strong></span>
                                <span><?php echo esc_html($payment_data->item_category); ?></span>
                            </div>
                            
                            <?php if (!empty($payment_data->item_description)): ?>
                            <div class="summary-item">
                                <span><strong>Description:</strong></span>
                                <span><?php echo esc_html($payment_data->item_description); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="summary-item">
                                <span><strong>Price:</strong></span>
                                <span>$<?php echo number_format($payment_data->item_price, 2); ?></span>
                            </div>
                            
                            <?php if ($payment_data->discount > 0): ?>
                            <div class="summary-item">
                                <span><strong>Discount:</strong></span>
                                <span class="text-success">-$<?php echo number_format($payment_data->discount, 2); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="summary-item total">
                                <span><strong>Total Amount:</strong></span>
                                <span>$<?php echo number_format($payment_data->total_cost, 2); ?></span>
                            </div>
                        </div>
                        
                        <!-- Payment Methods -->
                        <div class="payment-methods">
                            <h3 class="mb-4"><i class="fas fa-credit-card me-2"></i>Payment Method</h3>
                            
                            <form id="checkout-form">
                                <input type="hidden" name="payment_id" value="<?php echo esc_attr($payment_data->payment_id); ?>">
                                
                                <?php if ($payment_data->payment_gateway === 'stripe' || $payment_data->payment_gateway === 'all'): ?>
                                <div class="payment-method" data-gateway="stripe">
                                    <label class="d-flex align-items-center">
                                        <input type="radio" name="gateway" value="stripe" <?php echo ($payment_data->payment_gateway === 'stripe') ? 'checked' : ''; ?>>
                                        <i class="fab fa-stripe stripe-icon payment-icon"></i>
                                        <div>
                                            <strong>Stripe</strong>
                                            <small class="d-block text-muted">Pay with credit/debit card</small>
                                        </div>
                                    </label>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($payment_data->payment_gateway === 'paypal' || $payment_data->payment_gateway === 'all'): ?>
                                <div class="payment-method" data-gateway="paypal">
                                    <label class="d-flex align-items-center">
                                        <input type="radio" name="gateway" value="paypal" <?php echo ($payment_data->payment_gateway === 'paypal') ? 'checked' : ''; ?>>
                                        <i class="fab fa-paypal paypal-icon payment-icon"></i>
                                        <div>
                                            <strong>PayPal</strong>
                                            <small class="d-block text-muted">Pay with PayPal account</small>
                                        </div>
                                    </label>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($payment_data->payment_gateway === 'razorpay' || $payment_data->payment_gateway === 'all'): ?>
                                <div class="payment-method" data-gateway="razorpay">
                                    <label class="d-flex align-items-center">
                                        <input type="radio" name="gateway" value="razorpay" <?php echo ($payment_data->payment_gateway === 'razorpay') ? 'checked' : ''; ?>>
                                        <i class="fas fa-university razorpay-icon payment-icon"></i>
                                        <div>
                                            <strong>Razorpay</strong>
                                            <small class="d-block text-muted">UPI, Cards, Net Banking</small>
                                        </div>
                                    </label>
                                </div>
                                <?php endif; ?>
                                
                                <button type="submit" class="pay-button mt-4">
                                    <i class="fas fa-lock me-2"></i>
                                    Pay $<?php echo number_format($payment_data->total_cost, 2); ?> Securely
                                </button>
                            </form>
                            
                            <div class="loading">
                                <i class="fas fa-spinner fa-spin fa-2x"></i>
                                <p class="mt-2">Processing your payment...</p>
                            </div>
                            
                            <div class="success-message">
                                <h4><i class="fas fa-check-circle me-2"></i>Payment Successful!</h4>
                                <p>Your payment has been processed successfully. You will receive a confirmation email shortly.</p>
                            </div>
                            
                            <div class="error-message">
                                <h4><i class="fas fa-exclamation-triangle me-2"></i>Payment Failed</h4>
                                <p id="error-text">There was an error processing your payment. Please try again.</p>
                            </div>
                            
                            <div class="security-notice">
                                <i class="fas fa-shield-alt me-2"></i>
                                Your payment information is encrypted and secure
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        jQuery(document).ready(function($) {
            // Payment method selection
            $('.payment-method').on('click', function() {
                $('.payment-method').removeClass('selected');
                $(this).addClass('selected');
                $(this).find('input[type="radio"]').prop('checked', true);
            });
            
            // Form submission
            $('#checkout-form').on('submit', function(e) {
                e.preventDefault();
                
                var selectedGateway = $('input[name="gateway"]:checked').val();
                if (!selectedGateway) {
                    alert('Please select a payment method');
                    return;
                }
                
                // Show loading
                $(this).hide();
                $('.loading').show();
                
                // Process payment
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'process_payment',
                        payment_id: $('input[name="payment_id"]').val(),
                        gateway: selectedGateway,
                        nonce: '<?php echo wp_create_nonce('payment_link_nonce'); ?>'
                    },
                    success: function(response) {
                        $('.loading').hide();
                        if (response.success) {
                            $('.success-message').show();
                        } else {
                            $('#error-text').text(response.data.message || 'Payment failed. Please try again.');
                            $('.error-message').show();
                            $('#checkout-form').show();
                        }
                    },
                    error: function() {
                        $('.loading').hide();
                        $('#error-text').text('An error occurred. Please try again.');
                        $('.error-message').show();
                        $('#checkout-form').show();
                    }
                });
            });
        });
    </script>
</body>
</html>