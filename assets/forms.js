jQuery(document).ready(function($) {
    
    // Sales form submission
    $('#wpf-sales-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitBtn = form.find('.wpf-submit-btn');
        var resultDiv = $('#wpf-result');
        
        // Show loading state
        submitBtn.html('<span class="wpf-spinner"></span>Generating Link...');
        submitBtn.prop('disabled', true);
        form.addClass('wpf-loading');
        
        resultDiv.removeClass('success error').addClass('loading')
               .html('Processing your request...').show();
        
        // Collect form data
        var formData = {
            action: 'wpf_submit_sales_form',
            wpf_nonce: form.find('#wpf_nonce').val(),
            salesperson_name: form.find('#salesperson_name').val(),
            salesperson_email: form.find('#salesperson_email').val(),
            client_name: form.find('#client_name').val(),
            client_email: form.find('#client_email').val(),
            client_phone: form.find('#client_phone').val(),
            service_description: form.find('#service_description').val(),
            amount: form.find('#amount').val(),
            currency: form.find('#currency').val(),
            conditions_accepted: form.find('#conditions_accepted').is(':checked') ? 1 : 0
        };
        
        // Submit via AJAX
        $.ajax({
            url: wpf_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    resultDiv.removeClass('loading error').addClass('success');
                    
                    var successHtml = '<h4>✅ Success!</h4>';
                    successHtml += '<p>' + response.data.message + '</p>';
                    successHtml += '<div class="wpf-payment-link">';
                    successHtml += '<strong>Invoice ID:</strong> ' + response.data.form_id + '<br>';
                    successHtml += '<strong>Payment Link:</strong><br>';
                    successHtml += '<a href="' + response.data.payment_link + '" target="_blank">' + response.data.payment_link + '</a>';
                    successHtml += '<br><br><small>This link has been emailed to you. Share it with your client to receive payment.</small>';
                    successHtml += '</div>';
                    
                    resultDiv.html(successHtml);
                    
                    // Reset form
                    form[0].reset();
                    
                } else {
                    resultDiv.removeClass('loading success').addClass('error');
                    resultDiv.html('<h4>❌ Error</h4><p>' + response.data + '</p>');
                }
            },
            error: function() {
                resultDiv.removeClass('loading success').addClass('error');
                resultDiv.html('<h4>❌ Error</h4><p>Something went wrong. Please try again.</p>');
            },
            complete: function() {
                // Reset button state
                submitBtn.html('Generate Payment Link');
                submitBtn.prop('disabled', false);
                form.removeClass('wpf-loading');
            }
        });
    });
    
    // Payment form submission
    $('#wpf-payment-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitBtn = form.find('.wpf-pay-btn');
        var resultDiv = $('#wpf-payment-result');
        var selectedMethod = form.find('input[name="payment_method"]:checked').val();
        
        // Validate credit card fields if credit card is selected
        if (selectedMethod === 'credit_card') {
            var cardNumber = form.find('#card_number').val().replace(/\s/g, '');
            var expiryDate = form.find('#expiry_date').val();
            var cvv = form.find('#cvv').val();
            var cardholderName = form.find('#cardholder_name').val();
            
            if (!cardNumber || cardNumber.length < 13) {
                showPaymentError('Please enter a valid card number.');
                return;
            }
            
            if (!expiryDate || !expiryDate.match(/^(0[1-9]|1[0-2])\/\d{2}$/)) {
                showPaymentError('Please enter a valid expiry date (MM/YY).');
                return;
            }
            
            if (!cvv || cvv.length < 3) {
                showPaymentError('Please enter a valid CVV.');
                return;
            }
            
            if (!cardholderName.trim()) {
                showPaymentError('Please enter the cardholder name.');
                return;
            }
        }
        
        // Show loading state
        var originalAmount = submitBtn.html();
        submitBtn.html('<span class="wpf-spinner"></span>Processing Payment...');
        submitBtn.prop('disabled', true);
        form.addClass('wpf-loading');
        
        resultDiv.removeClass('success error').addClass('loading')
               .html('Processing your payment...').show();
        
        // Collect form data
        var formData = {
            action: 'wpf_process_payment',
            wpf_payment_nonce: form.find('#wpf_payment_nonce').val(),
            form_id: form.find('input[name="form_id"]').val(),
            token: form.find('input[name="token"]').val(),
            payment_method: selectedMethod,
            card_number: form.find('#card_number').val(),
            expiry_date: form.find('#expiry_date').val(),
            cvv: form.find('#cvv').val(),
            cardholder_name: form.find('#cardholder_name').val()
        };
        
        // Submit payment
        $.ajax({
            url: wpf_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    resultDiv.removeClass('loading error').addClass('success');
                    
                    var successHtml = '<h4>🎉 Payment Successful!</h4>';
                    successHtml += '<p>' + response.data.message + '</p>';
                    successHtml += '<p><strong>Transaction ID:</strong> ' + response.data.transaction_id + '</p>';
                    successHtml += '<p>A confirmation email has been sent to you and the salesperson.</p>';
                    
                    resultDiv.html(successHtml);
                    
                    // Hide the payment form
                    form.hide();
                    
                } else {
                    showPaymentError(response.data);
                }
            },
            error: function() {
                showPaymentError('Payment processing failed. Please try again.');
            },
            complete: function() {
                // Reset button state
                submitBtn.html(originalAmount);
                submitBtn.prop('disabled', false);
                form.removeClass('wpf-loading');
            }
        });
        
        function showPaymentError(message) {
            resultDiv.removeClass('loading success').addClass('error');
            resultDiv.html('<h4>❌ Payment Failed</h4><p>' + message + '</p>').show();
        }
    });
    
    // Payment method change handler
    $('input[name="payment_method"]').on('change', function() {
        var selectedMethod = $(this).val();
        var creditCardFields = $('#credit_card_fields');
        
        if (selectedMethod === 'credit_card') {
            creditCardFields.show();
            // Make credit card fields required
            creditCardFields.find('input').prop('required', true);
        } else {
            creditCardFields.hide();
            // Remove required from credit card fields
            creditCardFields.find('input').prop('required', false);
        }
    });
    
    // Card number formatting
    $('#card_number').on('input', function() {
        var value = $(this).val().replace(/\s/g, '').replace(/[^0-9]/gi, '');
        var formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
        $(this).val(formattedValue);
    });
    
    // Expiry date formatting
    $('#expiry_date').on('input', function() {
        var value = $(this).val().replace(/\D/g, '');
        if (value.length >= 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        $(this).val(value);
    });
    
    // CVV formatting (numbers only)
    $('#cvv').on('input', function() {
        var value = $(this).val().replace(/[^0-9]/g, '');
        $(this).val(value);
    });
    
    // Copy payment link functionality
    $(document).on('click', '.wpf-copy-link', function(e) {
        e.preventDefault();
        var link = $(this).data('link');
        
        // Create temporary input to copy text
        var tempInput = $('<input>');
        $('body').append(tempInput);
        tempInput.val(link).select();
        document.execCommand('copy');
        tempInput.remove();
        
        // Show feedback
        var originalText = $(this).text();
        $(this).text('Copied!');
        setTimeout(() => {
            $(this).text(originalText);
        }, 2000);
    });
    
    // Form validation helpers
    function validateEmail(email) {
        var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    function validatePhoneNumber(phone) {
        var re = /^[\+]?[1-9][\d]{0,15}$/;
        return re.test(phone.replace(/\s/g, ''));
    }
    
    // Real-time validation
    $('#salesperson_email, #client_email').on('blur', function() {
        var email = $(this).val();
        if (email && !validateEmail(email)) {
            $(this).addClass('error');
            if (!$(this).next('.error-message').length) {
                $(this).after('<small class="error-message" style="color: #dc3545;">Please enter a valid email address.</small>');
            }
        } else {
            $(this).removeClass('error');
            $(this).next('.error-message').remove();
        }
    });
    
    $('#client_phone').on('blur', function() {
        var phone = $(this).val();
        if (phone && !validatePhoneNumber(phone)) {
            $(this).addClass('error');
            if (!$(this).next('.error-message').length) {
                $(this).after('<small class="error-message" style="color: #dc3545;">Please enter a valid phone number.</small>');
            }
        } else {
            $(this).removeClass('error');
            $(this).next('.error-message').remove();
        }
    });
    
    // Amount validation
    $('#amount').on('blur', function() {
        var amount = parseFloat($(this).val());
        if (isNaN(amount) || amount <= 0) {
            $(this).addClass('error');
            if (!$(this).next('.error-message').length) {
                $(this).after('<small class="error-message" style="color: #dc3545;">Please enter a valid amount greater than 0.</small>');
            }
        } else {
            $(this).removeClass('error');
            $(this).next('.error-message').remove();
        }
    });
    
    // Auto-resize textarea
    $('textarea').on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
    
    // Smooth scrolling to result
    function scrollToResult(resultElement) {
        $('html, body').animate({
            scrollTop: resultElement.offset().top - 100
        }, 500);
    }
    
    // Show result with animation
    function showResult(element, content, type) {
        element.removeClass('success error loading').addClass(type);
        element.html(content);
        element.slideDown(300, function() {
            scrollToResult(element);
        });
    }
    
    // Prevent form submission on Enter key in specific fields
    $('#card_number, #expiry_date, #cvv').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            var form = $(this).closest('form');
            var inputs = form.find('input:visible');
            var currentIndex = inputs.index(this);
            var nextInput = inputs.eq(currentIndex + 1);
            
            if (nextInput.length) {
                nextInput.focus();
            } else {
                form.find('button[type="submit"]').focus();
            }
        }
    });
    
});