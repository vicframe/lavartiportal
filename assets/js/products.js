/**
 * Products and Membership Tiers JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Purchase product button click handlers
    const purchaseButtons = document.querySelectorAll('.purchase-product');
    purchaseButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const productName = this.getAttribute('data-product-name');
            const productPrice = this.getAttribute('data-product-price');
            
            // Set values in modal
            document.getElementById('productId').value = productId;
            document.getElementById('selectedPlan').textContent = productName;
            document.getElementById('planName').textContent = productName;
            document.getElementById('planPrice').textContent = '$' + parseFloat(productPrice).toFixed(2) + '/month';
            document.getElementById('successPlanName').textContent = productName;
            
            // Show payment modal
            const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
            paymentModal.show();
        });
    });
    
    // Process payment button click handler
    const processPaymentButton = document.getElementById('processPayment');
    if (processPaymentButton) {
        processPaymentButton.addEventListener('click', function() {
            // Validate form fields
            const paymentForm = document.getElementById('paymentForm');
            if (!paymentForm.checkValidity()) {
                paymentForm.classList.add('was-validated');
                return;
            }
            
            // Hide payment modal
            const paymentModal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
            paymentModal.hide();
            
            // Show processing modal
            const processingModal = new bootstrap.Modal(document.getElementById('processingModal'));
            processingModal.show();
            
            // Get form data
            const productId = document.getElementById('productId').value;
            const cardName = document.getElementById('cardName').value;
            const cardNumber = document.getElementById('cardNumber').value;
            const cardExpiry = document.getElementById('cardExpiry').value;
            const cardCvv = document.getElementById('cardCvv').value;
            const billingAddress = document.getElementById('billingAddress').value;
            const billingCity = document.getElementById('billingCity').value;
            const billingZip = document.getElementById('billingZip').value;
            
            // Prepare data for submission
            const paymentData = {
                productId,
                cardName,
                cardNumber: cardNumber.replace(/\s/g, ''), // Remove spaces
                cardExpiry,
                cardCvv,
                billingAddress,
                billingCity,
                billingZip
            };
            
            // In a real implementation, this would be an AJAX call to process the payment
            // For this demo, we'll simulate a successful payment after a short delay
            setTimeout(() => {
                // Hide processing modal
                processingModal.hide();
                
                // Show success modal
                const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                successModal.show();
                
                // Reset form
                paymentForm.reset();
                paymentForm.classList.remove('was-validated');
            }, 2000);
            
            // In a real implementation, the code would look something like this:
            /*
            fetch('/api/process-payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(paymentData)
            })
            .then(response => response.json())
            .then(data => {
                // Hide processing modal
                processingModal.hide();
                
                if (data.success) {
                    // Show success modal
                    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                    successModal.show();
                    
                    // Reset form
                    paymentForm.reset();
                    paymentForm.classList.remove('was-validated');
                } else {
                    // Show error message
                    alert(data.message || 'Payment processing failed. Please try again.');
                }
            })
            .catch(error => {
                // Hide processing modal
                processingModal.hide();
                
                // Show error message
                alert('An error occurred while processing your payment. Please try again.');
                console.error('Payment error:', error);
            });
            */
        });
    }
    
    // Format credit card number input with spaces
    const cardNumberInput = document.getElementById('cardNumber');
    if (cardNumberInput) {
        cardNumberInput.addEventListener('input', function(e) {
            let value = e.target.value;
            
            // Remove all non-digit characters
            value = value.replace(/\D/g, '');
            
            // Add a space after every 4 digits
            value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
            
            // Limit to 19 characters (16 digits + 3 spaces)
            value = value.substring(0, 19);
            
            // Update the input value
            e.target.value = value;
        });
    }
    
    // Format card expiry date (MM/YY)
    const cardExpiryInput = document.getElementById('cardExpiry');
    if (cardExpiryInput) {
        cardExpiryInput.addEventListener('input', function(e) {
            let value = e.target.value;
            
            // Remove all non-digit characters
            value = value.replace(/\D/g, '');
            
            // Add a slash after 2 digits (MM/YY)
            if (value.length > 2) {
                value = value.substring(0, 2) + '/' + value.substring(2);
            }
            
            // Limit to 5 characters (MM/YY)
            value = value.substring(0, 5);
            
            // Update the input value
            e.target.value = value;
        });
    }
    
    // Format CVV to limit to 3-4 digits
    const cardCvvInput = document.getElementById('cardCvv');
    if (cardCvvInput) {
        cardCvvInput.addEventListener('input', function(e) {
            let value = e.target.value;
            
            // Remove all non-digit characters
            value = value.replace(/\D/g, '');
            
            // Limit to 4 characters (some cards have 4-digit CVVs)
            value = value.substring(0, 4);
            
            // Update the input value
            e.target.value = value;
        });
    }
    
    // Additional product info modals
    const productInfoButtons = document.querySelectorAll('.product-info-btn');
    productInfoButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.getAttribute('data-product-id');
            const productName = this.getAttribute('data-product-name');
            
            // In a real implementation, this would fetch detailed product info from the server
            // For the demo, we'll just show a simple alert
            alert(`Detailed information about ${productName} would be displayed here.`);
        });
    });
    
    // Compare plans button
    const comparePlansButton = document.getElementById('comparePlansBtn');
    if (comparePlansButton) {
        comparePlansButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            // In a real implementation, this would show a comparison modal
            // For the demo, we'll scroll to the pricing table
            const pricingTable = document.querySelector('.pricing-table');
            if (pricingTable) {
                pricingTable.scrollIntoView({ behavior: 'smooth' });
            }
        });
    }
});

// Function to load product reviews
function loadProductReviews(productId) {
    const reviewsContainer = document.getElementById('productReviews');
    if (!reviewsContainer) return;
    
    // Show loading indicator
    reviewsContainer.innerHTML = `
        <div class="text-center p-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 mb-0">Loading reviews...</p>
        </div>
    `;
    
    // In a real implementation, this would fetch reviews from the server
    fetch(`/api/product-reviews.php?product_id=${productId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.reviews.length > 0) {
                let reviewsHtml = '';
                
                data.reviews.forEach(review => {
                    // Generate stars for rating
                    let stars = '';
                    for (let i = 1; i <= 5; i++) {
                        if (i <= review.rating) {
                            stars += '<i class="fas fa-star text-warning"></i>';
                        } else {
                            stars += '<i class="far fa-star text-warning"></i>';
                        }
                    }
                    
                    reviewsHtml += `
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 class="card-title mb-0">${review.title}</h5>
                                    <div>${stars}</div>
                                </div>
                                <p class="card-text">${review.comment}</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">${review.author}</small>
                                    <small class="text-muted">${formatDate(review.date)}</small>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                reviewsContainer.innerHTML = reviewsHtml;
            } else {
                // Show empty state
                reviewsContainer.innerHTML = `
                    <div class="text-center p-4">
                        <i class="fas fa-comment-slash fa-3x text-muted mb-3"></i>
                        <h5>No Reviews Yet</h5>
                        <p class="mb-0">Be the first to review this product!</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading reviews:', error);
            reviewsContainer.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error loading reviews. Please try again later.
                </div>
            `;
        });
}

// Function to handle submitting a product review
function submitProductReview(formElement) {
    // Prevent default form submission
    formElement.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!formElement.checkValidity()) {
            formElement.classList.add('was-validated');
            return;
        }
        
        // Get form data
        const formData = new FormData(formElement);
        const reviewData = {
            productId: formData.get('productId'),
            rating: formData.get('rating'),
            title: formData.get('title'),
            comment: formData.get('comment')
        };
        
        // Disable submit button and show loading state
        const submitButton = formElement.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...';
        
        // In a real implementation, this would send the review to the server
        fetch('/api/submit-review.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(reviewData)
        })
        .then(response => response.json())
        .then(data => {
            // Reset button state
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
            
            if (data.success) {
                // Show success message
                alert('Thank you for your review!');
                
                // Reset form
                formElement.reset();
                formElement.classList.remove('was-validated');
                
                // Reload reviews to show the new one
                loadProductReviews(reviewData.productId);
            } else {
                // Show error message
                alert(data.message || 'Failed to submit review. Please try again.');
            }
        })
        .catch(error => {
            // Reset button state
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
            
            // Show error message
            alert('An error occurred while submitting your review. Please try again.');
            console.error('Review submission error:', error);
        });
    });
}
