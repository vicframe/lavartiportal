/**
 * Dashboard functionality
 */
$(document).ready(function() {
    // Toggle sidebar on mobile
    $('.menu-toggle').on('click', function() {
        $('.sidebar').toggleClass('open');
    });

    // User profile dropdown
    $('.dropdown-toggle').on('click', function() {
        $('.dropdown-menu').toggleClass('show');
    });

    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.dropdown').length) {
            $('.dropdown-menu').removeClass('show');
        }
    });

    // Tab switching for quick actions
    $('.action-tabs .tab').on('click', function() {
        const targetTab = $(this).data('tab');
        
        // Update active tab
        $('.action-tabs .tab').removeClass('active');
        $(this).addClass('active');
        
        // Show related content
        $('.tab-content').hide();
        $(`#${targetTab}`).show();
    });

    // Copy affiliate link
    $('#copy-affiliate-link').on('click', function() {
        const affiliateLinkInput = document.getElementById('affiliate-link');
        affiliateLinkInput.select();
        document.execCommand('copy');
        
        // Show feedback
        const originalText = $(this).text();
        $(this).text('Copied!');
        
        setTimeout(function() {
            $('#copy-affiliate-link').text(originalText);
        }, 2000);
    });

    // Load recent activity
    loadRecentActivity();

    // Load stats
    loadDashboardStats();
});

/**
 * Load recent activity data via AJAX
 */
function loadRecentActivity(page = 1) {
    // Get base URL from the page
    const baseUrl = document.querySelector('meta[name="base-url"]').getAttribute('content') || '';
    const apiUrl = baseUrl + '/api/activity.php';
    
    $.ajax({
        url: apiUrl,
        type: 'GET',
        data: { page: page },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                displayActivity(response.data);
            } else {
                $('#recent-activity-list').html('<p class="text-center text-muted">Unable to load activity data.</p>');
            }
        },
        error: function() {
            $('#recent-activity-list').html('<p class="text-center text-muted">Error loading activity data.</p>');
        }
    });
}

/**
 * Display activity data in the activity list
 */
function displayActivity(activities) {
    if (!activities || activities.length === 0) {
        $('#recent-activity-list').html('<p class="text-center text-muted">No recent activity found.</p>');
        return;
    }
    
    let html = '';
    
    activities.forEach(function(activity) {
        html += `
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="${getActivityIcon(activity.type)}"></i>
                </div>
                <div class="activity-details">
                    <h3>${activity.title}</h3>
                    <p>${activity.description}</p>
                </div>
                <div class="activity-time">
                    ${formatTimeAgo(activity.created_at)}
                </div>
            </div>
        `;
    });
    
    $('#recent-activity-list').html(html);
}

/**
 * Load dashboard statistics via AJAX
 */
function loadDashboardStats() {
    // Get base URL from the page
    const baseUrl = document.querySelector('meta[name="base-url"]').getAttribute('content') || '';
    const apiUrl = baseUrl + '/api/dashboard-stats.php';
    
    $.ajax({
        url: apiUrl,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                updateDashboardStats(response.data);
            }
        },
        error: function() {
            console.error('Error loading dashboard stats');
        }
    });
}

/**
 * Update dashboard statistics with data
 */
function updateDashboardStats(stats) {
    // Update team members count
    $('#team-members-count').text(stats.team_members || 0);
    
    // Update commissions amount
    $('#commissions-amount').text(formatCurrency(stats.commissions || 0));
    
    // Update membership tier
    $('#membership-tier').text(stats.membership_tier || 'Basic');
    $('#membership-price').text(formatCurrency(stats.membership_price || 0) + '/mo');
    
    // Update affiliate clicks
    $('#affiliate-clicks').text(stats.affiliate_clicks || 0);
}

/**
 * Get appropriate icon for activity type
 */
function getActivityIcon(type) {
    switch (type) {
        case 'commission':
            return 'fas fa-dollar-sign';
        case 'signup':
            return 'fas fa-user-plus';
        case 'purchase':
            return 'fas fa-shopping-cart';
        case 'login':
            return 'fas fa-sign-in-alt';
        default:
            return 'fas fa-bell';
    }
}

/**
 * Format time ago
 */
function formatTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    if (seconds < 60) {
        return 'just now';
    }
    
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) {
        return minutes + ' min ago';
    }
    
    const hours = Math.floor(minutes / 60);
    if (hours < 24) {
        return hours + ' hours ago';
    }
    
    const days = Math.floor(hours / 24);
    if (days < 30) {
        return days + ' days ago';
    }
    
    const months = Math.floor(days / 30);
    if (months < 12) {
        return months + ' months ago';
    }
    
    const years = Math.floor(months / 12);
    return years + ' years ago';
}

/**
 * Format currency with $ sign and 2 decimal places
 */
function formatCurrency(amount) {
    return '$' + parseFloat(amount).toFixed(2);
}