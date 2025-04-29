/**
 * Main JavaScript file for LaVarti Systems
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Handle alert dismissal
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000); // Auto close alerts after 5 seconds
    });
    
    // Copy affiliate link functionality
    const copyAffiliateLinkButton = document.getElementById('copyAffiliateLink');
    if (copyAffiliateLinkButton) {
        copyAffiliateLinkButton.addEventListener('click', function() {
            const affiliateLinkInput = document.getElementById('affiliateLink');
            affiliateLinkInput.select();
            document.execCommand('copy');
            
            // Show feedback to user
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-check"></i> Copied!';
            this.classList.remove('btn-outline-secondary', 'btn-outline-primary');
            this.classList.add('btn-success');
            
            setTimeout(() => {
                this.innerHTML = originalText;
                this.classList.remove('btn-success');
                this.classList.add('btn-outline-secondary');
            }, 2000);
        });
    }
    
    // Mobile menu enhancement
    const navbarToggler = document.querySelector('.navbar-toggler');
    if (navbarToggler) {
        navbarToggler.addEventListener('click', function() {
            document.body.classList.toggle('navbar-open');
        });
    }
    
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            
            if (href !== "#" && href.charAt(0) === '#') {
                e.preventDefault();
                
                const targetElement = document.querySelector(this.getAttribute('href'));
                if (targetElement) {
                    targetElement.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            }
        });
    });
    
    // Back to top button
    const backToTopButton = document.getElementById('back-to-top');
    if (backToTopButton) {
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                backToTopButton.style.display = 'block';
            } else {
                backToTopButton.style.display = 'none';
            }
        });
        
        backToTopButton.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
    
    // Dark mode toggle
    const darkModeToggle = document.getElementById('darkModeToggle');
    if (darkModeToggle) {
        // Check for saved theme preference or use preferred color scheme
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
            document.body.classList.add('dark-mode');
            darkModeToggle.checked = true;
        }
        
        darkModeToggle.addEventListener('change', function() {
            if (this.checked) {
                document.body.classList.add('dark-mode');
                localStorage.setItem('theme', 'dark');
            } else {
                document.body.classList.remove('dark-mode');
                localStorage.setItem('theme', 'light');
            }
        });
    }
    
    // Pricing toggle (monthly/yearly)
    const pricingToggle = document.getElementById('pricingToggle');
    if (pricingToggle) {
        pricingToggle.addEventListener('change', function() {
            const monthlyPrices = document.querySelectorAll('.price-monthly');
            const yearlyPrices = document.querySelectorAll('.price-yearly');
            
            if (this.checked) {
                // Show yearly prices
                monthlyPrices.forEach(el => el.style.display = 'none');
                yearlyPrices.forEach(el => el.style.display = 'block');
            } else {
                // Show monthly prices
                monthlyPrices.forEach(el => el.style.display = 'block');
                yearlyPrices.forEach(el => el.style.display = 'none');
            }
        });
    }
});

// Helper function to format currency
function formatCurrency(amount) {
    return '$' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

// Helper function to format date
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('en-US', options);
}

// Helper function to show loading spinner
function showLoading(message = 'Loading...') {
    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'loading-overlay';
    loadingDiv.innerHTML = `
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <div class="mt-2">${message}</div>
    `;
    document.body.appendChild(loadingDiv);
}

// Helper function to hide loading spinner
function hideLoading() {
    const loadingDiv = document.querySelector('.loading-overlay');
    if (loadingDiv) {
        loadingDiv.remove();
    }
}

// Handle form submission with AJAX
function handleFormSubmit(formElement, successCallback, errorCallback) {
    formElement.addEventListener('submit', function(event) {
        event.preventDefault();
        
        if (!formElement.checkValidity()) {
            formElement.classList.add('was-validated');
            return;
        }
        
        const formData = new FormData(formElement);
        const submitButton = formElement.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.innerHTML;
        
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...';
        
        fetch(formElement.action, {
            method: formElement.method,
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
            
            if (data.success) {
                if (typeof successCallback === 'function') {
                    successCallback(data);
                }
            } else {
                if (typeof errorCallback === 'function') {
                    errorCallback(data);
                } else {
                    alert(data.message || 'An error occurred. Please try again.');
                }
            }
        })
        .catch(error => {
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
            
            if (typeof errorCallback === 'function') {
                errorCallback({ message: 'Network error. Please check your connection and try again.' });
            } else {
                alert('Network error. Please check your connection and try again.');
            }
            console.error('Error:', error);
        });
    });
}
