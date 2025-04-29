<?php
/**
 * Admin Order Management
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

// Set page title
$page_title = 'Order Management';

// Include admin header
require_once __DIR__ . '/../includes/admin_header.php';
?>

<!-- Admin Orders Content -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">All Orders</h5>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-primary me-2" id="refreshOrdersBtn">
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
                <div class="order-filters mb-4">
                    <div class="row">
                        <div class="col-md-9">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-primary active" data-filter="all">All</button>
                                <button type="button" class="btn btn-outline-primary" data-filter="pending">Pending</button>
                                <button type="button" class="btn btn-outline-primary" data-filter="processing">Processing</button>
                                <button type="button" class="btn btn-outline-primary" data-filter="completed">Completed</button>
                                <button type="button" class="btn btn-outline-primary" data-filter="failed">Failed</button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="input-group">
                                <input type="text" class="form-control" id="orderSearch" placeholder="Search orders...">
                                <button class="btn btn-outline-primary" type="button" id="searchBtn">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle" id="ordersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Product</th>
                                <th>Tier</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="ordersTableBody">
                            <tr>
                                <td colspan="8" class="text-center">Loading orders...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div id="ordersPagination">
                            <!-- Pagination will be generated here -->
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="d-inline-block">
                            <select class="form-select form-select-sm" id="ordersPerPage">
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

<!-- Order Stats -->
<div class="row">
    <div class="col-md-3 mb-4">
        <div class="stats-card">
            <div class="icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <h3 id="totalOrders">--</h3>
            <p>Total Orders</p>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="stats-card">
            <div class="icon">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <h3 id="totalRevenue">--</h3>
            <p>Total Revenue</p>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="stats-card">
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3 id="completedOrders">--</h3>
            <p>Completed Orders</p>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="stats-card">
            <div class="icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h3 id="pendingOrders">--</h3>
            <p>Pending Orders</p>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderDetailsModalLabel">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <div class="text-center p-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading order details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="editOrderBtn">Edit Order</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Order Modal -->
<div class="modal fade" id="editOrderModal" tabindex="-1" aria-labelledby="editOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editOrderModalLabel">Edit Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editOrderForm">
                    <input type="hidden" id="editOrderId" name="id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="editProductId" class="form-label">Product</label>
                            <select class="form-select" id="editProductId" name="product_id" required>
                                <!-- Products will be loaded here -->
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="editAmount" class="form-label">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="editAmount" name="amount" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="editStatus" class="form-label">Status</label>
                            <select class="form-select" id="editStatus" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="completed">Completed</option>
                                <option value="failed">Failed</option>
                                <option value="refunded">Refunded</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="editOrderDate" class="form-label">Order Date</label>
                            <input type="date" class="form-control" id="editOrderDate" name="order_date" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editNotes" class="form-label">Order Notes</label>
                        <textarea class="form-control" id="editNotes" name="notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="updateOrderBtn">Update Order</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let ordersPerPage = 10;
let currentFilter = 'all';
let searchQuery = '';
let orders = [];
let selectedOrderId = null;

document.addEventListener('DOMContentLoaded', function() {
    // Load orders
    loadOrders();
    
    // Load order stats
    loadOrderStats();
    
    // Refresh orders button
    document.getElementById('refreshOrdersBtn').addEventListener('click', function() {
        loadOrders();
        loadOrderStats();
    });
    
    // Filter buttons
    document.querySelectorAll('.order-filters [data-filter]').forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            document.querySelectorAll('.order-filters [data-filter]').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Update filter
            currentFilter = this.getAttribute('data-filter');
            currentPage = 1;
            loadOrders();
        });
    });
    
    // Search button
    document.getElementById('searchBtn').addEventListener('click', function() {
        searchQuery = document.getElementById('orderSearch').value.trim();
        currentPage = 1;
        loadOrders();
    });
    
    // Press Enter to search
    document.getElementById('orderSearch').addEventListener('keyup', function(event) {
        if (event.key === 'Enter') {
            searchQuery = this.value.trim();
            currentPage = 1;
            loadOrders();
        }
    });
    
    // Orders per page change
    document.getElementById('ordersPerPage').addEventListener('change', function() {
        ordersPerPage = parseInt(this.value);
        currentPage = 1;
        loadOrders();
    });
    
    // Edit order button in order details modal
    document.getElementById('editOrderBtn').addEventListener('click', function() {
        // Close details modal
        bootstrap.Modal.getInstance(document.getElementById('orderDetailsModal')).hide();
        
        // Open edit modal
        editOrder(selectedOrderId);
    });
    
    // Update order button
    document.getElementById('updateOrderBtn').addEventListener('click', function() {
        updateOrder();
    });
    
    // Export CSV
    document.getElementById('exportCSV').addEventListener('click', function(e) {
        e.preventDefault();
        exportOrders('csv');
    });
    
    // Export PDF
    document.getElementById('exportPDF').addEventListener('click', function(e) {
        e.preventDefault();
        exportOrders('pdf');
    });
});

function loadOrders() {
    const tableBody = document.getElementById('ordersTableBody');
    tableBody.innerHTML = '<tr><td colspan="8" class="text-center"><div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div> Loading orders...</td></tr>';
    
    // Build query string
    let queryParams = `?page=${currentPage}&limit=${ordersPerPage}`;
    
    if (currentFilter !== 'all') {
        queryParams += `&status=${currentFilter}`;
    }
    
    if (searchQuery) {
        queryParams += `&search=${encodeURIComponent(searchQuery)}`;
    }
    
    // Fetch orders
    fetch(`/api/admin-orders.php${queryParams}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayOrders(data.orders);
                generatePagination(data.totalOrders, data.totalPages);
                orders = data.orders;
            } else {
                tableBody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">Error loading orders: ${data.error}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Failed to load orders</td></tr>';
        });
}

function displayOrders(orders) {
    const tableBody = document.getElementById('ordersTableBody');
    
    if (orders.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="8" class="text-center">No orders found</td></tr>';
        return;
    }
    
    let html = '';
    
    orders.forEach(order => {
        let statusBadge = '';
        
        switch (order.status.toLowerCase()) {
            case 'completed':
                statusBadge = '<span class="badge bg-success text-uppercase">Completed</span>';
                break;
            case 'pending':
                statusBadge = '<span class="badge bg-warning text-uppercase">Pending</span>';
                break;
            case 'processing':
                statusBadge = '<span class="badge bg-info text-uppercase">Processing</span>';
                break;
            case 'failed':
                statusBadge = '<span class="badge bg-danger text-uppercase">Failed</span>';
                break;
            case 'refunded':
                statusBadge = '<span class="badge bg-secondary text-uppercase">Refunded</span>';
                break;
            default:
                statusBadge = `<span class="badge bg-secondary text-uppercase">${order.status}</span>`;
        }
        
        const tierName = getTierName(order.tier_level);
        const orderDate = new Date(order.order_date).toLocaleDateString();
        
        html += `
            <tr data-order-id="${order.id}">
                <td>${order.id}</td>
                <td>
                    <a href="/admin/users.php?id=${order.user_id}" class="text-decoration-none">
                        ${order.first_name} ${order.last_name}
                    </a>
                    <div class="small text-muted">${order.email}</div>
                </td>
                <td>${order.product_name}</td>
                <td>${tierName}</td>
                <td>$${parseFloat(order.amount).toFixed(2)}</td>
                <td>${orderDate}</td>
                <td>${statusBadge}</td>
                <td>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-primary view-order-btn" data-order-id="${order.id}">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <button class="btn btn-sm btn-warning edit-order-btn" data-order-id="${order.id}">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    
    tableBody.innerHTML = html;
    
    // Add event listeners to buttons
    document.querySelectorAll('.view-order-btn').forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            viewOrder(orderId);
        });
    });
    
    document.querySelectorAll('.edit-order-btn').forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            editOrder(orderId);
        });
    });
}

function generatePagination(totalOrders, totalPages) {
    const paginationContainer = document.getElementById('ordersPagination');
    
    let html = '<nav aria-label="Orders pagination"><ul class="pagination">';
    
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
                loadOrders();
            }
        });
    });
}

function loadOrderStats() {
    fetch('/api/admin-order-stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateOrderStats(data.stats);
            } else {
                console.error('Error loading order stats:', data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function updateOrderStats(stats) {
    document.getElementById('totalOrders').textContent = stats.totalOrders;
    document.getElementById('totalRevenue').textContent = '$' + parseFloat(stats.totalRevenue).toFixed(2);
    document.getElementById('completedOrders').textContent = stats.completedOrders;
    document.getElementById('pendingOrders').textContent = stats.pendingOrders;
}

function viewOrder(orderId) {
    const orderDetailsContent = document.getElementById('orderDetailsContent');
    orderDetailsContent.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading order details...</p>
        </div>
    `;
    
    // Show modal
    const orderDetailsModal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
    orderDetailsModal.show();
    
    // Store selected order ID for edit button
    selectedOrderId = orderId;
    
    // Find order in cached data
    const order = orders.find(o => o.id == orderId);
    
    if (order) {
        displayOrderDetails(order);
    } else {
        // Fetch order details
        fetch(`/api/order-details.php?id=${orderId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayOrderDetails(data.order);
                } else {
                    orderDetailsContent.innerHTML = `
                        <div class="alert alert-danger">
                            Error loading order details: ${data.error}
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                orderDetailsContent.innerHTML = `
                    <div class="alert alert-danger">
                        Failed to load order details. Please try again.
                    </div>
                `;
            });
    }
}

function displayOrderDetails(order) {
    const orderDetailsContent = document.getElementById('orderDetailsContent');
    const tierName = getTierName(order.tier_level);
    const orderDate = new Date(order.order_date).toLocaleDateString();
    
    let statusBadgeClass = '';
    switch (order.status.toLowerCase()) {
        case 'completed':
            statusBadgeClass = 'success';
            break;
        case 'pending':
            statusBadgeClass = 'warning';
            break;
        case 'processing':
            statusBadgeClass = 'info';
            break;
        case 'failed':
            statusBadgeClass = 'danger';
            break;
        case 'refunded':
            statusBadgeClass = 'secondary';
            break;
        default:
            statusBadgeClass = 'secondary';
    }
    
    orderDetailsContent.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6>Order Information</h6>
                <table class="table table-sm">
                    <tr>
                        <th>Order ID:</th>
                        <td>${order.id}</td>
                    </tr>
                    <tr>
                        <th>Date:</th>
                        <td>${orderDate}</td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td><span class="badge bg-${statusBadgeClass}">${order.status.toUpperCase()}</span></td>
                    </tr>
                    <tr>
                        <th>Amount:</th>
                        <td>$${parseFloat(order.amount).toFixed(2)}</td>
                    </tr>
                    <tr>
                        <th>Product:</th>
                        <td>${order.product_name}</td>
                    </tr>
                    <tr>
                        <th>Tier:</th>
                        <td>${tierName}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Customer Information</h6>
                <table class="table table-sm">
                    <tr>
                        <th>Name:</th>
                        <td>${order.first_name} ${order.last_name}</td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td>${order.email}</td>
                    </tr>
                    <!-- Removed GHL Order ID reference -->
                </table>
            </div>
        </div>
        
        <div class="mt-4">
            <h6>Notes</h6>
            <div class="card bg-light">
                <div class="card-body">
                    ${order.notes || 'No notes for this order.'}
                </div>
            </div>
        </div>
    `;
}

function editOrder(orderId) {
    // First, load products for the dropdown
    fetch('/api/admin-products.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Populate product dropdown
                const productSelect = document.getElementById('editProductId');
                
                let options = '';
                data.products.forEach(product => {
                    options += `<option value="${product.id}">${product.name} ($${parseFloat(product.price).toFixed(2)})</option>`;
                });
                
                productSelect.innerHTML = options;
                
                // Now, find order in cached data or fetch it
                const order = orders.find(o => o.id == orderId);
                
                if (order) {
                    populateEditForm(order);
                } else {
                    // Fetch order details
                    fetch(`/api/order-details.php?id=${orderId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                populateEditForm(data.order);
                            } else {
                                showAlert('danger', `Error loading order details: ${data.error}`);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showAlert('danger', 'Failed to load order details. Please try again.');
                        });
                }
                
                // Show edit modal
                const editOrderModal = new bootstrap.Modal(document.getElementById('editOrderModal'));
                editOrderModal.show();
            } else {
                showAlert('danger', `Error loading products: ${data.error}`);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'Failed to load products. Please try again.');
        });
}

function populateEditForm(order) {
    document.getElementById('editOrderId').value = order.id;
    document.getElementById('editProductId').value = order.product_id;
    document.getElementById('editAmount').value = parseFloat(order.amount).toFixed(2);
    document.getElementById('editStatus').value = order.status;
    
    // Format date as YYYY-MM-DD for input
    const date = new Date(order.order_date);
    const formattedDate = date.toISOString().split('T')[0];
    document.getElementById('editOrderDate').value = formattedDate;
    
    document.getElementById('editNotes').value = order.notes || '';
}

function updateOrder() {
    const form = document.getElementById('editOrderForm');
    const formData = new FormData(form);
    
    // Convert form data to object
    const orderData = Object.fromEntries(formData.entries());
    
    // Send request
    fetch('/api/admin-update-order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(orderData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('editOrderModal')).hide();
            
            // Show success message
            showAlert('success', 'Order updated successfully!');
            
            // Reload orders
            loadOrders();
            loadOrderStats();
        } else {
            showAlert('danger', `Error updating order: ${data.error}`);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Failed to update order. Please try again.');
    });
}

function exportOrders(format) {
    // Build query string
    let queryParams = `?format=${format}`;
    
    if (currentFilter !== 'all') {
        queryParams += `&status=${currentFilter}`;
    }
    
    if (searchQuery) {
        queryParams += `&search=${encodeURIComponent(searchQuery)}`;
    }
    
    // Redirect to export endpoint
    window.location.href = `/api/admin-export-orders.php${queryParams}`;
}

function getTierName(tierId) {
    switch (parseInt(tierId)) {
        case 1:
            return 'Basic';
        case 2:
            return 'Premium';
        case 3:
            return 'Elite';
        default:
            return 'Unknown';
    }
}
</script>

<?php
// Include admin footer
require_once __DIR__ . '/../includes/admin_footer.php';
?>