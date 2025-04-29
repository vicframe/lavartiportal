/**
 * Dashboard specific JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Copy affiliate link handler
    const copyAffiliateLink = document.getElementById('copyAffiliateLink');
    if (copyAffiliateLink) {
        copyAffiliateLink.addEventListener('click', function() {
            const affiliateLink = document.getElementById('affiliateLink');
            affiliateLink.select();
            document.execCommand('copy');
            
            // Show success feedback
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-check"></i> Copied!';
            
            setTimeout(() => {
                this.innerHTML = originalText;
            }, 2000);
        });
    }
    
    // Load recent activity (mocked in this version)
    const activityContainer = document.getElementById('recentActivityContainer');
    if (activityContainer) {
        const loadingSpinner = activityContainer.querySelector('.loading-spinner');
        if (loadingSpinner) {
            // In a real implementation, this would be an AJAX call to get actual data
            setTimeout(() => {
                loadingSpinner.style.display = 'none';
                
                // Check if there's any content already (for empty state)
                if (activityContainer.querySelector('.activity-item')) {
                    activityContainer.querySelector('.activity-list').style.display = 'block';
                } else {
                    const emptyState = document.createElement('div');
                    emptyState.className = 'text-center p-4';
                    emptyState.innerHTML = `
                        <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                        <h5>No Recent Activity</h5>
                        <p class="mb-0">Your recent activities will appear here.</p>
                    `;
                    activityContainer.appendChild(emptyState);
                }
            }, 1000);
        }
    }
    
    // Initialize charts if needed
    const earningsChartCanvas = document.getElementById('earningsChart');
    if (earningsChartCanvas) {
        // This would be populated with real data in production
        const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        const data = {
            labels: labels,
            datasets: [{
                label: 'Commissions',
                backgroundColor: 'rgba(13, 110, 253, 0.2)',
                borderColor: 'rgb(13, 110, 253)',
                data: [0, 0, 0, 0, 0, 0],
                tension: 0.4
            }]
        };
        
        const config = {
            type: 'line',
            data: data,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Commission Earnings'
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
        };
        
        // Initialize chart (would use Chart.js in production)
        // const earningsChart = new Chart(earningsChartCanvas, config);
    }
    
    // Handle notification preferences toggles
    const notificationToggles = document.querySelectorAll('.notification-toggle');
    notificationToggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const notificationType = this.getAttribute('data-notification-type');
            const isEnabled = this.checked;
            
            // In production, this would send an AJAX request to update preferences
            console.log(`Notification preference updated: ${notificationType} is now ${isEnabled ? 'enabled' : 'disabled'}`);
            
            // Show feedback
            const feedbackElement = document.createElement('div');
            feedbackElement.className = 'alert alert-success alert-dismissible fade show mt-3';
            feedbackElement.innerHTML = `
                Notification preferences updated!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            
            const preferencesContainer = document.querySelector('.notification-preferences');
            if (preferencesContainer) {
                preferencesContainer.appendChild(feedbackElement);
                
                // Auto dismiss after 3 seconds
                setTimeout(() => {
                    feedbackElement.remove();
                }, 3000);
            }
        });
    });
    
    // Handle quick action buttons
    const quickActionButtons = document.querySelectorAll('.quick-action-btn');
    quickActionButtons.forEach(button => {
        button.addEventListener('click', function() {
            const actionType = this.getAttribute('data-action');
            
            // Different actions based on button type
            switch(actionType) {
                case 'upgrade':
                    window.location.href = '/dashboard/products.php';
                    break;
                case 'affiliate':
                    window.location.href = '/dashboard/affiliate.php';
                    break;
                case 'support':
                    // This would open a support ticket modal in production
                    alert('Support functionality would open here');
                    break;
                default:
                    break;
            }
        });
    });
});

// Function to update dashboard stats (would be called via AJAX in production)
function updateDashboardStats() {
    fetch('/api/dashboard-stats.php')
        .then(response => response.json())
        .then(data => {
            // Update DOM elements with the returned data
            if (data.success) {
                document.getElementById('totalEarnings').textContent = formatCurrency(data.totalEarnings);
                document.getElementById('pendingCommissions').textContent = formatCurrency(data.pendingCommissions);
                document.getElementById('activeReferrals').textContent = data.activeReferrals;
                
                // Also update any charts with new data
                // earningsChart.data.datasets[0].data = data.earningsChartData;
                // earningsChart.update();
            }
        })
        .catch(error => {
            console.error('Error fetching dashboard stats:', error);
        });
}

// Function to load user's recent activity
function loadRecentActivity(page = 1) {
    const activityContainer = document.getElementById('recentActivityContainer');
    if (!activityContainer) return;
    
    // Show loading indicator
    activityContainer.innerHTML = `
        <div class="text-center p-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 mb-0">Loading activity...</p>
        </div>
    `;
    
    // In production, this would fetch data from the server
    fetch(`/api/user-activity.php?page=${page}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.activities.length > 0) {
                let activitiesHtml = '<div class="list-group">';
                
                data.activities.forEach(activity => {
                    activitiesHtml += `
                        <div class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">${activity.title}</h6>
                                <small>${formatDate(activity.date)}</small>
                            </div>
                            <p class="mb-1">${activity.description}</p>
                            <small class="text-muted">${activity.type}</small>
                        </div>
                    `;
                });
                
                activitiesHtml += '</div>';
                
                // Add pagination if needed
                if (data.totalPages > 1) {
                    activitiesHtml += '<div class="d-flex justify-content-center mt-3"><nav aria-label="Activity pagination">';
                    activitiesHtml += '<ul class="pagination">';
                    
                    for (let i = 1; i <= data.totalPages; i++) {
                        activitiesHtml += `
                            <li class="page-item ${i === page ? 'active' : ''}">
                                <a class="page-link" href="#" onclick="loadRecentActivity(${i}); return false;">${i}</a>
                            </li>
                        `;
                    }
                    
                    activitiesHtml += '</ul></nav></div>';
                }
                
                activityContainer.innerHTML = activitiesHtml;
            } else {
                // Show empty state
                activityContainer.innerHTML = `
                    <div class="text-center p-4">
                        <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                        <h5>No Recent Activity</h5>
                        <p class="mb-0">Your recent activities will appear here.</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading activity:', error);
            activityContainer.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error loading activity. Please try again later.
                </div>
            `;
        });
}
