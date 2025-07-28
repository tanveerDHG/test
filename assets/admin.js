jQuery(document).ready(function($) {
    
    // View details modal functionality
    $('.wpf-view-details').on('click', function() {
        var formId = $(this).data('form-id');
        showFormDetails(formId);
    });
    
    function showFormDetails(formId) {
        // Create modal if it doesn't exist
        if (!$('#wpf-details-modal').length) {
            var modalHtml = '<div id="wpf-details-modal" class="wpf-modal">' +
                '<div class="wpf-modal-content">' +
                '<span class="wpf-modal-close">&times;</span>' +
                '<div id="wpf-modal-body">' +
                '<h2>Loading...</h2>' +
                '</div>' +
                '</div>' +
                '</div>';
            $('body').append(modalHtml);
        }
        
        var modal = $('#wpf-details-modal');
        var modalBody = $('#wpf-modal-body');
        
        // Show modal
        modal.show();
        
        // Load form details via AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpf_get_form_details',
                form_id: formId,
                nonce: wpf_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    modalBody.html(response.data);
                } else {
                    modalBody.html('<h2>Error</h2><p>Could not load form details.</p>');
                }
            },
            error: function() {
                modalBody.html('<h2>Error</h2><p>Could not load form details.</p>');
            }
        });
    }
    
    // Close modal
    $(document).on('click', '.wpf-modal-close, .wpf-modal', function(e) {
        if (e.target === this) {
            $('.wpf-modal').hide();
        }
    });
    
    // Prevent modal from closing when clicking inside modal content
    $(document).on('click', '.wpf-modal-content', function(e) {
        e.stopPropagation();
    });
    
    // Copy payment link functionality
    $(document).on('click', '.wpf-copy-link', function(e) {
        e.preventDefault();
        var link = $(this).data('link');
        
        // Create temporary input
        var tempInput = $('<input>');
        $('body').append(tempInput);
        tempInput.val(link).select();
        document.execCommand('copy');
        tempInput.remove();
        
        // Show feedback
        var originalText = $(this).text();
        $(this).text('Copied!').addClass('success');
        setTimeout(function() {
            $(this).text(originalText).removeClass('success');
        }.bind(this), 2000);
    });
    
    // Export functionality
    $('.wpf-export-csv').on('click', function() {
        var startDate = $('#export-start-date').val();
        var endDate = $('#export-end-date').val();
        var status = $('#export-status').val();
        
        var params = {
            action: 'wpf_export_forms',
            start_date: startDate,
            end_date: endDate,
            status: status,
            nonce: wpf_admin.nonce
        };
        
        var url = ajaxurl + '?' + $.param(params);
        window.open(url, '_blank');
    });
    
    // Bulk actions
    $('#bulk-action-selector-top, #bulk-action-selector-bottom').on('change', function() {
        var action = $(this).val();
        var selectedForms = $('input[name="form_ids[]"]:checked');
        
        if (action && selectedForms.length > 0) {
            if (action === 'delete') {
                if (confirm('Are you sure you want to delete the selected forms? This action cannot be undone.')) {
                    performBulkAction(action, selectedForms);
                }
            } else {
                performBulkAction(action, selectedForms);
            }
        } else if (action && selectedForms.length === 0) {
            alert('Please select at least one form.');
        }
    });
    
    function performBulkAction(action, selectedForms) {
        var formIds = [];
        selectedForms.each(function() {
            formIds.push($(this).val());
        });
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpf_bulk_action',
                bulk_action: action,
                form_ids: formIds,
                nonce: wpf_admin.nonce
            },
            beforeSend: function() {
                $('.wrap').addClass('wpf-loading');
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred while performing the bulk action.');
            },
            complete: function() {
                $('.wrap').removeClass('wpf-loading');
            }
        });
    }
    
    // Select all checkbox functionality
    $('#cb-select-all-1, #cb-select-all-2').on('change', function() {
        var checked = $(this).is(':checked');
        $('input[name="form_ids[]"]').prop('checked', checked);
    });
    
    // Update select all when individual checkboxes change
    $('input[name="form_ids[]"]').on('change', function() {
        var totalCheckboxes = $('input[name="form_ids[]"]').length;
        var checkedCheckboxes = $('input[name="form_ids[]"]:checked').length;
        
        $('#cb-select-all-1, #cb-select-all-2').prop('checked', totalCheckboxes === checkedCheckboxes);
    });
    
    // Search functionality
    $('#wpf-search-forms').on('input', function() {
        var searchTerm = $(this).val().toLowerCase();
        var rows = $('.wp-list-table tbody tr');
        
        rows.each(function() {
            var text = $(this).text().toLowerCase();
            if (text.indexOf(searchTerm) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
        
        // Show "no results" message if no rows are visible
        var visibleRows = rows.filter(':visible').length;
        if (visibleRows === 0 && searchTerm.length > 0) {
            if (!$('.wpf-no-results').length) {
                $('.wp-list-table tbody').append('<tr class="wpf-no-results"><td colspan="7" style="text-align: center; padding: 20px;">No forms found matching your search.</td></tr>');
            }
        } else {
            $('.wpf-no-results').remove();
        }
    });
    
    // Filter by status
    $('#wpf-filter-status').on('change', function() {
        var selectedStatus = $(this).val();
        var rows = $('.wp-list-table tbody tr');
        
        if (selectedStatus === '') {
            rows.show();
        } else {
            rows.each(function() {
                var status = $(this).find('.wpf-status').text().toLowerCase();
                if (status === selectedStatus) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }
    });
    
    // Auto-refresh functionality
    var autoRefreshInterval;
    
    $('#wpf-auto-refresh').on('change', function() {
        if ($(this).is(':checked')) {
            autoRefreshInterval = setInterval(function() {
                location.reload();
            }, 30000); // Refresh every 30 seconds
        } else {
            clearInterval(autoRefreshInterval);
        }
    });
    
    // Tooltips
    $('.wpf-tooltip').on('mouseenter', function() {
        var tooltip = $(this).attr('data-tooltip');
        if (tooltip) {
            var tooltipElement = $('<div class="wpf-tooltip-bubble">' + tooltip + '</div>');
            $('body').append(tooltipElement);
            
            var offset = $(this).offset();
            tooltipElement.css({
                position: 'absolute',
                top: offset.top - tooltipElement.outerHeight() - 5,
                left: offset.left + ($(this).outerWidth() / 2) - (tooltipElement.outerWidth() / 2),
                background: '#333',
                color: '#fff',
                padding: '8px 12px',
                borderRadius: '4px',
                fontSize: '12px',
                zIndex: 9999,
                whiteSpace: 'nowrap'
            });
        }
    });
    
    $('.wpf-tooltip').on('mouseleave', function() {
        $('.wpf-tooltip-bubble').remove();
    });
    
    // Settings form validation
    $('#wpf-settings-form').on('submit', function(e) {
        var emailField = $('input[name="email_from_address"]');
        var email = emailField.val();
        
        if (email && !isValidEmail(email)) {
            e.preventDefault();
            alert('Please enter a valid email address.');
            emailField.focus();
        }
    });
    
    function isValidEmail(email) {
        var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }
    
    // Currency formatting
    $('.wpf-currency-input').on('input', function() {
        var value = $(this).val();
        var numericValue = value.replace(/[^0-9.]/g, '');
        var formattedValue = parseFloat(numericValue).toFixed(2);
        
        if (!isNaN(formattedValue)) {
            $(this).val(formattedValue);
        }
    });
    
    // Confirmation dialogs
    $('.wpf-delete-form').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this form? This action cannot be undone.')) {
            e.preventDefault();
        }
    });
    
    // Form validation feedback
    $('input[required], select[required], textarea[required]').on('blur', function() {
        if ($(this).val() === '') {
            $(this).addClass('error');
        } else {
            $(this).removeClass('error');
        }
    });
    
    // Success message auto-hide
    $('.notice.is-dismissible').delay(5000).fadeOut();
    
    // Chart functionality (if chart library is loaded)
    if (typeof Chart !== 'undefined' && $('#wpf-revenue-chart').length) {
        initRevenueChart();
    }
    
    function initRevenueChart() {
        var ctx = document.getElementById('wpf-revenue-chart').getContext('2d');
        
        // This would be populated with real data from PHP
        var chartData = {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Revenue',
                data: [1200, 1900, 3000, 5000, 2000, 3000],
                borderColor: '#0073aa',
                backgroundColor: 'rgba(0, 115, 170, 0.1)',
                tension: 0.4
            }]
        };
        
        new Chart(ctx, {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Monthly Revenue'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + S to save settings
        if ((e.ctrlKey || e.metaKey) && e.which === 83) {
            if ($('#wpf-settings-form').length) {
                e.preventDefault();
                $('#wpf-settings-form').submit();
            }
        }
        
        // Escape to close modals
        if (e.which === 27) {
            $('.wpf-modal').hide();
        }
    });
    
});