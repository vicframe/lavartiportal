<?php
/**
 * Admin Settings
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

// Set page title
$page_title = 'System Settings';

// Include admin header
require_once __DIR__ . '/../includes/admin_header.php';
?>

<!-- Admin Settings Content -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">System Configuration</h5>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-secondary me-2" id="resetSettingsBtn">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" id="saveSettingsBtn">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    These settings control core functionality of the platform. Changes will take effect immediately after saving.
                </div>
                
                <form id="systemSettingsForm">
                    <!-- General Settings -->
                    <h6 class="mb-3">General Settings</h6>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="siteName" class="form-label">Site Name</label>
                                <input type="text" class="form-control" id="siteName" name="site_name" placeholder="LaVarti Travel">
                            </div>
                            
                            <div class="mb-3">
                                <label for="siteDescription" class="form-label">Site Description</label>
                                <textarea class="form-control" id="siteDescription" name="site_description" rows="2" placeholder="Travel membership and affiliate platform"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="contactEmail" class="form-label">Contact Email</label>
                                <input type="email" class="form-control" id="contactEmail" name="contact_email" placeholder="support@example.com">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="timeZone" class="form-label">Time Zone</label>
                                <select class="form-select" id="timeZone" name="time_zone">
                                    <option value="UTC">UTC</option>
                                    <option value="America/New_York">Eastern Time (ET)</option>
                                    <option value="America/Chicago">Central Time (CT)</option>
                                    <option value="America/Denver">Mountain Time (MT)</option>
                                    <option value="America/Los_Angeles">Pacific Time (PT)</option>
                                    <option value="America/Anchorage">Alaska Time</option>
                                    <option value="America/Honolulu">Hawaii Time</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="dateFormat" class="form-label">Date Format</label>
                                <select class="form-select" id="dateFormat" name="date_format">
                                    <option value="m/d/Y">MM/DD/YYYY (e.g., 04/29/2025)</option>
                                    <option value="d/m/Y">DD/MM/YYYY (e.g., 29/04/2025)</option>
                                    <option value="Y-m-d">YYYY-MM-DD (e.g., 2025-04-29)</option>
                                    <option value="F j, Y">Month D, YYYY (e.g., April 29, 2025)</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="currency" class="form-label">Currency</label>
                                <select class="form-select" id="currency" name="currency">
                                    <option value="USD">US Dollar ($)</option>
                                    <option value="EUR">Euro (€)</option>
                                    <option value="GBP">British Pound (£)</option>
                                    <option value="CAD">Canadian Dollar (C$)</option>
                                    <option value="AUD">Australian Dollar (A$)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User Settings -->
                    <h6 class="mb-3">User & Registration Settings</h6>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3 form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="allowRegistration" name="allow_registration" checked>
                                <label class="form-check-label" for="allowRegistration">Allow New Registrations</label>
                            </div>
                            
                            <div class="mb-3 form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="requireEmailVerification" name="require_email_verification" checked>
                                <label class="form-check-label" for="requireEmailVerification">Require Email Verification</label>
                            </div>
                            
                            <div class="mb-3 form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="allowGuestCheckout" name="allow_guest_checkout">
                                <label class="form-check-label" for="allowGuestCheckout">Allow Guest Checkout</label>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="defaultUserRole" class="form-label">Default User Role</label>
                                <select class="form-select" id="defaultUserRole" name="default_user_role">
                                    <option value="customer">Customer</option>
                                    <option value="affiliate">Affiliate</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="minPasswordLength" class="form-label">Minimum Password Length</label>
                                <input type="number" class="form-control" id="minPasswordLength" name="min_password_length" min="6" max="24" value="8">
                            </div>
                            
                            <div class="mb-3">
                                <label for="sessionTimeout" class="form-label">Session Timeout (minutes)</label>
                                <input type="number" class="form-control" id="sessionTimeout" name="session_timeout" min="15" max="1440" value="120">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Email Settings -->
                    <h6 class="mb-3">Email Settings</h6>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="smtpHost" class="form-label">SMTP Host</label>
                                <input type="text" class="form-control" id="smtpHost" name="smtp_host" placeholder="smtp.example.com">
                            </div>
                            
                            <div class="mb-3">
                                <label for="smtpPort" class="form-label">SMTP Port</label>
                                <input type="number" class="form-control" id="smtpPort" name="smtp_port" placeholder="587">
                            </div>
                            
                            <div class="mb-3">
                                <label for="smtpUsername" class="form-label">SMTP Username</label>
                                <input type="text" class="form-control" id="smtpUsername" name="smtp_username" placeholder="username@example.com">
                            </div>
                            
                            <div class="mb-3">
                                <label for="smtpPassword" class="form-label">SMTP Password</label>
                                <input type="password" class="form-control" id="smtpPassword" name="smtp_password" placeholder="••••••••">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="emailFromName" class="form-label">From Name</label>
                                <input type="text" class="form-control" id="emailFromName" name="email_from_name" placeholder="LaVarti Travel">
                            </div>
                            
                            <div class="mb-3">
                                <label for="emailFromAddress" class="form-label">From Email Address</label>
                                <input type="email" class="form-control" id="emailFromAddress" name="email_from_address" placeholder="noreply@example.com">
                            </div>
                            
                            <div class="mb-3 form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="smtpAuth" name="smtp_auth" checked>
                                <label class="form-check-label" for="smtpAuth">Use SMTP Authentication</label>
                            </div>
                            
                            <div class="mb-3 form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="smtpSecure" name="smtp_secure" checked>
                                <label class="form-check-label" for="smtpSecure">Use TLS/SSL</label>
                            </div>
                            
                            <div class="mb-3 d-grid">
                                <button type="button" class="btn btn-outline-primary" id="testEmailBtn">
                                    <i class="fas fa-paper-plane"></i> Test Email Configuration
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Commission Settings -->
                    <h6 class="mb-3">Commission Settings</h6>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="directCommissionRate" class="form-label">Direct Commission Rate (%)</label>
                                <input type="number" class="form-control" id="directCommissionRate" name="direct_commission_rate" min="0" max="100" step="0.01" value="10">
                            </div>
                            
                            <div class="mb-3">
                                <label for="overrideCommissionRate" class="form-label">Override Commission Rate (%)</label>
                                <input type="number" class="form-control" id="overrideCommissionRate" name="override_commission_rate" min="0" max="100" step="0.01" value="5">
                            </div>
                            
                            <div class="mb-3">
                                <label for="bonusCommissionThreshold" class="form-label">Bonus Commission Threshold ($)</label>
                                <input type="number" class="form-control" id="bonusCommissionThreshold" name="bonus_commission_threshold" min="0" step="10" value="1000">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="commissionPayout" class="form-label">Commission Payout Frequency</label>
                                <select class="form-select" id="commissionPayout" name="commission_payout">
                                    <option value="weekly">Weekly</option>
                                    <option value="biweekly">Bi-weekly</option>
                                    <option value="monthly" selected>Monthly</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="minimumPayout" class="form-label">Minimum Payout Amount ($)</label>
                                <input type="number" class="form-control" id="minimumPayout" name="minimum_payout" min="0" step="5" value="50">
                            </div>
                            
                            <div class="mb-3 form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="autoApproveCommissions" name="auto_approve_commissions">
                                <label class="form-check-label" for="autoApproveCommissions">Auto-approve Commissions</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Advanced Settings -->
                    <h6 class="mb-3">Advanced Settings</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="logLevel" class="form-label">Log Level</label>
                                <select class="form-select" id="logLevel" name="log_level">
                                    <option value="error">Error Only</option>
                                    <option value="warning">Warning & Error</option>
                                    <option value="info" selected>Info, Warning & Error</option>
                                    <option value="debug">Debug (Verbose)</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="maintenanceMode" class="form-label">Maintenance Mode</label>
                                <select class="form-select" id="maintenanceMode" name="maintenance_mode">
                                    <option value="off" selected>Off</option>
                                    <option value="scheduled">Scheduled</option>
                                    <option value="on">On (Site Offline)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cacheLifetime" class="form-label">Cache Lifetime (minutes)</label>
                                <input type="number" class="form-control" id="cacheLifetime" name="cache_lifetime" min="0" max="1440" value="60">
                            </div>
                            
                            <div class="mb-3 form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="enableDebugMode" name="enable_debug_mode">
                                <label class="form-check-label" for="enableDebugMode">Enable Debug Mode</label>
                            </div>
                            
                            <div class="mb-3 form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="enableAuditLog" name="enable_audit_log" checked>
                                <label class="form-check-label" for="enableAuditLog">Enable Audit Logging</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card-footer text-end">
                <button type="button" class="btn btn-secondary me-2" id="resetSettingsBtn2">Reset</button>
                <button type="button" class="btn btn-primary" id="saveSettingsBtn2">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Test Email Modal -->
<div class="modal fade" id="testEmailModal" tabindex="-1" aria-labelledby="testEmailModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="testEmailModalLabel">Test Email Configuration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="testEmailRecipient" class="form-label">Recipient Email</label>
                    <input type="email" class="form-control" id="testEmailRecipient" placeholder="your@email.com" required>
                    <div class="form-text">Enter the email address where you want to receive the test email.</div>
                </div>
                
                <div class="mb-3">
                    <label for="testEmailSubject" class="form-label">Subject</label>
                    <input type="text" class="form-control" id="testEmailSubject" value="Test Email from LaVarti Travel" required>
                </div>
                
                <div class="mb-3">
                    <label for="testEmailMessage" class="form-label">Message</label>
                    <textarea class="form-control" id="testEmailMessage" rows="3">This is a test email from the LaVarti Travel platform. If you're receiving this, your email configuration is working correctly.</textarea>
                </div>
                
                <div id="testEmailResult" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="sendTestEmailBtn">Send Test Email</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load system settings
    loadSystemSettings();
    
    // Save settings button (both top and bottom)
    document.getElementById('saveSettingsBtn').addEventListener('click', saveSystemSettings);
    document.getElementById('saveSettingsBtn2').addEventListener('click', saveSystemSettings);
    
    // Reset settings button (both top and bottom)
    document.getElementById('resetSettingsBtn').addEventListener('click', function() {
        if (confirm('Are you sure you want to reset all settings to their last saved values? Any unsaved changes will be lost.')) {
            loadSystemSettings();
        }
    });
    document.getElementById('resetSettingsBtn2').addEventListener('click', function() {
        if (confirm('Are you sure you want to reset all settings to their last saved values? Any unsaved changes will be lost.')) {
            loadSystemSettings();
        }
    });
    
    // Test email button
    document.getElementById('testEmailBtn').addEventListener('click', function() {
        // Get email settings from form
        const emailSettings = {
            smtp_host: document.getElementById('smtpHost').value,
            smtp_port: document.getElementById('smtpPort').value,
            smtp_username: document.getElementById('smtpUsername').value,
            smtp_password: document.getElementById('smtpPassword').value,
            email_from_name: document.getElementById('emailFromName').value,
            email_from_address: document.getElementById('emailFromAddress').value,
            smtp_auth: document.getElementById('smtpAuth').checked,
            smtp_secure: document.getElementById('smtpSecure').checked
        };
        
        // Check if required fields are filled
        if (!emailSettings.smtp_host || !emailSettings.smtp_port || !emailSettings.email_from_address) {
            showAlert('danger', 'Please fill in all required email settings (SMTP Host, Port, and From Address) before testing.');
            return;
        }
        
        // Populate test email form with current user's email
        fetchCurrentUserEmail()
            .then(email => {
                document.getElementById('testEmailRecipient').value = email;
                
                // Show test email modal
                const testEmailModal = new bootstrap.Modal(document.getElementById('testEmailModal'));
                testEmailModal.show();
            })
            .catch(error => {
                console.error('Error fetching user email:', error);
                
                // Show test email modal without populating email
                const testEmailModal = new bootstrap.Modal(document.getElementById('testEmailModal'));
                testEmailModal.show();
            });
    });
    
    // Send test email button
    document.getElementById('sendTestEmailBtn').addEventListener('click', sendTestEmail);
});

function loadSystemSettings() {
    // Show loading state
    showLoading('Loading system settings...');
    
    fetch('/api/admin-get-settings.php')
        .then(response => response.json())
        .then(data => {
            hideLoading();
            
            if (data.success) {
                populateSettingsForm(data.settings);
            } else {
                showAlert('danger', `Error loading settings: ${data.error}`);
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            showAlert('danger', 'Failed to load system settings. Please try again.');
        });
}

function populateSettingsForm(settings) {
    // General Settings
    if (settings.site_name) document.getElementById('siteName').value = settings.site_name;
    if (settings.site_description) document.getElementById('siteDescription').value = settings.site_description;
    if (settings.contact_email) document.getElementById('contactEmail').value = settings.contact_email;
    
    if (settings.time_zone) {
        const timeZoneSelect = document.getElementById('timeZone');
        for (let i = 0; i < timeZoneSelect.options.length; i++) {
            if (timeZoneSelect.options[i].value === settings.time_zone) {
                timeZoneSelect.selectedIndex = i;
                break;
            }
        }
    }
    
    if (settings.date_format) {
        const dateFormatSelect = document.getElementById('dateFormat');
        for (let i = 0; i < dateFormatSelect.options.length; i++) {
            if (dateFormatSelect.options[i].value === settings.date_format) {
                dateFormatSelect.selectedIndex = i;
                break;
            }
        }
    }
    
    if (settings.currency) {
        const currencySelect = document.getElementById('currency');
        for (let i = 0; i < currencySelect.options.length; i++) {
            if (currencySelect.options[i].value === settings.currency) {
                currencySelect.selectedIndex = i;
                break;
            }
        }
    }
    
    // User & Registration Settings
    if (settings.hasOwnProperty('allow_registration')) {
        document.getElementById('allowRegistration').checked = settings.allow_registration;
    }
    
    if (settings.hasOwnProperty('require_email_verification')) {
        document.getElementById('requireEmailVerification').checked = settings.require_email_verification;
    }
    
    if (settings.hasOwnProperty('allow_guest_checkout')) {
        document.getElementById('allowGuestCheckout').checked = settings.allow_guest_checkout;
    }
    
    if (settings.default_user_role) {
        const roleSelect = document.getElementById('defaultUserRole');
        for (let i = 0; i < roleSelect.options.length; i++) {
            if (roleSelect.options[i].value === settings.default_user_role) {
                roleSelect.selectedIndex = i;
                break;
            }
        }
    }
    
    if (settings.min_password_length) {
        document.getElementById('minPasswordLength').value = settings.min_password_length;
    }
    
    if (settings.session_timeout) {
        document.getElementById('sessionTimeout').value = settings.session_timeout;
    }
    
    // Email Settings
    if (settings.smtp_host) document.getElementById('smtpHost').value = settings.smtp_host;
    if (settings.smtp_port) document.getElementById('smtpPort').value = settings.smtp_port;
    if (settings.smtp_username) document.getElementById('smtpUsername').value = settings.smtp_username;
    if (settings.email_from_name) document.getElementById('emailFromName').value = settings.email_from_name;
    if (settings.email_from_address) document.getElementById('emailFromAddress').value = settings.email_from_address;
    
    if (settings.hasOwnProperty('smtp_auth')) {
        document.getElementById('smtpAuth').checked = settings.smtp_auth;
    }
    
    if (settings.hasOwnProperty('smtp_secure')) {
        document.getElementById('smtpSecure').checked = settings.smtp_secure;
    }
    
    // Commission Settings
    if (settings.direct_commission_rate) {
        document.getElementById('directCommissionRate').value = settings.direct_commission_rate;
    }
    
    if (settings.override_commission_rate) {
        document.getElementById('overrideCommissionRate').value = settings.override_commission_rate;
    }
    
    if (settings.bonus_commission_threshold) {
        document.getElementById('bonusCommissionThreshold').value = settings.bonus_commission_threshold;
    }
    
    if (settings.commission_payout) {
        const payoutSelect = document.getElementById('commissionPayout');
        for (let i = 0; i < payoutSelect.options.length; i++) {
            if (payoutSelect.options[i].value === settings.commission_payout) {
                payoutSelect.selectedIndex = i;
                break;
            }
        }
    }
    
    if (settings.minimum_payout) {
        document.getElementById('minimumPayout').value = settings.minimum_payout;
    }
    
    if (settings.hasOwnProperty('auto_approve_commissions')) {
        document.getElementById('autoApproveCommissions').checked = settings.auto_approve_commissions;
    }
    
    // Advanced Settings
    if (settings.log_level) {
        const logLevelSelect = document.getElementById('logLevel');
        for (let i = 0; i < logLevelSelect.options.length; i++) {
            if (logLevelSelect.options[i].value === settings.log_level) {
                logLevelSelect.selectedIndex = i;
                break;
            }
        }
    }
    
    if (settings.maintenance_mode) {
        const maintenanceModeSelect = document.getElementById('maintenanceMode');
        for (let i = 0; i < maintenanceModeSelect.options.length; i++) {
            if (maintenanceModeSelect.options[i].value === settings.maintenance_mode) {
                maintenanceModeSelect.selectedIndex = i;
                break;
            }
        }
    }
    
    if (settings.cache_lifetime) {
        document.getElementById('cacheLifetime').value = settings.cache_lifetime;
    }
    
    if (settings.hasOwnProperty('enable_debug_mode')) {
        document.getElementById('enableDebugMode').checked = settings.enable_debug_mode;
    }
    
    if (settings.hasOwnProperty('enable_audit_log')) {
        document.getElementById('enableAuditLog').checked = settings.enable_audit_log;
    }
}

function saveSystemSettings() {
    // Collect form data
    const form = document.getElementById('systemSettingsForm');
    const formData = new FormData(form);
    
    // Convert FormData to object with correct data types
    const settings = {};
    
    formData.forEach((value, key) => {
        if (key === 'allow_registration' || 
            key === 'require_email_verification' || 
            key === 'allow_guest_checkout' || 
            key === 'smtp_auth' || 
            key === 'smtp_secure' || 
            key === 'auto_approve_commissions' || 
            key === 'enable_debug_mode' || 
            key === 'enable_audit_log') {
            // Checkboxes
            settings[key] = value === 'on';
        } else if (key === 'min_password_length' || 
                key === 'session_timeout' || 
                key === 'smtp_port' || 
                key === 'cache_lifetime') {
            // Integers
            settings[key] = parseInt(value);
        } else if (key === 'direct_commission_rate' || 
                key === 'override_commission_rate' || 
                key === 'bonus_commission_threshold' || 
                key === 'minimum_payout') {
            // Floats
            settings[key] = parseFloat(value);
        } else {
            // Strings
            settings[key] = value;
        }
    });
    
    // Show loading state
    showLoading('Saving system settings...');
    
    // Send settings to server
    fetch('/api/admin-save-settings.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(settings)
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            showAlert('success', 'System settings saved successfully!');
        } else {
            showAlert('danger', `Error saving settings: ${data.error}`);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showAlert('danger', 'Failed to save system settings. Please try again.');
    });
}

function fetchCurrentUserEmail() {
    return new Promise((resolve, reject) => {
        fetch('/api/admin-current-user.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.email) {
                    resolve(data.email);
                } else {
                    reject(new Error(data.error || 'Failed to get user email'));
                }
            })
            .catch(error => {
                reject(error);
            });
    });
}

function sendTestEmail() {
    // Get test email data
    const recipient = document.getElementById('testEmailRecipient').value;
    const subject = document.getElementById('testEmailSubject').value;
    const message = document.getElementById('testEmailMessage').value;
    
    // Validate required fields
    if (!recipient || !subject || !message) {
        document.getElementById('testEmailResult').innerHTML = `
            <div class="alert alert-danger">
                Please fill in all required fields.
            </div>
        `;
        return;
    }
    
    // Get email settings from form
    const emailSettings = {
        smtp_host: document.getElementById('smtpHost').value,
        smtp_port: document.getElementById('smtpPort').value,
        smtp_username: document.getElementById('smtpUsername').value,
        smtp_password: document.getElementById('smtpPassword').value,
        email_from_name: document.getElementById('emailFromName').value,
        email_from_address: document.getElementById('emailFromAddress').value,
        smtp_auth: document.getElementById('smtpAuth').checked,
        smtp_secure: document.getElementById('smtpSecure').checked
    };
    
    // Show loading state
    document.getElementById('testEmailResult').innerHTML = `
        <div class="alert alert-info">
            <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
            Sending test email...
        </div>
    `;
    
    document.getElementById('sendTestEmailBtn').disabled = true;
    
    // Send test email
    fetch('/api/admin-test-email.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            recipient: recipient,
            subject: subject,
            message: message,
            config: emailSettings
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('testEmailResult').innerHTML = `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    Test email sent successfully!
                </div>
            `;
        } else {
            document.getElementById('testEmailResult').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Error sending test email: ${data.error}
                </div>
            `;
        }
        
        document.getElementById('sendTestEmailBtn').disabled = false;
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('testEmailResult').innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                Failed to send test email. Please try again.
            </div>
        `;
        
        document.getElementById('sendTestEmailBtn').disabled = false;
    });
}

function showLoading(message) {
    // Create loading overlay if it doesn't exist
    let loadingOverlay = document.getElementById('loadingOverlay');
    
    if (!loadingOverlay) {
        loadingOverlay = document.createElement('div');
        loadingOverlay.id = 'loadingOverlay';
        loadingOverlay.style.position = 'fixed';
        loadingOverlay.style.top = '0';
        loadingOverlay.style.left = '0';
        loadingOverlay.style.width = '100%';
        loadingOverlay.style.height = '100%';
        loadingOverlay.style.backgroundColor = 'rgba(255, 255, 255, 0.8)';
        loadingOverlay.style.display = 'flex';
        loadingOverlay.style.justifyContent = 'center';
        loadingOverlay.style.alignItems = 'center';
        loadingOverlay.style.zIndex = '9999';
        
        const loadingContent = document.createElement('div');
        loadingContent.style.textAlign = 'center';
        
        const spinner = document.createElement('div');
        spinner.className = 'spinner-border text-primary';
        spinner.style.width = '3rem';
        spinner.style.height = '3rem';
        spinner.setAttribute('role', 'status');
        
        const spinnerText = document.createElement('span');
        spinnerText.className = 'visually-hidden';
        spinnerText.textContent = 'Loading...';
        
        const loadingMessage = document.createElement('p');
        loadingMessage.className = 'mt-3';
        loadingMessage.id = 'loadingMessage';
        loadingMessage.style.fontSize = '1.1rem';
        
        spinner.appendChild(spinnerText);
        loadingContent.appendChild(spinner);
        loadingContent.appendChild(loadingMessage);
        loadingOverlay.appendChild(loadingContent);
        
        document.body.appendChild(loadingOverlay);
    }
    
    // Update loading message
    document.getElementById('loadingMessage').textContent = message || 'Loading...';
    
    // Show loading overlay
    loadingOverlay.style.display = 'flex';
}

function hideLoading() {
    const loadingOverlay = document.getElementById('loadingOverlay');
    
    if (loadingOverlay) {
        loadingOverlay.style.display = 'none';
    }
}

function showAlert(type, message, duration = 5000) {
    // Create alerts container if it doesn't exist
    let alertsContainer = document.getElementById('alertsContainer');
    
    if (!alertsContainer) {
        alertsContainer = document.createElement('div');
        alertsContainer.id = 'alertsContainer';
        alertsContainer.style.position = 'fixed';
        alertsContainer.style.top = '20px';
        alertsContainer.style.right = '20px';
        alertsContainer.style.zIndex = '9999';
        document.body.appendChild(alertsContainer);
    }
    
    // Create alert element
    const alertId = 'alert-' + Date.now();
    const alertElement = document.createElement('div');
    alertElement.id = alertId;
    alertElement.className = `alert alert-${type} alert-dismissible fade show`;
    alertElement.role = 'alert';
    alertElement.style.minWidth = '300px';
    alertElement.style.marginBottom = '10px';
    
    alertElement.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Add alert to container
    alertsContainer.appendChild(alertElement);
    
    // Auto-dismiss after duration
    setTimeout(() => {
        const alert = document.getElementById(alertId);
        if (alert) {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }
    }, duration);
}
</script>

<?php
// Include admin footer
require_once __DIR__ . '/../includes/admin_footer.php';
?>