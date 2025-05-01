/**
 * Main JavaScript file for LaVarti Systems
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize Bootstrap popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Handle custom file inputs
    const fileInputs = document.querySelectorAll('.custom-file-input');
    fileInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            const label = this.nextElementSibling;
            const fileName = this.files[0].name;
            label.textContent = fileName;
        });
    });
    
    // Add auto-dismiss to alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-persistent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // Handle form submission with AJAX
    const ajaxForms = document.querySelectorAll('form[data-ajax="true"]');
    ajaxForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            handleFormSubmit(form);
        });
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
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', options);
}

/**
 * Show loading state
 */
function showLoading(message = 'Loading...') {
    const loadingHtml = `
        <div class="loading-overlay" id="loadingOverlay">
            <div class="loading-spinner">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">${message}</p>
            </div>
        </div>
    `;
    
    // Add overlay if it doesn't exist
    if (!document.getElementById('loadingOverlay')) {
        document.body.insertAdjacentHTML('beforeend', loadingHtml);
    }
    
    document.getElementById('loadingOverlay').style.display = 'flex';
}

/**
 * Hide loading state
 */
function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

/**
 * Show alert message
 */
function showAlert(type, message, duration = 5000) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    // Create alert container if it doesn't exist
    let alertContainer = document.getElementById('alertContainer');
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.id = 'alertContainer';
        alertContainer.className = 'alert-container';
        document.body.prepend(alertContainer);
    }
    
    // Add alert to container
    alertContainer.insertAdjacentHTML('beforeend', alertHtml);
    
    // Auto-dismiss after duration
    const alerts = alertContainer.querySelectorAll('.alert');
    const latestAlert = alerts[alerts.length - 1];
    
    setTimeout(function() {
        const bsAlert = new bootstrap.Alert(latestAlert);
        bsAlert.close();
    }, duration);
}

/**
 * Handle form submission with custom callbacks
 */
function handleFormSubmit(formElement, successCallback, errorCallback) {
    // Show loading
    showLoading('Processing...');
    
    // Get base URL from the page
    const baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';
    
    // Collect form data
    const formData = new FormData(formElement);
    
    // Use form action as-is if it starts with http, otherwise prepend baseUrl
    const actionUrl = formElement.action.startsWith('http') ? 
        formElement.action : 
        baseUrl + formElement.action.replace(/^\/+/, '/');
    
    // Send AJAX request
    fetch(actionUrl, {
        method: formElement.method,
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        // Hide loading
        hideLoading();
        
        if (data.success) {
            // Success
            if (typeof successCallback === 'function') {
                successCallback(data);
            } else {
                // Default success behavior
                showAlert('success', data.message || 'Operation completed successfully');
                
                // Redirect if specified
                if (data.redirect) {
                    setTimeout(function() {
                        // Handle relative URL redirects by prepending base URL if needed
                        const redirectUrl = data.redirect.startsWith('http') ? 
                            data.redirect : 
                            baseUrl + data.redirect.replace(/^\/+/, '/');
                        window.location.href = redirectUrl;
                    }, 1000);
                }
            }
        } else {
            // Error
            if (typeof errorCallback === 'function') {
                errorCallback(data);
            } else {
                // Default error behavior
                showAlert('danger', data.error || 'An error occurred');
            }
        }
    })
    .catch(error => {
        // Hide loading
        hideLoading();
        
        // Handle error
        if (typeof errorCallback === 'function') {
            errorCallback({ error: 'Request failed' });
        } else {
            // Default error behavior
            showAlert('danger', 'Request failed: ' + error.message);
        }
    });
}