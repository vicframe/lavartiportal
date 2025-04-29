/**
 * Affiliate Dashboard JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTables for commission history
    const commissionsTable = document.getElementById('commissionsTable');
    if (commissionsTable) {
        $(commissionsTable).DataTable({
            order: [[0, 'desc']], // Sort by date descending
            responsive: true,
            pageLength: 10,
            language: {
                emptyTable: "No commissions found",
                zeroRecords: "No matching commissions found"
            }
        });
    }
    
    // Handle copy affiliate link button
    const copyAffiliateLink = document.getElementById('copyAffiliateLink');
    if (copyAffiliateLink) {
        copyAffiliateLink.addEventListener('click', function() {
            const affiliateLinkInput = document.getElementById('affiliateLink');
            if (affiliateLinkInput) {
                affiliateLinkInput.select();
                document.execCommand('copy');
                
                // Show success message
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check"></i> Copied!';
                this.classList.remove('btn-outline-primary');
                this.classList.add('btn-success');
                
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.classList.remove('btn-success');
                    this.classList.add('btn-outline-primary');
                }, 2000);
            }
        });
    }
    
    // Initialize commission charts
    initializeCommissionCharts();
    
    // Handle marketing materials download buttons
    const downloadButtons = document.querySelectorAll('.download-material');
    downloadButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const materialId = this.getAttribute('data-material-id');
            const materialType = this.getAttribute('data-material-type');
            
            // In a real implementation, this would trigger a download or redirect to a file
            console.log(`Downloading ${materialType} with ID ${materialId}`);
            
            // Show success message
            alert(`The ${materialType} would be downloaded in a real implementation.`);
        });
    });
    
    // Handle social sharing buttons
    const shareButtons = document.querySelectorAll('.share-button');
    shareButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const platform = this.getAttribute('data-platform');
            const affiliateLink = document.getElementById('affiliateLink').value;
            
            let shareUrl = '';
            
            switch(platform) {
                case 'facebook':
                    shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(affiliateLink)}`;
                    break;
                case 'twitter':
                    shareUrl = `https://twitter.com/intent/tweet?url=${encodeURIComponent(affiliateLink)}&text=${encodeURIComponent('Check out LaVarti Systems!')}`;
                    break;
                case 'linkedin':
                    shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(affiliateLink)}`;
                    break;
                case 'email':
                    shareUrl = `mailto:?subject=${encodeURIComponent('Check out LaVarti Systems')}&body=${encodeURIComponent('I thought you might be interested in this: ' + affiliateLink)}`;
                    break;
                default:
                    break;
            }
            
            if (shareUrl) {
                window.open(shareUrl, '_blank');
            }
        });
    });
    
    // Handle affiliate tip expansion
    const readMoreButtons = document.querySelectorAll('.read-more-tip');
    readMoreButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const tipId = this.getAttribute('data-tip-id');
            const fullTipContent = document.getElementById(`full-tip-${tipId}`);
            const shortTipContent = document.getElementById(`short-tip-${tipId}`);
            
            if (fullTipContent && shortTipContent) {
                if (fullTipContent.style.display === 'none') {
                    fullTipContent.style.display = 'block';
                    shortTipContent.style.display = 'none';
                    this.textContent = 'Read Less';
                } else {
                    fullTipContent.style.display = 'none';
                    shortTipContent.style.display = 'block';
                    this.textContent = 'Read More';
                }
            }
        });
    });
});

// Initialize commission charts
function initializeCommissionCharts() {
    const earningsChartCanvas = document.getElementById('earningsChart');
    if (!earningsChartCanvas) return;
    
    // This would be populated with real data from the server in production
    // For demo purposes, we'll use sample data
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    const commissionData = [0, 0, 0, 0, 0, 0]; // This would be real data in production
    
    // Chart configuration (this would use Chart.js in production)
    const chartConfig = {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                label: 'Commissions',
                data: commissionData,
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                borderColor: 'rgb(13, 110, 253)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value;
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '$' + context.parsed.y;
                        }
                    }
                }
            }
        }
    };
    
    // In production, this would initialize a Chart.js chart
    // const earningsChart = new Chart(earningsChartCanvas, chartConfig);
    
    // Referral breakdown chart
    const referralChartCanvas = document.getElementById('referralChart');
    if (!referralChartCanvas) return;
    
    // Sample data for demo - would be real in production
    const referralData = {
        labels: ['Direct', 'Tier 1', 'Tier 2'],
        datasets: [{
            label: 'Referrals',
            data: [0, 0, 0], // This would be real data in production
            backgroundColor: [
                'rgba(13, 110, 253, 0.7)',
                'rgba(25, 135, 84, 0.7)',
                'rgba(255, 193, 7, 0.7)'
            ],
            borderColor: [
                'rgb(13, 110, 253)',
                'rgb(25, 135, 84)',
                'rgb(255, 193, 7)'
            ],
            borderWidth: 1
        }]
    };
    
    const referralChartConfig = {
        type: 'doughnut',
        data: referralData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    };
    
    // In production, this would initialize a Chart.js chart
    // const referralChart = new Chart(referralChartCanvas, referralChartConfig);
}

// Function to load referral data
function loadReferralData() {
    // In production, this would fetch data from the server
    fetch('/api/referral-data.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update DOM elements with the returned data
                document.getElementById('directReferrals').textContent = data.directReferrals;
                document.getElementById('tier1Referrals').textContent = data.tier1Referrals;
                document.getElementById('tier2Referrals').textContent = data.tier2Referrals;
                document.getElementById('totalReferrals').textContent = data.totalReferrals;
                
                // Update charts with new data
                // referralChart.data.datasets[0].data = [data.directReferrals, data.tier1Referrals, data.tier2Referrals];
                // referralChart.update();
            }
        })
        .catch(error => {
            console.error('Error loading referral data:', error);
        });
}

// Function to load commission data
function loadCommissionData(timeframe = 'monthly') {
    // Show loading state
    const statsContainer = document.getElementById('commissionStats');
    if (statsContainer) {
        statsContainer.innerHTML = `
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 mb-0">Loading commission data...</p>
            </div>
        `;
    }
    
    // In production, this would fetch data from the server
    fetch(`/api/commission-data.php?timeframe=${timeframe}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update DOM elements with the returned data
                document.getElementById('totalCommissions').textContent = formatCurrency(data.totalAmount);
                document.getElementById('pendingCommissions').textContent = formatCurrency(data.pendingAmount);
                document.getElementById('paidCommissions').textContent = formatCurrency(data.paidAmount);
                
                // Update chart with new data
                // earningsChart.data.labels = data.labels;
                // earningsChart.data.datasets[0].data = data.values;
                // earningsChart.update();
                
                // Update stats container
                statsContainer.innerHTML = `
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Earnings</h5>
                                    <h2 class="mb-0">${formatCurrency(data.totalAmount)}</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Pending</h5>
                                    <h2 class="mb-0">${formatCurrency(data.pendingAmount)}</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Paid</h5>
                                    <h2 class="mb-0">${formatCurrency(data.paidAmount)}</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading commission data:', error);
            if (statsContainer) {
                statsContainer.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error loading commission data. Please try again later.
                    </div>
                `;
            }
        });
}
