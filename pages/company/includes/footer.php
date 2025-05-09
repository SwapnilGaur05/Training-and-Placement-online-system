            </main>
        </div>
    </div>

    <script>
    // Auto-hide alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize any Bootstrap components
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        var dropdownTriggerList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
        var dropdownList = dropdownTriggerList.map(function (dropdownToggleEl) {
            return new bootstrap.Dropdown(dropdownToggleEl);
        });
        
        // Auto-hide alerts
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Toggle mobile sidebar
        var sidebarToggle = document.querySelector('.navbar-toggler');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                document.querySelector('#sidebar').classList.toggle('show');
            });
        }
    });
    </script>
</body>
</html> 