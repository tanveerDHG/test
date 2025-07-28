jQuery(document).ready(function($) {
    
    // Calculate total cost
    function calculateTotal() {
        var price = parseFloat($('#item_price').val()) || 0;
        var discount = parseFloat($('#discount').val()) || 0;
        var total = Math.max(0, price - discount);
        $('#total_cost').val(total.toFixed(2));
        return total;
    }
    
    // Update price and discount calculations
    $('#item_price, #discount').on('input', function() {
        calculateTotal();
    });
    
    // Step navigation
    $('.next-step').on('click', function() {
        var currentStep = $('.form-step.active');
        var currentStepNumber = parseInt(currentStep.data('step'));
        
        // Validate current step
        var isValid = validateStep(currentStepNumber);
        if (!isValid) {
            return;
        }
        
        // Update progress bar
        $('.step[data-step="' + currentStepNumber + '"]').removeClass('active');
        $('.step[data-step="' + (currentStepNumber + 1) + '"]').addClass('active');
        
        // Show next step
        currentStep.removeClass('active');
        $('.form-step[data-step="' + (currentStepNumber + 1) + '"]').addClass('active');
        
        // Update summary
        updateSummary();
    });
    
    $('.prev-step').on('click', function() {
        var currentStep = $('.form-step.active');
        var currentStepNumber = parseInt(currentStep.data('step'));
        
        // Update progress bar
        $('.step[data-step="' + currentStepNumber + '"]').removeClass('active');
        $('.step[data-step="' + (currentStepNumber - 1) + '"]').addClass('active');
        
        // Show previous step
        currentStep.removeClass('active');
        $('.form-step[data-step="' + (currentStepNumber - 1) + '"]').addClass('active');
    });
    
    // Validate form step
    function validateStep(stepNumber) {
        var isValid = true;
        var currentStep = $('.form-step[data-step="' + stepNumber + '"]');
        
        // Clear previous validation
        currentStep.find('.form-control').removeClass('is-invalid is-valid');
        currentStep.find('.invalid-feedback').remove();
        
        // Validate required fields
        currentStep.find('[required]').each(function() {
            var field = $(this);
            var value = field.val().trim();
            
            if (!value) {
                field.addClass('is-invalid');
                field.after('<div class="invalid-feedback">This field is required.</div>');
                isValid = false;
            } else {
                field.addClass('is-valid');
            }
        });
        
        // Validate price field
        var price = parseFloat($('#item_price').val());
        if (price <= 0) {
            $('#item_price').addClass('is-invalid');
            $('#item_price').after('<div class="invalid-feedback">Price must be greater than 0.</div>');
            isValid = false;
        }
        
        // Calculate total
        calculateTotal();
        
        return isValid;
    }
    
    // Update summary
    function updateSummary() {
        $('#summary-item-name').text($('#item_name').val());
        $('#summary-category').text($('#item_category').val());
        $('#summary-price').text('$' + parseFloat($('#item_price').val() || 0).toFixed(2));
        $('#summary-discount').text('$' + parseFloat($('#discount').val() || 0).toFixed(2));
        $('#summary-total').text('$' + calculateTotal().toFixed(2));
        $('#summary-gateway').text($('#payment_gateway option:selected').text());
    }
    
    // Form submission
    $('#payment-link-form').on('submit', function(e) {
        e.preventDefault();
        
        // Validate final step
        if (!validateStep(2)) {
            return;
        }
        
        // Show loading state
        var submitBtn = $('.generate-link');
        var originalText = submitBtn.text();
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Generating...');
        
        // Prepare form data
        var formData = {
            action: 'generate_payment_link',
            nonce: paymentLinkAjax.nonce,
            item_name: $('#item_name').val(),
            item_price: $('#item_price').val(),
            discount: $('#discount').val(),
            item_description: $('#item_description').val(),
            item_category: $('#item_category').val(),
            payment_gateway: $('#payment_gateway').val(),
            total_cost: calculateTotal()
        };
        
        // Submit via AJAX
        $.ajax({
            url: paymentLinkAjax.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                submitBtn.prop('disabled', false).text(originalText);
                
                if (response.success) {
                    // Show generated link
                    $('#generated-link').val(response.data.checkout_url);
                    $('#generated-link-container').slideDown();
                    
                    // Scroll to generated link
                    $('html, body').animate({
                        scrollTop: $('#generated-link-container').offset().top - 50
                    }, 500);
                    
                    // Show success message
                    showAlert('success', response.data.message);
                    
                } else {
                    showAlert('error', response.data || 'Failed to generate payment link');
                }
            },
            error: function() {
                submitBtn.prop('disabled', false).text(originalText);
                showAlert('error', 'An error occurred. Please try again.');
            }
        });
    });
    
    // Copy link functionality
    $('.copy-link').on('click', function() {
        var linkInput = $('#generated-link');
        linkInput.select();
        document.execCommand('copy');
        
        // Visual feedback
        var copyBtn = $(this);
        var originalText = copyBtn.text();
        copyBtn.text('Copied!').addClass('btn-success').removeClass('btn-primary');
        
        setTimeout(function() {
            copyBtn.text(originalText).addClass('btn-primary').removeClass('btn-success');
        }, 2000);
        
        showAlert('success', 'Payment link copied to clipboard!');
    });
    
    // Show alert messages
    function showAlert(type, message) {
        var alertClass = type === 'success' ? 'alert-success' : 'alert-error';
        var alert = $('<div class="alert ' + alertClass + '">' + message + '</div>');
        
        // Remove existing alerts
        $('.alert').remove();
        
        // Add new alert
        $('.form-container h2').after(alert);
        
        // Auto remove after 5 seconds
        setTimeout(function() {
            alert.fadeOut();
        }, 5000);
    }
    
    // Real-time validation
    $('.form-control').on('blur', function() {
        var field = $(this);
        var value = field.val().trim();
        
        field.removeClass('is-invalid is-valid');
        field.siblings('.invalid-feedback').remove();
        
        if (field.prop('required') && !value) {
            field.addClass('is-invalid');
            field.after('<div class="invalid-feedback">This field is required.</div>');
        } else if (value) {
            field.addClass('is-valid');
            
            // Special validation for price
            if (field.attr('id') === 'item_price' && parseFloat(value) <= 0) {
                field.removeClass('is-valid').addClass('is-invalid');
                field.after('<div class="invalid-feedback">Price must be greater than 0.</div>');
            }
        }
    });
    
    // Enhanced select styling
    $('#payment_gateway').on('change', function() {
        var selectedValue = $(this).val();
        if (selectedValue) {
            $(this).addClass('is-valid').removeClass('is-invalid');
        } else {
            $(this).addClass('is-invalid').removeClass('is-valid');
        }
    });
    
    // Auto-resize textarea
    $('textarea').on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
    
    // Format currency inputs
    $('#item_price, #discount').on('input', function() {
        var value = $(this).val();
        if (value && !isNaN(value)) {
            $(this).val(parseFloat(value).toFixed(2));
        }
        calculateTotal();
    });
    
    // Prevent form submission on Enter key (except for textarea)
    $('#payment-link-form').on('keypress', function(e) {
        if (e.which === 13 && e.target.tagName !== 'TEXTAREA') {
            e.preventDefault();
            if ($('.form-step.active').data('step') === 1) {
                $('.next-step').click();
            }
        }
    });
    
    // Initialize tooltips if Bootstrap tooltips are available
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Initialize the form
    calculateTotal();
    
});