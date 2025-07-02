<?php
$page_title = 'Affiliate';

// Include all necessary files
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Require login to access this page
require_login();

// Get user data
$user = get_current_logged_user();

// Generate Affiliate Link
//$affiliate_link = APP_URL . '/?ref=' . ($user['replicated_site'] ?? $user['id']);
$affiliate_link = AFFILIATE_URL . '/?ref=' . ($user['replicated_site'] ?? $user['id']);
echo "<pre>Debug: Affiliate link = " . $affiliate_link . "</pre>";

// Set custom styles for the dashboard
$custom_css = '<link href="/assets/css/dashboard.css" rel="stylesheet">';
require_once __DIR__ . '/../includes/dashboard_header.php';
?>

<div class="dashboard-container">
    <!-- Sidebar -->
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    
    <!-- Main content -->
    <div class="main-content">
        <!-- Top navigation -->
        <?php include __DIR__ . '/partials/topnav.php'; ?>
        
        <div class="container-fluid px-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="dashboard-title">Affiliate Program</h1>
            </div>
            
            <!-- Affiliate Overview -->
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="card stats-card text-center">
                        <div class="card-body">
                            <h2 class="stats-number">$145.50</h2>
                            <p class="stats-text">Total Earnings</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card stats-card text-center">
                        <div class="card-body">
                            <h2 class="stats-number">$100.00</h2>
                            <p class="stats-text">Pending Commissions</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card stats-card text-center">
                        <div class="card-body">
                            <h2 class="stats-number">3</h2>
                            <p class="stats-text">Total Referrals</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card stats-card text-center">
                        <div class="card-body">
                            <h2 class="stats-number">2</h2>
                            <p class="stats-text">Active Referrals</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Affiliate Link -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Your Affiliate Link</h5>
                </div>
                <div class="card-body">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="affiliateLink" value="<?php echo htmlspecialchars($affiliate_link); ?>" readonly>
                        <button class="btn btn-primary" type="button" id="copyLinkBtn">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                    </div>
                    <div class="mt-3">
                        <p>Share this link with others. When they sign up or make a purchase, you'll earn commission!</p>
                        <div class="social-share mt-3">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($affiliate_link); ?>" class="btn btn-outline-primary me-2" target="_blank">
                                <i class="fab fa-facebook-f"></i> Share on Facebook
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($affiliate_link); ?>&text=Check out this amazing travel program!" class="btn btn-outline-info me-2" target="_blank">
                                <i class="fab fa-twitter"></i> Share on Twitter
                            </a>
                            <a href="mailto:?subject=Check out this travel program&body=I thought you might be interested in this: <?php echo $affiliate_link; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-envelope"></i> Share via Email
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Commission Chart -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Earnings Overview</h5>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-primary active" data-timeframe="monthly">Monthly</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-timeframe="weekly">Weekly</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-timeframe="daily">Daily</button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="earningsChart" height="250"></canvas>
                </div>
            </div>
            
            <!-- Referrals Table -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Your Referrals</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Join Date</th>
                                    <th>Status</th>
                                    <th>Tier</th>
                                    <th>Commissions Generated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Sample data - would be populated from database -->
                                <tr>
                                    <td>John Doe</td>
                                    <td>April 2, 2025</td>
                                    <td><span class="badge bg-success">Active</span></td>
                                    <td>Premium</td>
                                    <td>$32.50</td>
                                </tr>
                                <tr>
                                    <td>Jane Smith</td>
                                    <td>April 10, 2025</td>
                                    <td><span class="badge bg-success">Active</span></td>
                                    <td>Basic</td>
                                    <td>$13.00</td>
                                </tr>
                                <tr>
                                    <td>Robert Johnson</td>
                                    <td>April 15, 2025</td>
                                    <td><span class="badge bg-warning text-dark">Pending</span></td>
                                    <td>Elite</td>
                                    <td>$100.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Commissions Table -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Commission History</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Sample data - would be populated from database -->
                                <tr>
                                    <td>April 20, 2025</td>
                                    <td>Commission from Jane Smith</td>
                                    <td><span class="badge bg-success">Paid</span></td>
                                    <td>$13.00</td>
                                </tr>
                                <tr>
                                    <td>April 15, 2025</td>
                                    <td>Commission from Robert Johnson</td>
                                    <td><span class="badge bg-warning text-dark">Pending</span></td>
                                    <td>$100.00</td>
                                </tr>
                                <tr>
                                    <td>April 5, 2025</td>
                                    <td>Commission from John Doe</td>
                                    <td><span class="badge bg-success">Paid</span></td>
                                    <td>$32.50</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Copy affiliate link functionality
    const copyLinkBtn = document.getElementById('copyLinkBtn');
    const affiliateLink = document.getElementById('affiliateLink');
    
    if (copyLinkBtn && affiliateLink) {
        copyLinkBtn.addEventListener('click', function() {
            affiliateLink.select();
            document.execCommand('copy');
            
            // Show copied feedback
            const originalText = copyLinkBtn.innerHTML;
            copyLinkBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
            
            setTimeout(function() {
                copyLinkBtn.innerHTML = originalText;
            }, 2000);
        });
    }
    
    // Initialize chart
    const ctx = document.getElementById('earningsChart');
    if (ctx) {
        const earningsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Commission Earnings ($)',
                    data: [0, 25, 45, 75, 100, 145.50],
                    borderColor: '#8e44ad',
                    backgroundColor: 'rgba(142, 68, 173, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '$' + context.raw;
                            }
                        }
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
        
        // Handle timeframe buttons
        const timeframeButtons = document.querySelectorAll('[data-timeframe]');
        
        timeframeButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                timeframeButtons.forEach(btn => btn.classList.remove('active'));
                
                // Add active class to clicked button
                this.classList.add('active');
                
                // Get timeframe
                const timeframe = this.getAttribute('data-timeframe');
                
                // Update chart based on timeframe (mock data for demonstration)
                let labels, data;
                
                if (timeframe === 'daily') {
                    labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                    data = [5, 12, 8, 15, 20, 10, 25];
                } else if (timeframe === 'weekly') {
                    labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
                    data = [25, 45, 35, 50];
                } else { // monthly
                    labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
                    data = [0, 25, 45, 75, 100, 145.50];
                }
                
                earningsChart.data.labels = labels;
                earningsChart.data.datasets[0].data = data;
                earningsChart.update();
            });
        });
    }
});
</script>

<?php
// Include dashboard footer
require_once __DIR__ . '/../includes/dashboard_footer.php';
?>