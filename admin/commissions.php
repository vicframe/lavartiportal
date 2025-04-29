<?php
/**
 * Admin Commission Management
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

// Set page title
$page_title = 'Commission Management';

// Include admin header
require_once __DIR__ . '/../includes/admin_header.php';
?>

<!-- Admin Commissions Content -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">All Commissions</h5>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-primary me-2" id="refreshCommissionsBtn">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                    <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                        Export
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" id="exportCSV">CSV</a></li>
                        <li><a class="dropdown-item" href="#" id="exportPDF">PDF</a></li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <div class="commission-filters mb-4">
                    <div class="row">
                        <div class="col-md-9">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-primary active" data-filter="all">All</button>
                                <button type="button" class="btn btn-outline-primary" data-filter="pending">Pending</button>
                                <button type="button" class="btn btn-outline-primary" data-filter="approved">Approved</button>
                                <button type="button" class="btn btn-outline-primary" data-filter="paid">Paid</button>
                                <button type="button" class="btn btn-outline-primary" data-filter="declined">Declined</button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="input-group">
                                <input type="text" class="form-control" id="commissionSearch" placeholder="Search commissions...">
                                <button class="btn btn-outline-primary" type="button" id="searchBtn">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle" id="commissionsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Affiliate</th>
                                <th>Type</th>
                                <th>Order</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="commissionsTableBody">
                            <tr>
                                <td colspan="8" class="text-center">Loading commissions...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div id="commissionsPagination">
                            <!-- Pagination will be generated here -->
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="d-inline-block">
                            <select class="form-select form-select-sm" id="commissionsPerPage">
                                <option value="10">10 per page</option>
                                <option value="25">25 per page</option>
                                <option value="50">50 per page</option>
                                <option value="100">100 per page</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Commission Stats -->
<div class="row">
    <div class="col-md-3 mb-4">
        <div class="stats-card">
            <div class="icon">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <h3 id="totalCommissions">--</h3>
            <p>Total Commissions</p>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="stats-card">
            <div class="icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <h3 id="totalPaid">--</h3>
            <p>Total Paid</p>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="stats-card">
            <div class="icon">
                <i class="fas fa-clock"></i>
            </div>
            <h3 id="pendingCommissions">--</h3>
            <p>Pending Commissions</p>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="stats-card">
            <div class="icon">
                <i class="fas fa-user-friends"></i>
            </div>
            <h3 id="activeAffiliates">--</h3>
            <p>Active Affiliates</p>
        </div>
    </div>
</div>

<!-- Commission Distribution Chart -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Commission Distribution</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="card bg-light h-100">
                            <div class="card-body">
                                <h5 class="card-title">Direct Commissions</h5>
                                <h2 class="mt-3 mb-3" id="directCommissions">--</h2>
                                <div class="progress">
                                    <div class="progress-bar bg-primary" id="directProgress" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card bg-light h-100">
                            <div class="card-body">
                                <h5 class="card-title">Override Commissions</h5>
                                <h2 class="mt-3 mb-3" id="overrideCommissions">--</h2>
                                <div class="progress">
                                    <div class="progress-bar bg-success" id="overrideProgress" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card bg-light h-100">
                            <div class="card-body">
                                <h5 class="card-title">Bonus Commissions</h5>
                                <h2 class="mt-3 mb-3" id="bonusCommissions">--</h2>
                                <div class="progress">
                                    <div class="progress-bar bg-warning" id="bonusProgress" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Commission Details Modal -->
<div class="modal fade" id="commissionDetailsModal" tabindex="-1" aria-labelledby="commissionDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="commissionDetailsModalLabel">Commission Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="commissionDetailsContent">
                <div class="text-center p-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading commission details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="editCommissionBtn">Edit Commission</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Commission Modal -->
<div class="modal fade" id="editCommissionModal" tabindex="-1" aria-labelledby="editCommissionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCommissionModalLabel">Edit Commission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editCommissionForm">
                    <input type="hidden" id="editCommissionId" name="id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="editCommissionType" class="form-label">Commission Type</label>
                            <select class="form-select" id="editCommissionType" name="type" required>
                                <option value="direct">Direct</option>
                                <option value="override">Override</option>
                                <option value="bonus">Bonus</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="editCommissionAmount" class="form-label">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="editCommissionAmount" name="amount" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="editCommissionStatus" class="form-label">Status</label>
                            <select class="form-select" id="editCommissionStatus" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="paid">Paid</option>
                                <option value="declined">Declined</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="editCommissionDate" class="form-label">Date</label>
                            <input type="date" class="form-control" id="editCommissionDate" name="commission_date" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editCommissionNotes" class="form-label">Notes</label>
                        <textarea class="form-control" id="editCommissionNotes" name="notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="updateCommissionBtn">Update Commission</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let commissionsPerPage = 10;
let currentFilter = 'all';
let searchQuery = '';
let commissions = [];
let selectedCommissionId = null;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize commissions page
    loadCommissions();
    loadCommissionStats();
    
    // Refresh commissions button
    document.getElementById('refreshCommissionsBtn').addEventListener('click', function() {
        loadCommissions();
        loadCommissionStats();
    });
    
    // Filter buttons
    document.querySelectorAll('.commission-filters [data-filter]').forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            document.querySelectorAll('.commission-filters [data-filter]').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Update filter
            currentFilter = this.getAttribute('data-filter');
            currentPage = 1;
            loadCommissions();
        });
    });
    
    // Search button
    document.getElementById('searchBtn').addEventListener('click', function() {
        searchQuery = document.getElementById('commissionSearch').value.trim();
        currentPage = 1;
        loadCommissions();
    });
    
    // Press Enter to search
    document.getElementById('commissionSearch').addEventListener('keyup', function(event) {
        if (event.key === 'Enter') {
            searchQuery = this.value.trim();
            currentPage = 1;
            loadCommissions();
        }
    });
    
    // Commissions per page change
    document.getElementById('commissionsPerPage').addEventListener('change', function() {
        commissionsPerPage = parseInt(this.value);
        currentPage = 1;
        loadCommissions();
    });
    
    // Edit commission button in commission details modal
    document.getElementById('editCommissionBtn').addEventListener('click', function() {
        // Close details modal
        bootstrap.Modal.getInstance(document.getElementById('commissionDetailsModal')).hide();
        
        // Open edit modal
        editCommission(selectedCommissionId);
    });
    
    // Update commission button
    document.getElementById('updateCommissionBtn').addEventListener('click', function() {
        updateCommission();
    });
    
    // Export CSV
    document.getElementById('exportCSV').addEventListener('click', function(e) {
        e.preventDefault();
        exportCommissions('csv');
    });
    
    // Export PDF
    document.getElementById('exportPDF').addEventListener('click', function(e) {
        e.preventDefault();
        exportCommissions('pdf');
    });
});

function loadCommissions() {
    const tableBody = document.getElementById('commissionsTableBody');
    tableBody.innerHTML = '<tr><td colspan="8" class="text-center"><div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div> Loading commissions...</td></tr>';
    
    // Build query string
    let queryParams = `?page=${currentPage}&limit=${commissionsPerPage}`;
    
    if (currentFilter !== 'all') {
        queryParams += `&status=${currentFilter}`;
    }
    
    if (searchQuery) {
        queryParams += `&search=${encodeURIComponent(searchQuery)}`;
    }
    
    // Fetch commissions
    fetch(`/api/admin-commissions.php${queryParams}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayCommissions(data.commissions);
                generatePagination(data.totalCommissions, data.totalPages);
                commissions = data.commissions;
            } else {
                tableBody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">Error loading commissions: ${data.error}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Failed to load commissions</td></tr>';
        });
}

function displayCommissions(commissions) {
    const tableBody = document.getElementById('commissionsTableBody');
    
    if (commissions.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="8" class="text-center">No commissions found</td></tr>';
        return;
    }
    
    let html = '';
    
    commissions.forEach(commission => {
        let statusBadge = '';
        
        switch (commission.status.toLowerCase()) {
            case 'approved':
                statusBadge = '<span class="badge bg-success text-uppercase">Approved</span>';
                break;
            case 'pending':
                statusBadge = '<span class="badge bg-warning text-uppercase">Pending</span>';
                break;
            case 'paid':
                statusBadge = '<span class="badge bg-info text-uppercase">Paid</span>';
                break;
            case 'declined':
                statusBadge = '<span class="badge bg-danger text-uppercase">Declined</span>';
                break;
            default:
                statusBadge = `<span class="badge bg-secondary text-uppercase">${commission.status}</span>`;
        }
        
        const commissionDate = new Date(commission.commission_date).toLocaleDateString();
        const commissionType = getCommissionTypeLabel(commission.type);
        
        html += `
            <tr data-commission-id="${commission.id}">
                <td>${commission.id}</td>
                <td>
                    <a href="/admin/users.php?id=${commission.user_id}" class="text-decoration-none">
                        ${commission.first_name} ${commission.last_name}
                    </a>
                    <div class="small text-muted">${commission.email}</div>
                </td>
                <td>${commissionType}</td>
                <td>
                    <a href="/admin/orders.php?id=${commission.order_id}" class="text-decoration-none">
                        Order #${commission.order_id}
                    </a>
                </td>
                <td>$${parseFloat(commission.amount).toFixed(2)}</td>
                <td>${commissionDate}</td>
                <td>${statusBadge}</td>
                <td>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-primary view-commission-btn" data-commission-id="${commission.id}">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <button class="btn btn-sm btn-warning edit-commission-btn" data-commission-id="${commission.id}">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    
    tableBody.innerHTML = html;
    
    // Add event listeners to buttons
    document.querySelectorAll('.view-commission-btn').forEach(button => {
        button.addEventListener('click', function() {
            const commissionId = this.getAttribute('data-commission-id');
            viewCommission(commissionId);
        });
    });
    
    document.querySelectorAll('.edit-commission-btn').forEach(button => {
        button.addEventListener('click', function() {
            const commissionId = this.getAttribute('data-commission-id');
            editCommission(commissionId);
        });
    });
}

function generatePagination(totalCommissions, totalPages) {
    const paginationContainer = document.getElementById('commissionsPagination');
    
    let html = '<nav aria-label="Commissions pagination"><ul class="pagination">';
    
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
        if (
            i === 1 ||
            i === totalPages ||
            (i >= currentPage - 2 && i <= currentPage + 2)
        ) {
            html += `
                <li class="page-item ${currentPage === i ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `;
        } else if (
            i === currentPage - 3 ||
            i === currentPage + 3
        ) {
            html += `
                <li class="page-item disabled">
                    <a class="page-link" href="#">...</a>
                </li>
            `;
        }
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
                loadCommissions();
            }
        });
    });
}

function loadCommissionStats() {
    fetch('/api/admin-commission-stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCommissionStats(data.stats);
            } else {
                console.error('Error loading commission stats:', data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function updateCommissionStats(stats) {
    document.getElementById('totalCommissions').textContent = '$' + parseFloat(stats.totalCommissions).toFixed(2);
    document.getElementById('totalPaid').textContent = '$' + parseFloat(stats.totalPaid).toFixed(2);
    document.getElementById('pendingCommissions').textContent = '$' + parseFloat(stats.pendingCommissions).toFixed(2);
    document.getElementById('activeAffiliates').textContent = stats.activeAffiliates;
    
    // Update commission type distribution
    document.getElementById('directCommissions').textContent = '$' + parseFloat(stats.directCommissions).toFixed(2);
    document.getElementById('overrideCommissions').textContent = '$' + parseFloat(stats.overrideCommissions).toFixed(2);
    document.getElementById('bonusCommissions').textContent = '$' + parseFloat(stats.bonusCommissions).toFixed(2);
    
    // Calculate percentages
    const totalAmount = parseFloat(stats.totalCommissions) || 1; // Avoid division by zero
    const directPercent = parseFloat(stats.directCommissions) / totalAmount * 100;
    const overridePercent = parseFloat(stats.overrideCommissions) / totalAmount * 100;
    const bonusPercent = parseFloat(stats.bonusCommissions) / totalAmount * 100;
    
    // Update progress bars
    document.getElementById('directProgress').style.width = `${directPercent}%`;
    document.getElementById('overrideProgress').style.width = `${overridePercent}%`;
    document.getElementById('bonusProgress').style.width = `${bonusPercent}%`;
}

function viewCommission(commissionId) {
    const commissionDetailsContent = document.getElementById('commissionDetailsContent');
    commissionDetailsContent.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading commission details...</p>
        </div>
    `;
    
    // Show modal
    const commissionDetailsModal = new bootstrap.Modal(document.getElementById('commissionDetailsModal'));
    commissionDetailsModal.show();
    
    // Store selected commission ID for edit button
    selectedCommissionId = commissionId;
    
    // Find commission in cached data
    const commission = commissions.find(c => c.id == commissionId);
    
    if (commission) {
        displayCommissionDetails(commission);
    } else {
        // Fetch commission details
        fetch(`/api/commission-details.php?id=${commissionId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayCommissionDetails(data.commission);
                } else {
                    commissionDetailsContent.innerHTML = `
                        <div class="alert alert-danger">
                            Error loading commission details: ${data.error}
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                commissionDetailsContent.innerHTML = `
                    <div class="alert alert-danger">
                        Failed to load commission details. Please try again.
                    </div>
                `;
            });
    }
}

function displayCommissionDetails(commission) {
    const commissionDetailsContent = document.getElementById('commissionDetailsContent');
    const commissionType = getCommissionTypeLabel(commission.type);
    const commissionDate = new Date(commission.commission_date).toLocaleDateString();
    
    let statusBadgeClass = '';
    switch (commission.status.toLowerCase()) {
        case 'approved':
            statusBadgeClass = 'success';
            break;
        case 'pending':
            statusBadgeClass = 'warning';
            break;
        case 'paid':
            statusBadgeClass = 'info';
            break;
        case 'declined':
            statusBadgeClass = 'danger';
            break;
        default:
            statusBadgeClass = 'secondary';
    }
    
    commissionDetailsContent.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6>Commission Information</h6>
                <table class="table table-sm">
                    <tr>
                        <th>Commission ID:</th>
                        <td>${commission.id}</td>
                    </tr>
                    <tr>
                        <th>Type:</th>
                        <td>${commissionType}</td>
                    </tr>
                    <tr>
                        <th>Amount:</th>
                        <td>$${parseFloat(commission.amount).toFixed(2)}</td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td><span class="badge bg-${statusBadgeClass}">${commission.status.toUpperCase()}</span></td>
                    </tr>
                    <tr>
                        <th>Date:</th>
                        <td>${commissionDate}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Affiliate Information</h6>
                <table class="table table-sm">
                    <tr>
                        <th>Name:</th>
                        <td>${commission.first_name} ${commission.last_name}</td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td>${commission.email}</td>
                    </tr>
                    <tr>
                        <th>Order ID:</th>
                        <td><a href="/admin/orders.php?id=${commission.order_id}">#${commission.order_id}</a></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="mt-4">
            <h6>Notes</h6>
            <div class="card bg-light">
                <div class="card-body">
                    ${commission.notes || 'No notes for this commission.'}
                </div>
            </div>
        </div>
    `;
}

function editCommission(commissionId) {
    // Find commission in cached data
    const commission = commissions.find(c => c.id == commissionId);
    
    if (commission) {
        populateEditForm(commission);
        
        // Show edit modal
        const editCommissionModal = new bootstrap.Modal(document.getElementById('editCommissionModal'));
        editCommissionModal.show();
    } else {
        // Fetch commission details
        fetch(`/api/commission-details.php?id=${commissionId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    populateEditForm(data.commission);
                    
                    // Show edit modal
                    const editCommissionModal = new bootstrap.Modal(document.getElementById('editCommissionModal'));
                    editCommissionModal.show();
                } else {
                    showAlert('danger', `Error loading commission details: ${data.error}`);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'Failed to load commission details. Please try again.');
            });
    }
}

function populateEditForm(commission) {
    document.getElementById('editCommissionId').value = commission.id;
    document.getElementById('editCommissionType').value = commission.type;
    document.getElementById('editCommissionAmount').value = parseFloat(commission.amount).toFixed(2);
    document.getElementById('editCommissionStatus').value = commission.status;
    
    // Format date as YYYY-MM-DD for input
    const date = new Date(commission.commission_date);
    const formattedDate = date.toISOString().split('T')[0];
    document.getElementById('editCommissionDate').value = formattedDate;
    
    document.getElementById('editCommissionNotes').value = commission.notes || '';
}

function updateCommission() {
    const form = document.getElementById('editCommissionForm');
    const formData = new FormData(form);
    
    // Convert form data to object
    const commissionData = Object.fromEntries(formData.entries());
    
    // Send request
    fetch('/api/admin-update-commission.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(commissionData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('editCommissionModal')).hide();
            
            // Show success message
            showAlert('success', 'Commission updated successfully!');
            
            // Reload commissions
            loadCommissions();
            loadCommissionStats();
        } else {
            showAlert('danger', `Error updating commission: ${data.error}`);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Failed to update commission. Please try again.');
    });
}

function exportCommissions(format) {
    // Build query string
    let queryParams = `?format=${format}`;
    
    if (currentFilter !== 'all') {
        queryParams += `&status=${currentFilter}`;
    }
    
    if (searchQuery) {
        queryParams += `&search=${encodeURIComponent(searchQuery)}`;
    }
    
    // Redirect to export endpoint
    window.location.href = `/api/admin-export-commissions.php${queryParams}`;
}

function getCommissionTypeLabel(type) {
    switch (type.toLowerCase()) {
        case 'direct':
            return 'Direct';
        case 'override':
            return 'Override';
        case 'bonus':
            return 'Bonus';
        default:
            return type;
    }
}
</script>

<?php
// Include admin footer
require_once __DIR__ . '/../includes/admin_footer.php';
?>