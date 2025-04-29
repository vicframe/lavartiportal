/**
 * Main JavaScript file for LaVarti Systems
 */
$(document).ready(function() {
    // Initialize Bootstrap tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Initialize Bootstrap popovers
    $('[data-bs-toggle="popover"]').popover();
    
    // Form validation
    $('form.needs-validation').each(function() {
        $(this).on('submit', function(event) {
            if (!this.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            $(this).addClass('was-validated');
        });
    });
    
    // AJAX form submissions
    $('.ajax-form').each(function() {
        $(this).on('submit', function(e) {
            e.preventDefault();
            
            const form = $(this);
            const url = form.attr('action');
            const method = form.attr('method') || 'POST';
            const formData = new FormData(this);
            
            // Get the submit button and loading text
            const submitBtn = form.find('[type="submit"]');
            const originalBtnText = submitBtn.html();
            const loadingText = submitBtn.data('loading-text') || 'Loading...';
            
            // Show loading state
            submitBtn.html(loadingText).prop('disabled', true);
            
            $.ajax({
                url: url,
                type: method,
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        if (response.message) {
                            showAlert('success', response.message);
                        }
                        
                        if (response.redirect) {
                            setTimeout(function() {
                                window.location.href = response.redirect;
                            }, 1500);
                        } else {
                            submitBtn.html(originalBtnText).prop('disabled', false);
                            
                            // Execute success callback if defined
                            if (typeof form.data('success-callback') === 'function') {
                                form.data('success-callback')(response);
                            }
                        }
                    } else {
                        if (response.message) {
                            showAlert('danger', response.message);
                        } else {
                            showAlert('danger', 'An error occurred. Please try again.');
                        }
                        submitBtn.html(originalBtnText).prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    showAlert('danger', 'An error occurred: ' + error);
                    submitBtn.html(originalBtnText).prop('disabled', false);
                }
            });
        });
    });
    
    // Handle logout confirmation
    $('.confirm-logout').on('click', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to log out?')) {
            window.location.href = $(this).attr('href');
        }
    });
});

/**
 * Format currency with $ sign and 2 decimal places
 */
function formatCurrency(amount) {
    return '$' + parseFloat(amount).toFixed(2);
}

/**
 * Format date to readable format
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

/**
 * Show loading state
 */
function showLoading(message = 'Loading...') {
    if ($('#loading-overlay').length === 0) {
        $('body').append(`
            <div id="loading-overlay">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div id="loading-message">${message}</div>
            </div>
        `);
    } else {
        $('#loading-message').text(message);
        $('#loading-overlay').show();
    }
}

/**
 * Hide loading state
 */
function hideLoading() {
    $('#loading-overlay').hide();
}

/**
 * Show alert message
 */
function showAlert(type, message, duration = 5000) {
    // Create alert container if it doesn't exist
    if ($('#alert-container').length === 0) {
        $('body').append('<div id="alert-container"></div>');
    }
    
    // Generate unique ID for this alert
    const alertId = 'alert-' + Date.now();
    
    // Create alert element
    const alertHtml = `
        <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    // Add alert to container
    $('#alert-container').append(alertHtml);
    
    // Auto close after duration
    if (duration > 0) {
        setTimeout(function() {
            $(`#${alertId}`).alert('close');
        }, duration);
    }
}

/**
 * Handle form submission with custom callbacks
 */
function handleFormSubmit(formElement, successCallback, errorCallback) {
    const form = $(formElement);
    const url = form.attr('action');
    const method = form.attr('method') || 'POST';
    const formData = new FormData(formElement);
    
    // Show loading state
    showLoading('Processing...');
    
    $.ajax({
        url: url,
        type: method,
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            hideLoading();
            
            if (response.success) {
                if (typeof successCallback === 'function') {
                    successCallback(response);
                } else if (response.message) {
                    showAlert('success', response.message);
                }
                
                if (response.redirect) {
                    setTimeout(function() {
                        window.location.href = response.redirect;
                    }, 1500);
                }
            } else {
                if (typeof errorCallback === 'function') {
                    errorCallback(response);
                } else if (response.message) {
                    showAlert('danger', response.message);
                } else {
                    showAlert('danger', 'An error occurred. Please try again.');
                }
            }
        },
        error: function(xhr, status, error) {
            hideLoading();
            
            if (typeof errorCallback === 'function') {
                errorCallback({ success: false, message: error });
            } else {
                showAlert('danger', 'An error occurred: ' + error);
            }
        }
    });
}