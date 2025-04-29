    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/dashboard.js"></script>
    
    <script>
    // Mobile sidebar toggle
    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.getElementById('menuToggle');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.sidebar');
        
        if (menuToggle) {
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('show');
            });
        }
        
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('show');
            });
        }
        
        // Copy affiliate link button functionality
        const copyLinkBtn = document.getElementById('copyLinkBtn');
        const affiliateLink = document.getElementById('affiliateLink');
        
        if (copyLinkBtn && affiliateLink) {
            copyLinkBtn.addEventListener('click', function() {
                affiliateLink.select();
                document.execCommand('copy');
                showAlert('success', 'Affiliate link copied to clipboard!');
            });
        }
    });

    // Show alert function
    function showAlert(type, message, duration = 3000) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        const flashContainer = document.querySelector('.flash-container');
        if (!flashContainer) {
            const newFlashContainer = document.createElement('div');
            newFlashContainer.className = 'flash-container';
            document.body.appendChild(newFlashContainer);
            newFlashContainer.appendChild(alertDiv);
        } else {
            flashContainer.appendChild(alertDiv);
        }
        
        setTimeout(() => {
            alertDiv.classList.remove('show');
            setTimeout(() => alertDiv.remove(), 300);
        }, duration);
    }
    </script>
</body>
</html>