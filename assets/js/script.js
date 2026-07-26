document.addEventListener("DOMContentLoaded", function () {
    // Sidebar Toggle
    const sidebarCollapse = document.getElementById("sidebarCollapse");
    const sidebarCollapseBtn = document.getElementById("sidebarCollapseBtn");
    const sidebar = document.getElementById("sidebar");

    const sidebarOverlay = document.getElementById("sidebarOverlay");

    function toggleSidebar() {
        sidebar.classList.toggle("show");
        if (sidebarOverlay) sidebarOverlay.classList.toggle("show");
    }

    if (sidebarCollapse && sidebar) {
        sidebarCollapse.addEventListener("click", toggleSidebar);
    }

    if (sidebarCollapseBtn && sidebar) {
        sidebarCollapseBtn.addEventListener("click", toggleSidebar);
    }
    
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener("click", toggleSidebar);
    }

    // Initialize Toasts
    const toastElList = [].slice.call(document.querySelectorAll('.toast'));
    const toastList = toastElList.map(function (toastEl) {
        return new bootstrap.Toast(toastEl, {
            autohide: true,
            delay: 5000
        }).show();
    });

    // Delete Confirmation Modal Setup
    const deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const url = button.getAttribute('data-url');
            
            const form = deleteModal.querySelector('#deleteForm');
            const inputId = deleteModal.querySelector('#deleteId');
            
            form.action = url;
            inputId.value = id;
        });
    }

    // Show/Hide Password (supports multiple)
    const togglePasswords = document.querySelectorAll('.password-toggle-btn');
    togglePasswords.forEach(toggleBtn => {
        toggleBtn.addEventListener('click', function (e) {
            const inputGroup = this.closest('.input-group');
            if (inputGroup) {
                const passwordInput = inputGroup.querySelector('input');
                const icon = this.querySelector('i');
                if (passwordInput && icon) {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    icon.classList.toggle('bi-eye');
                    icon.classList.toggle('bi-eye-slash');
                }
            }
        });
    });

    // Character counter for textarea
    const textareas = document.querySelectorAll('textarea[maxlength]');
    textareas.forEach(textarea => {
        const wrapper = document.createElement('div');
        wrapper.className = 'text-end text-muted small mt-1';
        const maxLength = textarea.getAttribute('maxlength');
        wrapper.innerText = `0 / ${maxLength}`;
        textarea.parentNode.insertBefore(wrapper, textarea.nextSibling);

        textarea.addEventListener('input', function() {
            wrapper.innerText = `${this.value.length} / ${maxLength}`;
        });
        
        // Initial set if prefilled
        if(textarea.value.length > 0) {
            wrapper.innerText = `${textarea.value.length} / ${maxLength}`;
        }
    });

    // Simple loading state on forms
    const forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            // Custom password matching validation
            const pwd = form.querySelector('input[name="password"]') || form.querySelector('input[name="new_password"]');
            const confirmPwd = form.querySelector('input[name="confirm_password"]');
            
            if (pwd && confirmPwd) {
                if (pwd.value !== confirmPwd.value) {
                    confirmPwd.setCustomValidity("Passwords do not match");
                } else {
                    confirmPwd.setCustomValidity("");
                }
            }

            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            } else {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Loading...';
                    submitBtn.disabled = true;
                }
            }
            form.classList.add('was-validated');
        }, false);
    });
});
