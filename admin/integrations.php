<?php
/**
 * Admin Integrations Page
 * 
 * Allows administrators to view and manage third-party integrations.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

// Require login
if (!is_logged_in()) {
    header('Location: ../login.php');
    exit;
}

$user = get_current_logged_user();

// Check if user is admin
if (!isset($user['is_admin']) || !$user['is_admin']) {
    header('Location: ../dashboard');
    exit;
}

// Include header
include '../includes/admin_header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <h4 class="page-title">Integrations</h4>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Active Integrations</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- GHL Integration -->
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header bg-primary text-white">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="card-title mb-0">GoHighLevel (GHL)</h5>
                                            <span class="badge bg-light text-dark" id="ghl-status">Checking...</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p>GoHighLevel integration for order management and customer data synchronization.</p>
                                        <div class="d-flex justify-content-between">
                                            <button class="btn btn-info" onclick="window.location.href='../webhook_setup.php'">Configure</button>
                                            <button class="btn btn-secondary" id="check-ghl-btn" onclick="checkIntegration('ghl')">Check Status</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pillars Integration -->
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header bg-info text-white">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="card-title mb-0">Pillars</h5>
                                            <span class="badge bg-light text-dark" id="pillars-status">Checking...</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p>Pillars integration for affiliate management and commission tracking.</p>
                                        <div class="d-flex justify-content-between">
                                            <button class="btn btn-info" onclick="window.location.href='../webhook_setup.php'">Configure</button>
                                            <button class="btn btn-secondary" id="check-pillars-btn" onclick="checkIntegration('pillars')">Check Status</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Recent Synchronizations</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered" id="sync-history-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Integration</th>
                                        <th>Action</th>
                                        <th>Status</th>
                                        <th>Records</th>
                                        <th>Duration</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="6" class="text-center">Loading sync history...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check integration status on page load
    checkIntegration('ghl');
    checkIntegration('pillars');
    
    // Load sync history
    loadSyncHistory();
});

function checkIntegration(integration) {
    const statusElement = document.getElementById(`${integration}-status`);
    const checkButton = document.getElementById(`check-${integration}-btn`);
    
    if (statusElement) {
        statusElement.textContent = 'Checking...';
        statusElement.className = 'badge bg-warning text-dark';
    }
    
    if (checkButton) {
        checkButton.disabled = true;
    }
    
    fetch(`../api/check-integration.php?integration=${integration}`)
        .then(response => response.json())
        .then(data => {
            if (statusElement) {
                if (data.connected) {
                    statusElement.textContent = 'Connected';
                    statusElement.className = 'badge bg-success';
                } else {
                    statusElement.textContent = 'Not Connected';
                    statusElement.className = 'badge bg-danger';
                }
            }
            
            if (checkButton) {
                checkButton.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error checking integration:', error);
            
            if (statusElement) {
                statusElement.textContent = 'Error';
                statusElement.className = 'badge bg-danger';
            }
            
            if (checkButton) {
                checkButton.disabled = false;
            }
        });
}

function loadSyncHistory() {
    const tableBody = document.querySelector('#sync-history-table tbody');
    
    fetch('../api/admin-sync-history.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.history.length > 0) {
                let html = '';
                
                data.history.forEach(item => {
                    const statusClass = item.status === 'success' ? 'bg-success' : (item.status === 'error' ? 'bg-danger' : 'bg-warning');
                    const date = new Date(item.created_at).toLocaleString();
                    
                    html += `
                        <tr>
                            <td>${date}</td>
                            <td>${item.integration}</td>
                            <td>${item.action}</td>
                            <td><span class="badge ${statusClass}">${item.status}</span></td>
                            <td>${item.records_processed}</td>
                            <td>${item.duration_seconds}s</td>
                        </tr>
                    `;
                });
                
                tableBody.innerHTML = html;
            } else {
                tableBody.innerHTML = '<tr><td colspan="6" class="text-center">No sync history available</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error loading sync history:', error);
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center">Error loading sync history</td></tr>';
        });
}
</script>

<?php include '../includes/admin_footer.php'; ?>