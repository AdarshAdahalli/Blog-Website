document.addEventListener('DOMContentLoaded', function () {
    // Auto-dismiss alert notifications after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            } else {
                alert.remove();
            }
        }, 5000);
    });

    // Initialize tooltips
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // Fail-safe Vanilla JS Dropdown Toggle for User Account Button
    const dropdownToggleBtn = document.getElementById('dropdownUserMenu');
    if (dropdownToggleBtn) {
        dropdownToggleBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const dropdownContainer = document.getElementById('userAccountDropdown');
            const menu = dropdownContainer ? dropdownContainer.querySelector('.dropdown-menu') : null;
            if (menu) {
                menu.classList.toggle('show');
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function (e) {
            const dropdownContainer = document.getElementById('userAccountDropdown');
            if (dropdownContainer && !dropdownContainer.contains(e.target)) {
                const menu = dropdownContainer.querySelector('.dropdown-menu');
                if (menu) {
                    menu.classList.remove('show');
                }
            }
        });
    }
});
