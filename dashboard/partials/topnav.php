<?php
// Get current user
$current_user = get_current_logged_user();
$is_admin = isset($current_user['is_admin']) && $current_user['is_admin'];
?>

<div class="top-nav">
    <div class="left">
        <button class="menu-toggle d-md-none" id="menuToggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <div class="right">
        <?php if ($is_admin): ?>
        <div class="top-nav-item">
            <a href="/admin" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-toolbox me-1"></i> Admin Panel
            </a>
        </div>
        <?php endif; ?>
        
        <div class="top-nav-item">
            <a href="#" class="notification-icon" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-bell"></i>
                <span class="badge" id="notificationBadge"></span>
            </a>
            <div class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown">
                <div class="notification-header">
                    <h6>Notifications</h6>
                    <a href="#" class="mark-all-read">Mark all as read</a>
                </div>
                <div class="notification-body" id="notificationContainer">
                    <div class="empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <p>No new notifications</p>
                    </div>
                </div>
                <div class="notification-footer">
                    <a href="/dashboard/notifications.php">View all notifications</a>
                </div>
            </div>
        </div>
        
        <div class="top-nav-item">
            <a href="#" class="user-dropdown" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="avatar-small">
                    <span><?php echo substr($current_user['first_name'] ?? 'U', 0, 1) . substr($current_user['last_name'] ?? 'U', 0, 1); ?></span>
                </div>
            </a>
            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                <div class="user-dropdown-header">
                    <div class="avatar-small">
                        <span><?php echo substr($current_user['first_name'] ?? 'U', 0, 1) . substr($current_user['last_name'] ?? 'U', 0, 1); ?></span>
                    </div>
                    <div class="user-info">
                        <div class="name"><?php echo ($current_user['first_name'] ?? '') . ' ' . ($current_user['last_name'] ?? ''); ?></div>
                        <div class="email"><?php echo $current_user['email'] ?? ''; ?></div>
                    </div>
                </div>
                
                <div class="dropdown-divider"></div>
                
                <a class="dropdown-item" href="/dashboard/account.php">
                    <i class="fas fa-user-circle me-2"></i> My Account
                </a>
                <a class="dropdown-item" href="/dashboard/orders.php">
                    <i class="fas fa-shopping-cart me-2"></i> My Orders
                </a>
                
                <?php if ($is_admin): ?>
                <a class="dropdown-item" href="/admin">
                    <i class="fas fa-toolbox me-2"></i> Admin Panel
                </a>
                <?php endif; ?>
                
                <div class="dropdown-divider"></div>
                
                <a class="dropdown-item" href="/logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const menuToggle = document.getElementById('menuToggle');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    
    function toggleSidebar() {
        sidebar.classList.toggle('open');
    }
    
    if (menuToggle) {
        menuToggle.addEventListener('click', toggleSidebar);
    }
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }
    
    // Load notifications
    loadNotifications();
});

function loadNotifications() {
    // Use the relative_url helper to get the correct URL with base path
    const apiUrl = '<?php echo relative_url("api/notifications.php"); ?>';
    
    fetch(apiUrl)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotifications(data.notifications);
            }
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
        });
}

function updateNotifications(notifications) {
    const notificationContainer = document.getElementById('notificationContainer');
    const notificationBadge = document.getElementById('notificationBadge');
    
    if (!notifications || notifications.length === 0) {
        notificationContainer.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-bell-slash"></i>
                <p>No new notifications</p>
            </div>
        `;
        notificationBadge.style.display = 'none';
        return;
    }
    
    // Count unread notifications
    const unreadCount = notifications.filter(notification => !notification.read).length;
    
    if (unreadCount > 0) {
        notificationBadge.textContent = unreadCount;
        notificationBadge.style.display = 'block';
    } else {
        notificationBadge.style.display = 'none';
    }
    
    // Display notifications
    let notificationsHtml = '';
    
    notifications.slice(0, 5).forEach(notification => {
        const readClass = notification.read ? '' : 'unread';
        const timeAgo = formatTimeAgo(notification.created_at);
        
        notificationsHtml += `
            <div class="notification-item ${readClass}" data-id="${notification.id}">
                <div class="notification-icon">
                    <i class="fas ${getNotificationIcon(notification.type)}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-text">${notification.message}</div>
                    <div class="notification-time">${timeAgo}</div>
                </div>
            </div>
        `;
    });
    
    notificationContainer.innerHTML = notificationsHtml;
    
    // Add click event to mark notifications as read
    document.querySelectorAll('.notification-item').forEach(item => {
        item.addEventListener('click', function() {
            const notificationId = this.getAttribute('data-id');
            markNotificationAsRead(notificationId);
            this.classList.remove('unread');
        });
    });
    
    // Mark all as read button
    document.querySelector('.mark-all-read').addEventListener('click', function(e) {
        e.preventDefault();
        markAllNotificationsAsRead();
    });
}

function getNotificationIcon(type) {
    switch (type) {
        case 'order':
            return 'fa-shopping-cart';
        case 'payment':
            return 'fa-credit-card';
        case 'commission':
            return 'fa-money-bill-alt';
        case 'system':
            return 'fa-cog';
        default:
            return 'fa-bell';
    }
}

function markNotificationAsRead(notificationId) {
    const apiUrl = '<?php echo relative_url("api/mark-notification-read.php"); ?>';
    
    fetch(apiUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${notificationId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update badge count
            const badge = document.getElementById('notificationBadge');
            const currentCount = parseInt(badge.textContent);
            if (currentCount > 1) {
                badge.textContent = currentCount - 1;
            } else {
                badge.style.display = 'none';
            }
        }
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
    });
}

function markAllNotificationsAsRead() {
    const apiUrl = '<?php echo relative_url("api/mark-all-notifications-read.php"); ?>';
    
    fetch(apiUrl, {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI
            document.querySelectorAll('.notification-item').forEach(item => {
                item.classList.remove('unread');
            });
            
            document.getElementById('notificationBadge').style.display = 'none';
        }
    })
    .catch(error => {
        console.error('Error marking all notifications as read:', error);
    });
}

function formatTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    if (seconds < 60) {
        return 'just now';
    }
    
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) {
        return `${minutes} minute${minutes !== 1 ? 's' : ''} ago`;
    }
    
    const hours = Math.floor(minutes / 60);
    if (hours < 24) {
        return `${hours} hour${hours !== 1 ? 's' : ''} ago`;
    }
    
    const days = Math.floor(hours / 24);
    if (days < 30) {
        return `${days} day${days !== 1 ? 's' : ''} ago`;
    }
    
    const months = Math.floor(days / 30);
    if (months < 12) {
        return `${months} month${months !== 1 ? 's' : ''} ago`;
    }
    
    const years = Math.floor(months / 12);
    return `${years} year${years !== 1 ? 's' : ''} ago`;
}
</script>