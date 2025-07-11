<?php
/**
 * Admin User Management
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

// Set page title
$page_title = 'User Management';

// Include admin header
require_once __DIR__ . '/../includes/admin_header.php';
?>

<!-- Admin Users Content -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">All Users</h5>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-primary me-2" id="refreshUsersBtn">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                    <!--<button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">-->
                    <!--    <i class="fas fa-plus"></i> Add User-->
                    <!--</button>-->
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped" id="usersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Membership</th>
                                <th>Join Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <tr>
                                <td colspan="7" class="text-center">Loading users...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div id="usersPagination">
                            <!-- Pagination will be generated here -->
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="d-inline-block">
                            <select class="form-select form-select-sm" id="usersPerPage">
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

<!-- User Stats Row -->
<div class="row">
    <div class="col-md-4 mb-4">
        <div class="stats-card">
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
            <h3 id="totalUsers">--</h3>
            <p>Total Users</p>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="stats-card">
            <div class="icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <h3 id="newUsers">--</h3>
            <p>New Users (Last 30 Days)</p>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="stats-card">
            <div class="icon">
                <i class="fas fa-crown"></i>
            </div>
            <h3 id="premiumUsers">--</h3>
            <p>Premium Users</p>
        </div>
    </div>
</div>


<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addUserForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="firstName" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="firstName" name="first_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="lastName" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="lastName" name="last_name" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="tierLevel" class="form-label">Membership Tier</label>
                        <select class="form-select" id="tierLevel" name="tier_id">
                            <option value="0">No Membership</option>
                            <option value="1">Basic Tier</option>
                            <option value="2">Premium Tier</option>
                            <option value="3">Elite Tier</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone">
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="isAdmin" name="is_admin">
                        <label class="form-check-label" for="isAdmin">Admin User</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveUserBtn">Save User</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    <input type="hidden" id="editUserId" name="id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="editFirstName" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="editFirstName" name="first_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editLastName" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="editLastName" name="last_name" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editEmail" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="editEmail" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editPassword" class="form-label">Password (leave blank to keep current)</label>
                        <input type="password" class="form-control" id="editPassword" name="password">
                    </div>
                    
                    <div class="mb-3">
                        <label for="editTierLevel" class="form-label">Membership Tier</label>
                        <select class="form-select" id="editTierLevel" name="tier_id">
                            <option value="0">No Membership</option>
                            <option value="1">Basic Tier</option>
                            <option value="2">Premium Tier</option>
                            <option value="3">Elite Tier</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editPhone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="editPhone" name="phone">
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="editIsAdmin" name="is_admin">
                        <label class="form-check-label" for="editIsAdmin">Admin User</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="updateUserBtn">Update User</button>
            </div>
        </div>
    </div>
</div>

<!-- View User Modal -->
<div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewUserModalLabel">User Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewUserContent">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading user details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="editUserBtn">Edit User</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let usersPerPage = 10;
let users = [];
let selectedUserId = null;

document.addEventListener('DOMContentLoaded', function() {
    // Load users
    loadUsers();
    
    // Load user stats
    loadUserStats();
    
    // Refresh users button
    document.getElementById('refreshUsersBtn').addEventListener('click', function() {
        loadUsers();
        loadUserStats();
    });
    
    // Users per page change
    document.getElementById('usersPerPage').addEventListener('change', function() {
        usersPerPage = parseInt(this.value);
        currentPage = 1;
        loadUsers();
    });
    
    // Save user button
    document.getElementById('saveUserBtn').addEventListener('click', function() {
        saveUser();
    });
    
    // Update user button
    document.getElementById('updateUserBtn').addEventListener('click', function() {
        updateUser();
    });
});

function loadUsers() {
    const tableBody = document.getElementById('usersTableBody');
    tableBody.innerHTML = '<tr><td colspan="7" class="text-center"><div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div> Loading users...</td></tr>';
    
    // Fetch users
    fetch(`https://levartiportal.com//api/admin-users.php?page=${currentPage}&limit=${usersPerPage}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayUsers(data.users);
                generatePagination(data.totalUsers, data.totalPages);
                users = data.users;
            } else {
                tableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">Error loading users: ${data.error}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Failed to load users</td></tr>';
        });
}

function displayUsers(users) {
    const tableBody = document.getElementById('usersTableBody');
    
    if (users.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="7" class="text-center">No users found</td></tr>';
        return;
    }
    
    let html = '';
    
    users.forEach(user => {
        const tierName = getTierName(user.tier_id);
        const joinDate = new Date(user.created_at).toLocaleDateString();
        
        html += `
            <tr data-user-id="${user.id}">
                <td>${user.id}</td>
                <td>${user.first_name} ${user.last_name}</td>
                <td>${user.email}</td>
                <td>${user.tierName}</td>
                <td>${joinDate}</td>
                <td><span class="badge bg-success">Active</span></td>
                <td>
                    <button class="btn btn-sm btn-primary view-user-btn" data-user-id="${user.id}">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-warning edit-user-btn" data-user-id="${user.id}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-user-btn" data-user-id="${user.id}">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    tableBody.innerHTML = html;
    
    // Add event listeners to buttons
    document.querySelectorAll('.view-user-btn').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            viewUser(userId);
        });
    });
    
    document.querySelectorAll('.edit-user-btn').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            editUser(userId);
        });
    });
    
    document.querySelectorAll('.delete-user-btn').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                deleteUser(userId);
            }
        });
    });
}

function generatePagination(totalUsers, totalPages) {
    const paginationContainer = document.getElementById('usersPagination');
    
    let html = '<nav aria-label="Users pagination"><ul class="pagination">';
    
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
            const page = parseInt(this.getAttribute('data-page'));
            if (page > 0 && page <= totalPages) {
                currentPage = page;
                loadUsers();
            }
        });
    });
}

function loadUserStats() {
    fetch('https://levartiportal.com//api/admin-user-stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateUserStats(data.stats);
            } else {
                console.error('Error loading user stats:', data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function updateUserStats(stats) {
    document.getElementById('totalUsers').textContent = stats.totalUsers;
    document.getElementById('newUsers').textContent = stats.newUsers;
    document.getElementById('premiumUsers').textContent = stats.premiumUsers;
    
    // Update tier counts
    document.getElementById('basicTierCount').textContent = stats.basicTierCount;
    document.getElementById('premiumTierCount').textContent = stats.premiumTierCount;
    document.getElementById('eliteTierCount').textContent = stats.eliteTierCount;
    
    // Calculate percentages
    const totalUsers = parseInt(stats.totalUsers);
    const basicPercent = totalUsers > 0 ? (stats.basicTierCount / totalUsers * 100) : 0;
    const premiumPercent = totalUsers > 0 ? (stats.premiumTierCount / totalUsers * 100) : 0;
    const elitePercent = totalUsers > 0 ? (stats.eliteTierCount / totalUsers * 100) : 0;
    
    // Update progress bars
    document.getElementById('basicTierProgress').style.width = `${basicPercent}%`;
    document.getElementById('premiumTierProgress').style.width = `${premiumPercent}%`;
    document.getElementById('eliteTierProgress').style.width = `${elitePercent}%`;
}

function viewUser(userId) {
    const viewUserContent = document.getElementById('viewUserContent');
    viewUserContent.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading user details...</p>
        </div>
    `;
    
    // Show modal
    const viewUserModal = new bootstrap.Modal(document.getElementById('viewUserModal'));
    viewUserModal.show();
    
    // Store selected user ID for edit button
    selectedUserId = userId;
    
    // Update edit button
    document.getElementById('editUserBtn').addEventListener('click', function() {
        viewUserModal.hide();
        editUser(selectedUserId);
    });
    
    // Find user in cached data
    const user = users.find(u => u.id === parseInt(userId));
    
    if (user) {
        displayUserDetails(user);
    } else {
        // Fetch user details
        fetch(`https://levartiportal.com//api/admin-user-details.php?id=${userId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayUserDetails(data.user);
                } else {
                    viewUserContent.innerHTML = `
                        <div class="alert alert-danger">
                            Error loading user details: ${data.error}
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                viewUserContent.innerHTML = `
                    <div class="alert alert-danger">
                        Failed to load user details. Please try again.
                    </div>
                `;
            });
    }
}

function displayUserDetails(user) {
    const viewUserContent = document.getElementById('viewUserContent');
    const tierName = getTierName(user.tier_id);
    const joinDate = new Date(user.created_at).toLocaleDateString();
    
    viewUserContent.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6>User Information</h6>
                <table class="table table-sm">
                    <tr>
                        <th>ID:</th>
                        <td>${user.id}</td>
                    </tr>
                    <tr>
                        <th>Name:</th>
                        <td>${user.first_name} ${user.last_name}</td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td>${user.email}</td>
                    </tr>
                    <tr>
                        <th>Phone:</th>
                        <td>${user.phone || 'Not provided'}</td>
                    </tr>
                    <tr>
                        <th>Join Date:</th>
                        <td>${joinDate}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Membership Details</h6>
                <table class="table table-sm">
                    <tr>
                        <th>Tier:</th>
                        <td>${tierName}</td>
                    </tr>
                    <tr>
                        <th>Admin:</th>
                        <td>${user.is_admin ? 'Yes' : 'No'}</td>
                    </tr>
                    <tr>
                        <th>GHL ID:</th>
                        <td>${user.ghl_id || 'Not connected'}</td>
                    </tr>
                    <tr>
                        <th>Pillars ID:</th>
                        <td>${user.pillars_id || 'Not connected'}</td>
                    </tr>
                </table>
            </div>
        </div>
    `;
}

function editUser(userId) {
    // Find user in cached data
    const user = users.find(u => u.id === parseInt(userId));
    
    if (user) {
        populateEditForm(user);
        
        // Show modal
        const editUserModal = new bootstrap.Modal(document.getElementById('editUserModal'));
        editUserModal.show();
    } else {
        // Fetch user details
        fetch(`https://levartiportal.com//api/admin-user-details.php?id=${userId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    populateEditForm(data.user);
                    
                    // Show modal
                    const editUserModal = new bootstrap.Modal(document.getElementById('editUserModal'));
                    editUserModal.show();
                } else {
                    showAlert('danger', `Error loading user details: ${data.error}`);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'Failed to load user details. Please try again.');
            });
    }
}

function populateEditForm(user) {
    document.getElementById('editUserId').value = user.id;
    document.getElementById('editFirstName').value = user.first_name;
    document.getElementById('editLastName').value = user.last_name;
    document.getElementById('editEmail').value = user.email;
    document.getElementById('editPassword').value = '';
    document.getElementById('editTierLevel').value = user.tier_id;
    document.getElementById('editPhone').value = user.phone || '';
    document.getElementById('editIsAdmin').checked = user.is_admin;
}

function saveUser() {
    const form = document.getElementById('addUserForm');
    const formData = new FormData(form);
    
    // Convert form data to object
    const userData = Object.fromEntries(formData.entries());
    userData.is_admin = formData.has('is_admin') ? 1 : 0;
    
    // Send request
    fetch('https://levartiportal.com//api/admin-create-user.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(userData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('addUserModal')).hide();
            
            // Reset form
            form.reset();
            
            // Show success message
            showAlert('success', 'User created successfully!');
            
            // Reload users
            loadUsers();
            loadUserStats();
        } else {
            showAlert('danger', `Error creating user: ${data.error}`);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Failed to create user. Please try again.');
    });
}

function updateUser() {
    const form = document.getElementById('editUserForm');
    const formData = new FormData(form);
    
    // Convert form data to object
    const userData = Object.fromEntries(formData.entries());
    userData.is_admin = formData.has('is_admin') ? 1 : 0;
    
    // If password is empty, remove it from the object
    if (!userData.password) {
        delete userData.password;
    }
    
    // Send request
    fetch('https://levartiportal.com//api/admin-update-user.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(userData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('editUserModal')).hide();
            
            // Show success message
            showAlert('success', 'User updated successfully!');
            
            // Reload users
            loadUsers();
            loadUserStats();
        } else {
            showAlert('danger', `Error updating user: ${data.error}`);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Failed to update user. Please try again.');
    });
}

function deleteUser(userId) {
    // Send request
    fetch('https://levartiportal.com//api/admin-delete-user.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: userId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            showAlert('success', 'User deleted successfully!');
            
            // Reload users
            loadUsers();
            loadUserStats();
        } else {
            showAlert('danger', `Error deleting user: ${data.error}`);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Failed to delete user. Please try again.');
    });
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
            return 'No Membership';
    }
}
</script>

<?php
// Include admin footer
require_once __DIR__ . '/../includes/admin_footer.php';
?>