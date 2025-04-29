<?php
/**
 * Admin Integrations Management
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

// Set page title
$page_title = 'Integrations';

// Include admin header
require_once __DIR__ . '/../includes/admin_header.php';
?>

<!-- Admin Integrations Content -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Integration Status</h5>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-primary me-2" id="refreshStatusBtn">
                        <i class="fas fa-sync-alt"></i> Refresh Status
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Integration</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="integration-icon me-3">
                                            <i class="fas fa-globe fa-2x text-primary"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">GoHighLevel (GHL)</h6>
                                            <small class="text-muted">Customer management & orders</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div id="ghlStatus">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        Checking status...
                                    </div>
                                </td>
                                <td id="ghlLastUpdated">Checking...</td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-primary" id="configureGhlBtn">
                                            <i class="fas fa-cog"></i> Configure
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="testGhlBtn">
                                            <i class="fas fa-vial"></i> Test
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="integration-icon me-3">
                                            <i class="fas fa-columns fa-2x text-success"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">Pillars</h6>
                                            <small class="text-muted">Commission tracking & affiliate management</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div id="pillarsStatus">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        Checking status...
                                    </div>
                                </td>
                                <td id="pillarsLastUpdated">Checking...</td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-primary" id="configurePillarsBtn">
                                            <i class="fas fa-cog"></i> Configure
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="testPillarsBtn">
                                            <i class="fas fa-vial"></i> Test
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="integration-icon me-3">
                                            <i class="fas fa-plane fa-2x text-warning"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">RSI Travel</h6>
                                            <small class="text-muted">Travel products & bookings</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div id="rsiStatus">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        Checking status...
                                    </div>
                                </td>
                                <td id="rsiLastUpdated">Checking...</td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-primary" id="configureRsiBtn">
                                            <i class="fas fa-cog"></i> Configure
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="testRsiBtn">
                                            <i class="fas fa-vial"></i> Test
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sync History -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Sync History</h5>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="refreshSyncHistoryBtn">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped" id="syncHistoryTable">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Integration</th>
                                <th>Action</th>
                                <th>Status</th>
                                <th>Records</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody id="syncHistoryTableBody">
                            <tr>
                                <td colspan="6" class="text-center">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                    Loading sync history...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div id="syncHistoryPagination">
                            <!-- Pagination will be generated here -->
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="d-inline-block">
                            <select class="form-select form-select-sm" id="syncHistoryPerPage">
                                <option value="10">10 per page</option>
                                <option value="25">25 per page</option>
                                <option value="50">50 per page</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Configure GHL Modal -->
<div class="modal fade" id="configureGhlModal" tabindex="-1" aria-labelledby="configureGhlModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="configureGhlModalLabel">Configure GoHighLevel Integration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="configureGhlForm">
                    <div class="mb-3">
                        <label for="ghlApiKey" class="form-label">API Key</label>
                        <input type="text" class="form-control" id="ghlApiKey" name="api_key" required>
                        <div class="form-text">Enter your GoHighLevel API key.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="ghlLocationId" class="form-label">Location ID</label>
                        <input type="text" class="form-control" id="ghlLocationId" name="location_id" required>
                        <div class="form-text">Enter your GoHighLevel Location ID.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="ghlWebhookSecret" class="form-label">Webhook Secret</label>
                        <input type="text" class="form-control" id="ghlWebhookSecret" name="webhook_secret">
                        <div class="form-text">Optional: Enter the secret key used to verify incoming webhooks.</div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="ghlAutoSync" name="auto_sync" checked>
                            <label class="form-check-label" for="ghlAutoSync">Enable Auto-Sync</label>
                        </div>
                        <div class="form-text">Automatically sync new orders and customers from GoHighLevel.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveGhlConfigBtn">Save Configuration</button>
            </div>
        </div>
    </div>
</div>

<!-- Configure Pillars Modal -->
<div class="modal fade" id="configurePillarsModal" tabindex="-1" aria-labelledby="configurePillarsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="configurePillarsModalLabel">Configure Pillars Integration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="configurePillarsForm">
                    <div class="mb-3">
                        <label for="pillarsApiKey" class="form-label">API Key</label>
                        <input type="text" class="form-control" id="pillarsApiKey" name="api_key" required>
                        <div class="form-text">Enter your Pillars API key.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="pillarsOrgId" class="form-label">Organization ID</label>
                        <input type="text" class="form-control" id="pillarsOrgId" name="org_id" required>
                        <div class="form-text">Enter your Pillars Organization ID.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="pillarsWebhookSecret" class="form-label">Webhook Secret</label>
                        <input type="text" class="form-control" id="pillarsWebhookSecret" name="webhook_secret">
                        <div class="form-text">Optional: Enter the secret key used to verify incoming webhooks.</div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="pillarsAutoSync" name="auto_sync" checked>
                            <label class="form-check-label" for="pillarsAutoSync">Enable Auto-Sync</label>
                        </div>
                        <div class="form-text">Automatically sync commissions and affiliate data from Pillars.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="savePillarsConfigBtn">Save Configuration</button>
            </div>
        </div>
    </div>
</div>

<!-- Configure RSI Modal -->
<div class="modal fade" id="configureRsiModal" tabindex="-1" aria-labelledby="configureRsiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="configureRsiModalLabel">Configure RSI Travel Integration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="configureRsiForm">
                    <div class="mb-3">
                        <label for="rsiApiKey" class="form-label">API Key</label>
                        <input type="text" class="form-control" id="rsiApiKey" name="api_key" required>
                        <div class="form-text">Enter your RSI Travel API key.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="rsiAccountId" class="form-label">Account ID</label>
                        <input type="text" class="form-control" id="rsiAccountId" name="account_id" required>
                        <div class="form-text">Enter your RSI Travel Account ID.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="rsiEndpoint" class="form-label">API Endpoint</label>
                        <input type="url" class="form-control" id="rsiEndpoint" name="endpoint" required>
                        <div class="form-text">Enter the RSI Travel API endpoint URL.</div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="rsiAutoSync" name="auto_sync" checked>
                            <label class="form-check-label" for="rsiAutoSync">Enable Auto-Sync</label>
                        </div>
                        <div class="form-text">Automatically sync travel products and bookings from RSI Travel.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveRsiConfigBtn">Save Configuration</button>
            </div>
        </div>
    </div>
</div>

<!-- Sync Details Modal -->
<div class="modal fade" id="syncDetailsModal" tabindex="-1" aria-labelledby="syncDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="syncDetailsModalLabel">Sync Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="syncDetailsContent">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading sync details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let itemsPerPage = 10;
let syncHistoryItems = [];

document.addEventListener('DOMContentLoaded', function() {
    // Load integration status
    checkIntegrationStatus();
    
    // Load sync history
    loadSyncHistory();
    
    // Refresh status button
    document.getElementById('refreshStatusBtn').addEventListener('click', function() {
        checkIntegrationStatus();
    });
    
    // Refresh sync history button
    document.getElementById('refreshSyncHistoryBtn').addEventListener('click', function() {
        loadSyncHistory();
    });
    
    // Configure GHL button
    document.getElementById('configureGhlBtn').addEventListener('click', function() {
        loadGhlConfig();
        const configureGhlModal = new bootstrap.Modal(document.getElementById('configureGhlModal'));
        configureGhlModal.show();
    });
    
    // Configure Pillars button
    document.getElementById('configurePillarsBtn').addEventListener('click', function() {
        loadPillarsConfig();
        const configurePillarsModal = new bootstrap.Modal(document.getElementById('configurePillarsModal'));
        configurePillarsModal.show();
    });
    
    // Configure RSI button
    document.getElementById('configureRsiBtn').addEventListener('click', function() {
        loadRsiConfig();
        const configureRsiModal = new bootstrap.Modal(document.getElementById('configureRsiModal'));
        configureRsiModal.show();
    });
    
    // Test GHL button
    document.getElementById('testGhlBtn').addEventListener('click', function() {
        testIntegration('ghl');
    });
    
    // Test Pillars button
    document.getElementById('testPillarsBtn').addEventListener('click', function() {
        testIntegration('pillars');
    });
    
    // Test RSI button
    document.getElementById('testRsiBtn').addEventListener('click', function() {
        testIntegration('rsi');
    });
    
    // Save GHL config button
    document.getElementById('saveGhlConfigBtn').addEventListener('click', function() {
        saveIntegrationConfig('ghl', 'configureGhlForm', 'configureGhlModal');
    });
    
    // Save Pillars config button
    document.getElementById('savePillarsConfigBtn').addEventListener('click', function() {
        saveIntegrationConfig('pillars', 'configurePillarsForm', 'configurePillarsModal');
    });
    
    // Save RSI config button
    document.getElementById('saveRsiConfigBtn').addEventListener('click', function() {
        saveIntegrationConfig('rsi', 'configureRsiForm', 'configureRsiModal');
    });
    
    // Sync history items per page change
    document.getElementById('syncHistoryPerPage').addEventListener('change', function() {
        itemsPerPage = parseInt(this.value);
        currentPage = 1;
        displaySyncHistory(syncHistoryItems);
    });
});

function checkIntegrationStatus() {
    // Update GHL status
    updateIntegrationStatus('ghl');
    
    // Update Pillars status
    updateIntegrationStatus('pillars');
    
    // Update RSI status
    updateIntegrationStatus('rsi');
}

function updateIntegrationStatus(integration) {
    const statusElement = document.getElementById(`${integration}Status`);
    const lastUpdatedElement = document.getElementById(`${integration}LastUpdated`);
    
    statusElement.innerHTML = `
        <div class="spinner-border spinner-border-sm text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        Checking status...
    `;
    
    lastUpdatedElement.textContent = 'Checking...';
    
    fetch(`/api/check-integration.php?integration=${integration}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.connected) {
                    statusElement.innerHTML = '<span class="badge bg-success">Connected</span>';
                } else {
                    statusElement.innerHTML = '<span class="badge bg-danger">Disconnected</span>';
                }
                
                if (data.last_updated) {
                    const lastUpdated = new Date(data.last_updated);
                    lastUpdatedElement.textContent = lastUpdated.toLocaleString();
                } else {
                    lastUpdatedElement.textContent = 'Never';
                }
            } else {
                statusElement.innerHTML = '<span class="badge bg-warning">Unknown</span>';
                lastUpdatedElement.textContent = 'Unknown';
                console.error(`Error checking ${integration} status:`, data.error);
            }
        })
        .catch(error => {
            statusElement.innerHTML = '<span class="badge bg-danger">Error</span>';
            lastUpdatedElement.textContent = 'Error';
            console.error(`Error checking ${integration} status:`, error);
        });
}

function loadSyncHistory() {
    const tableBody = document.getElementById('syncHistoryTableBody');
    
    tableBody.innerHTML = `
        <tr>
            <td colspan="6" class="text-center">
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                Loading sync history...
            </td>
        </tr>
    `;
    
    fetch('/api/admin-sync-history.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                syncHistoryItems = data.history;
                displaySyncHistory(syncHistoryItems);
            } else {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center text-danger">
                            Error loading sync history: ${data.error}
                        </td>
                    </tr>
                `;
                console.error('Error loading sync history:', data.error);
            }
        })
        .catch(error => {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-danger">
                        Failed to load sync history. Please try again.
                    </td>
                </tr>
            `;
            console.error('Error:', error);
        });
}

function displaySyncHistory(items) {
    const tableBody = document.getElementById('syncHistoryTableBody');
    
    if (items.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center">
                    No sync history available.
                </td>
            </tr>
        `;
        return;
    }
    
    // Calculate pagination
    const totalPages = Math.ceil(items.length / itemsPerPage);
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = Math.min(startIndex + itemsPerPage, items.length);
    const currentItems = items.slice(startIndex, endIndex);
    
    // Generate table rows
    let html = '';
    
    currentItems.forEach(item => {
        const datetime = new Date(item.created_at).toLocaleString();
        
        let statusBadge = '';
        switch (item.status.toLowerCase()) {
            case 'success':
                statusBadge = '<span class="badge bg-success">Success</span>';
                break;
            case 'error':
                statusBadge = '<span class="badge bg-danger">Error</span>';
                break;
            case 'partial':
                statusBadge = '<span class="badge bg-warning">Partial</span>';
                break;
            default:
                statusBadge = `<span class="badge bg-secondary">${item.status}</span>`;
        }
        
        html += `
            <tr>
                <td>${datetime}</td>
                <td>${item.integration}</td>
                <td>${item.action}</td>
                <td>${statusBadge}</td>
                <td>${item.records_processed}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary view-sync-details" data-sync-id="${item.id}">
                        <i class="fas fa-eye"></i> View Details
                    </button>
                </td>
            </tr>
        `;
    });
    
    tableBody.innerHTML = html;
    
    // Generate pagination
    generatePagination(totalPages);
    
    // Add event listeners to view details buttons
    document.querySelectorAll('.view-sync-details').forEach(button => {
        button.addEventListener('click', function() {
            const syncId = this.getAttribute('data-sync-id');
            viewSyncDetails(syncId);
        });
    });
}

function generatePagination(totalPages) {
    const paginationContainer = document.getElementById('syncHistoryPagination');
    
    if (totalPages <= 1) {
        paginationContainer.innerHTML = '';
        return;
    }
    
    let html = '<nav aria-label="Sync history pagination"><ul class="pagination">';
    
    // Previous button
    html += `
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">
                <span aria-hidden="true">&laquo;</span>
            </a>
        </li>
    `;
    
    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        html += `
            <li class="page-item ${currentPage === i ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>
        `;
    }
    
    // Next button
    html += `
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Next">
                <span aria-hidden="true">&raquo;</span>
            </a>
        </li>
    `;
    
    html += '</ul></nav>';
    
    paginationContainer.innerHTML = html;
    
    // Add event listeners to pagination links
    document.querySelectorAll('.pagination .page-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (this.parentElement.classList.contains('disabled')) {
                return;
            }
            
            const page = parseInt(this.getAttribute('data-page'));
            if (page > 0 && page <= totalPages) {
                currentPage = page;
                displaySyncHistory(syncHistoryItems);
            }
        });
    });
}

function viewSyncDetails(syncId) {
    const syncDetailsContent = document.getElementById('syncDetailsContent');
    
    syncDetailsContent.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading sync details...</p>
        </div>
    `;
    
    // Show modal
    const syncDetailsModal = new bootstrap.Modal(document.getElementById('syncDetailsModal'));
    syncDetailsModal.show();
    
    // Find sync item in cached data
    const syncItem = syncHistoryItems.find(item => item.id == syncId);
    
    if (syncItem) {
        displaySyncDetails(syncItem);
    } else {
        // Fetch sync details
        fetch(`/api/admin-sync-details.php?id=${syncId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displaySyncDetails(data.sync);
                } else {
                    syncDetailsContent.innerHTML = `
                        <div class="alert alert-danger">
                            Error loading sync details: ${data.error}
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                syncDetailsContent.innerHTML = `
                    <div class="alert alert-danger">
                        Failed to load sync details. Please try again.
                    </div>
                `;
            });
    }
}

function displaySyncDetails(syncItem) {
    const syncDetailsContent = document.getElementById('syncDetailsContent');
    const datetime = new Date(syncItem.created_at).toLocaleString();
    
    let statusClass = '';
    switch (syncItem.status.toLowerCase()) {
        case 'success':
            statusClass = 'success';
            break;
        case 'error':
            statusClass = 'danger';
            break;
        case 'partial':
            statusClass = 'warning';
            break;
        default:
            statusClass = 'secondary';
    }
    
    let detailsHtml = '';
    
    if (syncItem.details) {
        try {
            const details = JSON.parse(syncItem.details);
            
            if (details.errors && details.errors.length > 0) {
                detailsHtml += '<h6 class="mt-3">Errors</h6>';
                detailsHtml += '<ul class="text-danger">';
                
                details.errors.forEach(error => {
                    detailsHtml += `<li>${error}</li>`;
                });
                
                detailsHtml += '</ul>';
            }
            
            if (details.warnings && details.warnings.length > 0) {
                detailsHtml += '<h6 class="mt-3">Warnings</h6>';
                detailsHtml += '<ul class="text-warning">';
                
                details.warnings.forEach(warning => {
                    detailsHtml += `<li>${warning}</li>`;
                });
                
                detailsHtml += '</ul>';
            }
            
            if (details.items && details.items.length > 0) {
                detailsHtml += '<h6 class="mt-3">Processed Items</h6>';
                detailsHtml += '<ul>';
                
                details.items.forEach(item => {
                    detailsHtml += `<li>${item}</li>`;
                });
                
                detailsHtml += '</ul>';
            }
        } catch (e) {
            detailsHtml = `<pre class="mt-3">${syncItem.details}</pre>`;
        }
    }
    
    syncDetailsContent.innerHTML = `
        <div class="card mb-3 border-${statusClass}">
            <div class="card-header bg-${statusClass} text-white">
                <strong>${syncItem.integration} - ${syncItem.action}</strong>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Date & Time:</strong> ${datetime}</p>
                        <p><strong>Status:</strong> ${syncItem.status}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Records Processed:</strong> ${syncItem.records_processed}</p>
                        <p><strong>Duration:</strong> ${syncItem.duration_seconds} seconds</p>
                    </div>
                </div>
                
                <h6 class="mt-3">Summary</h6>
                <p>${syncItem.summary || 'No summary available.'}</p>
                
                ${detailsHtml}
            </div>
        </div>
    `;
}

function loadGhlConfig() {
    const apiKeyInput = document.getElementById('ghlApiKey');
    const locationIdInput = document.getElementById('ghlLocationId');
    const webhookSecretInput = document.getElementById('ghlWebhookSecret');
    const autoSyncCheckbox = document.getElementById('ghlAutoSync');
    
    // Show loading state
    apiKeyInput.disabled = true;
    locationIdInput.disabled = true;
    webhookSecretInput.disabled = true;
    autoSyncCheckbox.disabled = true;
    
    fetch('/api/get-integration-config.php?integration=ghl')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                apiKeyInput.value = data.config.api_key || '';
                locationIdInput.value = data.config.location_id || '';
                webhookSecretInput.value = data.config.webhook_secret || '';
                autoSyncCheckbox.checked = data.config.auto_sync !== false;
            } else {
                console.error('Error loading GHL config:', data.error);
                showAlert('danger', `Error loading GHL configuration: ${data.error}`);
            }
            
            // Remove loading state
            apiKeyInput.disabled = false;
            locationIdInput.disabled = false;
            webhookSecretInput.disabled = false;
            autoSyncCheckbox.disabled = false;
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'Failed to load GHL configuration. Please try again.');
            
            // Remove loading state
            apiKeyInput.disabled = false;
            locationIdInput.disabled = false;
            webhookSecretInput.disabled = false;
            autoSyncCheckbox.disabled = false;
        });
}

function loadPillarsConfig() {
    const apiKeyInput = document.getElementById('pillarsApiKey');
    const orgIdInput = document.getElementById('pillarsOrgId');
    const webhookSecretInput = document.getElementById('pillarsWebhookSecret');
    const autoSyncCheckbox = document.getElementById('pillarsAutoSync');
    
    // Show loading state
    apiKeyInput.disabled = true;
    orgIdInput.disabled = true;
    webhookSecretInput.disabled = true;
    autoSyncCheckbox.disabled = true;
    
    fetch('/api/get-integration-config.php?integration=pillars')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                apiKeyInput.value = data.config.api_key || '';
                orgIdInput.value = data.config.org_id || '';
                webhookSecretInput.value = data.config.webhook_secret || '';
                autoSyncCheckbox.checked = data.config.auto_sync !== false;
            } else {
                console.error('Error loading Pillars config:', data.error);
                showAlert('danger', `Error loading Pillars configuration: ${data.error}`);
            }
            
            // Remove loading state
            apiKeyInput.disabled = false;
            orgIdInput.disabled = false;
            webhookSecretInput.disabled = false;
            autoSyncCheckbox.disabled = false;
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'Failed to load Pillars configuration. Please try again.');
            
            // Remove loading state
            apiKeyInput.disabled = false;
            orgIdInput.disabled = false;
            webhookSecretInput.disabled = false;
            autoSyncCheckbox.disabled = false;
        });
}

function loadRsiConfig() {
    const apiKeyInput = document.getElementById('rsiApiKey');
    const accountIdInput = document.getElementById('rsiAccountId');
    const endpointInput = document.getElementById('rsiEndpoint');
    const autoSyncCheckbox = document.getElementById('rsiAutoSync');
    
    // Show loading state
    apiKeyInput.disabled = true;
    accountIdInput.disabled = true;
    endpointInput.disabled = true;
    autoSyncCheckbox.disabled = true;
    
    fetch('/api/get-integration-config.php?integration=rsi')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                apiKeyInput.value = data.config.api_key || '';
                accountIdInput.value = data.config.account_id || '';
                endpointInput.value = data.config.endpoint || '';
                autoSyncCheckbox.checked = data.config.auto_sync !== false;
            } else {
                console.error('Error loading RSI config:', data.error);
                showAlert('danger', `Error loading RSI configuration: ${data.error}`);
            }
            
            // Remove loading state
            apiKeyInput.disabled = false;
            accountIdInput.disabled = false;
            endpointInput.disabled = false;
            autoSyncCheckbox.disabled = false;
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'Failed to load RSI configuration. Please try again.');
            
            // Remove loading state
            apiKeyInput.disabled = false;
            accountIdInput.disabled = false;
            endpointInput.disabled = false;
            autoSyncCheckbox.disabled = false;
        });
}

function saveIntegrationConfig(integration, formId, modalId) {
    const form = document.getElementById(formId);
    const formData = new FormData(form);
    
    // Convert form data to object
    const configData = {};
    formData.forEach((value, key) => {
        if (key === 'auto_sync') {
            configData[key] = value === 'on';
        } else {
            configData[key] = value;
        }
    });
    
    // Save configuration
    fetch('/api/save-integration-config.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            integration: integration,
            config: configData
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById(modalId)).hide();
            
            // Show success message
            showAlert('success', `${integration.toUpperCase()} configuration saved successfully!`);
            
            // Update integration status
            updateIntegrationStatus(integration);
        } else {
            showAlert('danger', `Error saving ${integration.toUpperCase()} configuration: ${data.error}`);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', `Failed to save ${integration.toUpperCase()} configuration. Please try again.`);
    });
}

function testIntegration(integration) {
    const statusElement = document.getElementById(`${integration}Status`);
    
    // Show testing status
    statusElement.innerHTML = `
        <div class="spinner-border spinner-border-sm text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        Testing...
    `;
    
    fetch(`/api/test-integration.php?integration=${integration}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                statusElement.innerHTML = '<span class="badge bg-success">Connected</span>';
                showAlert('success', `${integration.toUpperCase()} integration test successful!`);
            } else {
                statusElement.innerHTML = '<span class="badge bg-danger">Failed</span>';
                showAlert('danger', `${integration.toUpperCase()} integration test failed: ${data.error}`);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            statusElement.innerHTML = '<span class="badge bg-danger">Error</span>';
            showAlert('danger', `Failed to test ${integration.toUpperCase()} integration. Please try again.`);
        });
}
</script>

<?php
// Include admin footer
require_once __DIR__ . '/../includes/admin_footer.php';
?>