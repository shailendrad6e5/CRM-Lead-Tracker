document.addEventListener("DOMContentLoaded", function () {
    // ── Sidebar Toggle ────────────────────────────────────────────────────
    const sidebarCollapse    = document.getElementById("sidebarCollapse");
    const sidebarCollapseBtn = document.getElementById("sidebarCollapseBtn");
    const sidebar            = document.getElementById("sidebar");
    const sidebarOverlay     = document.getElementById("sidebarOverlay");

    function toggleSidebar() {
        sidebar.classList.toggle("show");
        if (sidebarOverlay) sidebarOverlay.classList.toggle("show");
    }

    if (sidebarCollapse    && sidebar) sidebarCollapse.addEventListener("click", toggleSidebar);
    if (sidebarCollapseBtn && sidebar) sidebarCollapseBtn.addEventListener("click", toggleSidebar);
    if (sidebarOverlay) sidebarOverlay.addEventListener("click", toggleSidebar);

    // ── Initialize Toasts ─────────────────────────────────────────────────
    const toastElList = [].slice.call(document.querySelectorAll('.toast'));
    toastElList.map(toastEl => new bootstrap.Toast(toastEl, { autohide: true, delay: 5000 }).show());

    // ── Delete Modal ──────────────────────────────────────────────────────
    const deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (event) {
            const button  = event.relatedTarget;
            const id      = button.getAttribute('data-id');
            const url     = button.getAttribute('data-url');
            deleteModal.querySelector('#deleteForm').action = url;
            deleteModal.querySelector('#deleteId').value    = id;
        });
    }

    // ── Password Eye Toggle ───────────────────────────────────────────────
    document.querySelectorAll('.password-toggle-btn').forEach(toggleBtn => {
        toggleBtn.addEventListener('click', function () {
            const inputGroup    = this.closest('.input-group');
            const passwordInput = inputGroup?.querySelector('input');
            const icon          = this.querySelector('i');
            if (passwordInput && icon) {
                const isPassword = passwordInput.getAttribute('type') === 'password';
                passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                icon.classList.toggle('bi-eye');
                icon.classList.toggle('bi-eye-slash');
            }
        });
    });

    // ── Textarea Character Counter ────────────────────────────────────────
    document.querySelectorAll('textarea[maxlength]').forEach(textarea => {
        const wrapper = document.createElement('div');
        wrapper.className = 'text-end text-muted small mt-1';
        const maxLength = textarea.getAttribute('maxlength');
        wrapper.innerText = `${textarea.value.length} / ${maxLength}`;
        textarea.parentNode.insertBefore(wrapper, textarea.nextSibling);
        textarea.addEventListener('input', () => {
            wrapper.innerText = `${textarea.value.length} / ${maxLength}`;
        });
    });

    // ── Form Validation & Loading State ──────────────────────────────────
    Array.prototype.slice.call(document.querySelectorAll('.needs-validation')).forEach(form => {
        form.addEventListener('submit', function (event) {
            // Password match validation
            const pwd        = form.querySelector('input[name="password"]') || form.querySelector('input[name="new_password"]');
            const confirmPwd = form.querySelector('input[name="confirm_password"]');
            if (pwd && confirmPwd) {
                confirmPwd.setCustomValidity(pwd.value !== confirmPwd.value ? "Passwords do not match" : "");
            }

            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            } else {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn && !submitBtn.dataset.nospinner) {
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Loading...';
                    submitBtn.disabled  = true;
                }
            }
            form.classList.add('was-validated');
        }, false);
    });

    // ── Bulk Actions (Leads List) ─────────────────────────────────────────
    const selectAll     = document.getElementById('selectAll');
    const bulkBar       = document.getElementById('bulkActionBar');
    const bulkCount     = document.getElementById('bulkCount');
    const bulkCancelBtn = document.getElementById('bulkCancelBtn');

    function updateBulkBar() {
        const checked = document.querySelectorAll('.lead-checkbox:checked');
        if (!bulkBar) return;
        if (checked.length > 0) {
            bulkBar.classList.remove('d-none');
            bulkBar.classList.add('d-flex');
            bulkCount.textContent = `${checked.length} selected`;
        } else {
            bulkBar.classList.add('d-none');
            bulkBar.classList.remove('d-flex');
            if (selectAll) selectAll.checked = false;
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.lead-checkbox').forEach(cb => cb.checked = this.checked);
            updateBulkBar();
        });
    }

    document.querySelectorAll('.lead-checkbox').forEach(cb => {
        cb.addEventListener('change', updateBulkBar);
    });

    if (bulkCancelBtn) {
        bulkCancelBtn.addEventListener('click', () => {
            document.querySelectorAll('.lead-checkbox').forEach(cb => cb.checked = false);
            if (selectAll) selectAll.checked = false;
            updateBulkBar();
        });
    }
});

// Bulk action submit (global because called via onclick)
function doBulkAction(type) {
    const form          = document.getElementById('bulkForm');
    const actionInput   = document.getElementById('bulkActionInput');
    const statusSelect  = document.getElementById('bulkStatusSelect');
    const checked       = document.querySelectorAll('.lead-checkbox:checked');

    if (checked.length === 0) { alert('Please select at least one lead.'); return; }

    if (type === 'delete') {
        if (!confirm(`Are you sure you want to delete ${checked.length} lead(s)? This cannot be undone.`)) return;
        actionInput.value = 'delete';
    } else if (type === 'status') {
        const status = statusSelect.value;
        if (!status) { alert('Please select a status to apply.'); return; }
        actionInput.value = status;
    } else if (type === 'assign') {
        const assignSelect = document.getElementById('bulkAssignSelect');
        const assignValue = assignSelect ? assignSelect.value : '';
        if (!assignValue) { alert('Please select a team member to assign to.'); return; }
        actionInput.value = assignValue;
    }
    form.submit();
}
