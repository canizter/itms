    </div> <!-- End container -->

    <!-- Footer -->
    <footer style="background-color: #f8f9fa; border-top: 1px solid #e9ecef; margin-top: 3rem; padding: 2rem 0; text-align: center; color: #6c757d;">
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 1rem;">
                <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> v1.6.1. All rights reserved.</p>
            <p style="font-size: 0.9rem; margin-top: 0.5rem;">
                Developed for efficient IT asset management<br>
                <strong>Author:</strong> Jasper Sevilla &nbsp;|&nbsp; <strong>AI Assistant:</strong> GitHub Copilot<br>
                <a href="http://help.linc-fs.com" target="_blank" rel="noopener" style="color: #667eea; text-decoration: none;">Support</a>
            </p>
        </div>
    </footer>

    <script src="assets/js/app.js"></script>
    <script src="assets/js/app.js"></script>
    <!-- Additional page-specific JavaScript can be added here -->
    <?php if (isset($additional_js)): ?>
        <?php echo $additional_js; ?>
    <?php endif; ?>
    <script>
        // Initialize page-specific functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 0.5s ease';
                    setTimeout(() => {
                        if (alert.parentNode) {
                            alert.parentNode.removeChild(alert);
                        }
                    }, 500);
                }, 5000);
            });
            
            // Initialize data tables if present
            const tables = document.querySelectorAll('.data-table');
            tables.forEach(table => {
                // Add sorting functionality
                const headers = table.querySelectorAll('th[data-sort]');
                headers.forEach(header => {
                    header.style.cursor = 'pointer';
                    header.addEventListener('click', function() {
                        const column = this.getAttribute('data-sort');
                        const currentOrder = this.getAttribute('data-order') || 'asc';
                        const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
                        
                        // Update URL with sort parameters
                        const url = new URL(window.location);
                        url.searchParams.set('sort', column);
                        url.searchParams.set('order', newOrder);
                        window.location.href = url.toString();
                    });
                });
            });
            
            // Initialize search with Enter key
            const searchInputs = document.querySelectorAll('.search-input');
            searchInputs.forEach(input => {
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const form = this.closest('form');
                        if (form) {
                            form.submit();
                        }
                    }
                });
            });
        });
        
        // Global utility functions
        window.confirmDelete = function(itemName) {
            return confirm(`Are you sure you want to delete "${itemName}"?\n\nThis action cannot be undone.`);
        };
        
        window.printPage = function() {
            window.print();
        };
        
        window.exportData = function(format) {
            const url = new URL(window.location);
            url.searchParams.set('export', format);
            window.location.href = url.toString();
        };
    </script>
</body>
</html>
