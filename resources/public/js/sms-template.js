// ============================================================
//  SMS TEMPLATE JAVASCRIPT FUNCTIONALITY
//  All the interactive behaviour for SMS template pages.
//
//  SECTIONS (use Ctrl+F to jump):
//    1. SIDEBAR TOGGLE
//    2. SIDEBAR DROPDOWNS
//    3. MODAL HELPERS
//    4. TOAST NOTIFICATIONS
//    5. NOTIFICATION PANEL
//    6. FORM VALIDATION
//    7. BULK SELECTION
//    8. CRUD OPERATIONS
//    9. USER DROPDOWN
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    
// ============================================================
//  1. SIDEBAR TOGGLE
//  Clicking the hamburger icon collapses/expands the sidebar.
// ============================================================
const hamburgerBtn   = document.getElementById('hamburgerBtn');
const sidebar        = document.getElementById('sidebar');
const sidebarOverlay = document.getElementById('sidebarOverlay');

// On mobile (<= 900px) the sidebar is collapsed by default
if (window.innerWidth <= 900) {
    sidebar?.classList.add('collapsed');
}

if (hamburgerBtn && sidebar) {
    hamburgerBtn.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        if (sidebarOverlay) {
            sidebarOverlay.classList.toggle('active', !sidebar.classList.contains('collapsed'));
        }
    });
}

// Tap the overlay to close sidebar on mobile
if (sidebarOverlay && sidebar) {
    sidebarOverlay.addEventListener('click', () => {
        sidebar.classList.add('collapsed');
        sidebarOverlay.classList.remove('active');
    });
}


// ============================================================
//  2. SIDEBAR DROPDOWNS
//  Each nav item expands a dropdown when clicked.
// ============================================================
document.querySelectorAll('.dropdown-trigger').forEach(button => {
    button.addEventListener('click', function () {
        const targetMenu = document.getElementById(this.dataset.target);
        const isOpen     = targetMenu?.classList.contains('open');

        // Close all open dropdowns first
        document.querySelectorAll('.dropdown-menu.open').forEach(m => m.classList.remove('open'));
        document.querySelectorAll('.sidebar-item.open').forEach(b => b.classList.remove('open'));

        // Then open the clicked one (if it was closed)
        if (targetMenu && !isOpen) {
            targetMenu.classList.add('open');
            this.classList.add('open');
        }
    });
});


// ============================================================
//  3. MODAL HELPERS
//  openModal / closeModal show or hide a popup dialog.
//  Clicking outside the modal box or the × button also closes it.
// ============================================================

// Opens a modal by its HTML id
window.openModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
    }
};

// Closes a modal by its HTML id
window.closeModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }
};

// Listen for any click on a [data-close] button or the overlay background
document.addEventListener('click', event => {
    const closeButton = event.target.closest('[data-close]');
    if (closeButton) {
        closeModal(closeButton.dataset.close);
        return;
    }
    // Click directly on the dark overlay (not the modal box)
    if (event.target.classList.contains('modal-overlay')) {
        event.target.classList.remove('active');
    }
});


// ============================================================
//  4. TOAST NOTIFICATIONS
//  Shows a card-style popup in the top-right corner.
//
//  Types:
//    'success'  → green  "Submitted"
//    'updated'  → blue   "Updated"
//    'warning'  → yellow "Warning"
//    'error'    → red    "Error"
//
//  Usage: showToast('Your message here', 'success')
// ============================================================

// Maps each type to the bold label shown at the top of the card
const TOAST_LABELS = {
    success : 'Submitted',
    updated : 'Updated',
    warning : 'Warning',
    error   : 'Error'
};

window.showToast = function(message, type = 'success') {
    // Create the toast element once and reuse it
    let toast = document.querySelector('.toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.className = 'toast';
        toast.innerHTML = `
            <div class="toast-label">
                <span class="toast-dot"></span>
                <span class="toast-title"></span>
            </div>
            <div class="toast-msg"></div>`;
        document.body.appendChild(toast);
    }

    // Set the label and message
    toast.querySelector('.toast-title').textContent = TOAST_LABELS[type] ?? type;
    toast.querySelector('.toast-msg').textContent   = message;

    // Swap the colour class
    toast.className = `toast ${type}`;

    // Trigger animation (force reflow so class change restarts transition)
    void toast.offsetWidth;
    toast.classList.add('show');

    // Auto-hide after 3.5 seconds
    clearTimeout(toast._hideTimer);
    toast._hideTimer = setTimeout(() => toast.classList.remove('show'), 3500);
};

// ── Save a toast to show AFTER a page reload ─────────────────
// Because location.reload() happens before the toast is visible,
// we store the message in sessionStorage and read it back on load.
window.reloadWithToast = function(message, type) {
    sessionStorage.setItem('pending_toast', JSON.stringify({ message, type }));
    location.reload();
};

// On every page load: check if there's a pending toast and show it
const pending = sessionStorage.getItem('pending_toast');
if (pending) {
    sessionStorage.removeItem('pending_toast');
    const { message, type } = JSON.parse(pending);
    // Small delay so the DOM is fully painted before the toast appears
    setTimeout(() => showToast(message, type), 150);
}

// Remove existing toasts from session messages after delay
const sessionToasts = document.querySelectorAll('.toast.show');
sessionToasts.forEach(toast => {
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 5000);
});


// ============================================================
//  5. NOTIFICATION PANEL
//  Sidebar bell icon toggles the notification panel.
//  Clicking an unread item or "Mark all as read" clears the badge.
// ============================================================
const bellBtn      = document.getElementById('bellBtn');
const bellBadge    = document.getElementById('bellBadge');
const notifPanel   = document.getElementById('notifPanel');
const notifOverlay = document.getElementById('notifOverlay');
const notifClose   = document.getElementById('notifClose');
const notifMarkAll = document.getElementById('notifMarkAll');

window.openNotifPanel = function() {
    notifPanel?.classList.add('active');
    notifOverlay?.classList.add('active');
};

window.closeNotifPanel = function() {
    notifPanel?.classList.remove('active');
    notifOverlay?.classList.remove('active');
};

window.updateBellBadge = function() {
    const hasUnread = document.querySelector('#notifList .notif-item.unread');
    bellBadge?.classList.toggle('has-notif', !!hasUnread);
};

// Set initial badge state on page load
updateBellBadge();

bellBtn?.addEventListener('click', (e) => {
    e.stopPropagation();
    const isOpen = notifPanel?.classList.contains('active');
    isOpen ? closeNotifPanel() : openNotifPanel();
});

// Close when clicking the × button or the overlay
notifClose?.addEventListener('click', closeNotifPanel);
notifOverlay?.addEventListener('click', closeNotifPanel);

// Mark individual item as read on click
document.getElementById('notifList')?.addEventListener('click', (e) => {
    const item = e.target.closest('.notif-item');
    if (item) {
        item.classList.remove('unread');
        updateBellBadge();
    }
});

// Mark all as read
notifMarkAll?.addEventListener('click', () => {
    document.querySelectorAll('#notifList .notif-item.unread')
        .forEach(el => el.classList.remove('unread'));
    updateBellBadge();
});


// ============================================================
//  6. FORM VALIDATION
//  Shared form validation and live input clearing
// ============================================================

// Pass a <form> element and a map of { inputName: 'Label text' }.
// Returns true if all fields are filled, false otherwise.
// Also highlights empty fields red and shows an error message.
window.validateForm = function(form, requiredFields) {
    let valid = true;

    Object.entries(requiredFields).forEach(([name, label]) => {
        const input = form.querySelector(`[name="${name}"]`);
        if (!input) return;

        const field = input.closest('.form-field');
        const value = input.value.trim();

        // Ensure the error <span> exists below the input
        let errorEl = field?.querySelector('.field-error');
        if (field && !errorEl) {
            errorEl = document.createElement('span');
            errorEl.className = 'field-error';
            field.appendChild(errorEl);
        }

        if (!value) {
            input.classList.add('input-error');
            field?.classList.add('has-error');
            if (errorEl) errorEl.textContent = `${label} is required.`;
            valid = false;
        } else {
            input.classList.remove('input-error');
            field?.classList.remove('has-error');
            if (errorEl) errorEl.textContent = '';
        }
    });

    return valid;
};

// Live input listeners — clears error as soon as user types
window.attachLiveValidation = function(form) {
    form.querySelectorAll('input, select').forEach(input => {
        // Blue ring on focus
        input.addEventListener('focus', () => {
            if (!input.classList.contains('input-error')) {
                input.style.borderColor = '#2563eb';
            }
        });

        // Reset border on blur (CSS handles :focus, this covers the gap)
        input.addEventListener('blur', () => {
            if (!input.classList.contains('input-error')) {
                input.style.borderColor = '';
            }
        });

        // Clear error the moment the user starts typing / changing
        input.addEventListener('input', () => clearFieldError(input));
        input.addEventListener('change', () => clearFieldError(input));
    });
};

window.clearFieldError = function(input) {
    if (input.value.trim()) {
        input.classList.remove('input-error');
        const field = input.closest('.form-field');
        field?.classList.remove('has-error');
        const errorEl = field?.querySelector('.field-error');
        if (errorEl) errorEl.textContent = '';
    }
};


// ============================================================
//  7. BULK SELECTION
//  The "Select All" checkbox and individual row checkboxes.
//  When rows are checked, the bulk action toolbar appears.
// ============================================================
const checkAllBox  = document.getElementById('checkAll');
const bulkToolbar  = document.getElementById('bulkToolbar');
const bulkCountEl  = document.getElementById('bulkCount');

// Returns an array of IDs for all checked rows
window.getCheckedIds = function() {
    return [...document.querySelectorAll('.row-check:checked')].map(cb => cb.value);
};

// Shows/hides the bulk toolbar and updates the "N selected" text
window.updateBulkToolbar = function() {
    const checkedIds  = getCheckedIds();
    const allCheckboxes = document.querySelectorAll('.row-check');

    if (checkedIds.length > 0 && bulkCountEl) {
        bulkCountEl.textContent = `${checkedIds.length} selected`;
        bulkToolbar?.classList.add('visible');
    } else {
        bulkToolbar?.classList.remove('visible');
    }

    // Keep the "select all" checkbox in sync
    if (checkAllBox) {
        checkAllBox.indeterminate = checkedIds.length > 0 && checkedIds.length < allCheckboxes.length;
        checkAllBox.checked       = allCheckboxes.length > 0 && checkedIds.length === allCheckboxes.length;
    }
};

// "Select All" checkbox: check or uncheck every row
if (checkAllBox) {
    checkAllBox.addEventListener('change', function () {
        document.querySelectorAll('.row-check').forEach(cb => { cb.checked = this.checked; });
        updateBulkToolbar();
    });
}

// Individual row checkboxes — use event delegation on the tbody
document.getElementById('crudTbody')?.addEventListener('change', event => {
    if (event.target.classList.contains('row-check')) {
        updateBulkToolbar();
    }
});

// ── Bulk DELETE ──────────────────────────────────────────────
document.getElementById('btnBulkDelete')?.addEventListener('click', () => {
    const ids = getCheckedIds();
    if (!ids.length) return;
    document.getElementById('bulkDeleteCount').textContent = ids.length;
    openModal('bulkDeleteModal');
});

document.getElementById('btnConfirmBulkDelete')?.addEventListener('click', () => {
    const ids      = getCheckedIds();
    const formData = new FormData();
    formData.set('action', 'bulk_delete');
    formData.set('ids', ids.join(','));

    // Laravel API endpoint for bulk delete
    fetch('/api/v1/bulk-delete', { 
        method: 'POST', 
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        }
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                closeModal('bulkDeleteModal');
                reloadWithToast(data.message, 'warning');
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(() => showToast('Request failed.', 'error'));
});

// ── Bulk SET ACTIVE ──────────────────────────────────────────
document.getElementById('btnBulkActive')?.addEventListener('click', () => {
    const ids = getCheckedIds();
    if (!ids.length) return;
    const formData = new FormData();
    formData.set('action', 'bulk_status');
    formData.set('ids', ids.join(','));
    formData.set('status', 'Active');

    // Laravel API endpoint for bulk status
    fetch('/api/v1/bulk-status', { 
        method: 'POST', 
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        }
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) reloadWithToast(data.message, 'updated');
            else showToast(data.message, 'error');
        })
        .catch(() => showToast('Request failed.', 'error'));
});

// ── Bulk SET INACTIVE ────────────────────────────────────────
document.getElementById('btnBulkInactive')?.addEventListener('click', () => {
    const ids = getCheckedIds();
    if (!ids.length) return;
    const formData = new FormData();
    formData.set('action', 'bulk_status');
    formData.set('ids', ids.join(','));
    formData.set('status', 'Inactive');

    // Laravel API endpoint for bulk status
    fetch('/api/v1/bulk-status', { 
        method: 'POST', 
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        }
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) reloadWithToast(data.message, 'updated');
            else showToast(data.message, 'error');
        })
        .catch(() => showToast('Request failed.', 'error'));
});


// ============================================================
//  8. CRUD OPERATIONS
//  View, Add, Edit, Delete functionality for data tables
// ============================================================

// Required fields for the Add/Edit modal form
const modalRequired = {
    first_name : 'First Name',
    last_name  : 'Last Name',
    birthday   : 'Birthday',
    course     : 'Course',
    year_level : 'Year Level',
    section    : 'Section',
    phone      : 'Phone'
};

// ── VIEW MODAL ────────────────────────────────────────────────
document.querySelectorAll('.btn-view').forEach(button => {
    button.addEventListener('click', function () {
        const studentId = this.dataset.id;

        // Laravel API endpoint for getting single record
        fetch(`/api/v1/students/${studentId}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token') || ''}`,
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) { showToast(data.message, 'error'); return; }

                const s = data.student;
                // Fill in the modal fields
                document.getElementById('vName').textContent    = `${s.first_name} ${s.last_name}`;
                document.getElementById('vBirthday').textContent = s.birthday;
                document.getElementById('vPhone').textContent   = s.phone;
                document.getElementById('vCourse').textContent  = s.course;
                document.getElementById('vYear').textContent    = s.year_level;
                document.getElementById('vSection').textContent = s.section;

                openModal('viewModal');
            })
            .catch(() => showToast('Failed to load student.', 'error'));
    });
});

// ── ADD MODAL ─────────────────────────────────────────────────
document.getElementById('btnAddStudent')?.addEventListener('click', () => {
    const crudForm = document.getElementById('studentCrudForm');
    document.getElementById('formModalTitle').textContent = 'Add Student';
    crudForm.reset();
    // Clear any leftover error states from a previous open
    crudForm.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
    crudForm.querySelectorAll('.form-field.has-error').forEach(el => el.classList.remove('has-error'));
    crudForm.querySelectorAll('.field-error').forEach(el => { el.textContent = ''; });
    document.getElementById('crudId').value     = '';
    document.getElementById('crudAction').value = 'add';
    attachLiveValidation(crudForm);
    openModal('formModal');
});

// ── EDIT MODAL ────────────────────────────────────────────────
document.querySelectorAll('.btn-edit').forEach(button => {
    button.addEventListener('click', function () {
        const studentId = this.dataset.id;

        // Laravel API endpoint for getting single record
        fetch(`/api/v1/students/${studentId}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token') || ''}`,
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) { showToast(data.message, 'error'); return; }

                const s        = data.student;
                const crudForm = document.getElementById('studentCrudForm');

                // Set the hidden fields so the API knows this is an edit
                document.getElementById('formModalTitle').textContent = 'Edit Student';
                document.getElementById('crudId').value     = s.id;
                document.getElementById('crudAction').value = 'edit';

                // Pre-fill each form field with the student's current values
                document.getElementById('cFirst').value   = s.first_name;
                document.getElementById('cLast').value    = s.last_name;
                document.getElementById('cBday').value    = s.birthday;
                document.getElementById('cCourse').value  = s.course;
                document.getElementById('cYear').value    = s.year_level;
                document.getElementById('cSection').value = s.section;
                document.getElementById('cPhone').value   = s.phone;
                document.getElementById('cStatus').value  = s.status;

                // Clear any leftover error states
                crudForm.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
                crudForm.querySelectorAll('.form-field.has-error').forEach(el => el.classList.remove('has-error'));
                crudForm.querySelectorAll('.field-error').forEach(el => { el.textContent = ''; });

                attachLiveValidation(crudForm);
                openModal('formModal');
            })
            .catch(() => showToast('Failed to load student.', 'error'));
    });
});

// The Submit button inside the Add/Edit modal sends the form data
document.getElementById('btnCrudSubmit')?.addEventListener('click', () => {
    const crudForm = document.getElementById('studentCrudForm');
    const isEdit   = document.getElementById('crudAction').value === 'edit';

    if (!validateForm(crudForm, modalRequired)) return; // stop if invalid

    const formData = new FormData(crudForm);

    // Laravel API endpoint for CRUD operations
    const action = document.getElementById('crudAction').value;
    const id = document.getElementById('crudId').value;
    const url = action === 'edit' ? `/api/v1/students/${id}` : '/api/v1/students';
    const method = action === 'edit' ? 'PUT' : 'POST';
    
    fetch(url, { 
        method: method, 
        body: formData,
        headers: {
            'Authorization': `Bearer ${localStorage.getItem('token') || ''}`,
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeModal('formModal');
                reloadWithToast(data.message, isEdit ? 'updated' : 'success');
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(() => showToast('Request failed.', 'error'));
});

// ── DELETE (single student) ───────────────────────────────────
let pendingDeleteId = null; // stores the ID of the student to delete

document.querySelectorAll('.btn-delete').forEach(button => {
    button.addEventListener('click', function () {
        pendingDeleteId = this.dataset.id;
        document.getElementById('deleteStudentName').textContent = this.dataset.name;
        openModal('deleteModal');
    });
});

document.getElementById('btnConfirmDelete')?.addEventListener('click', () => {
    if (!pendingDeleteId) return;

    const formData = new FormData();
    formData.set('action', 'delete');
    formData.set('id', pendingDeleteId);

    // Laravel API endpoint for delete operation
    fetch(`/api/v1/students/${pendingDeleteId}`, { 
        method: 'DELETE',
        headers: {
            'Authorization': `Bearer ${localStorage.getItem('token') || ''}`,
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeModal('deleteModal');
                reloadWithToast(data.message, 'warning');
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(() => showToast('Request failed.', 'error'));
});


// ============================================================
//  9. USER DROPDOWN
//  Toggle user menu dropdown
// ============================================================
const avatarBtn = document.getElementById('avatarBtn');
const userMenu = document.getElementById('userMenu');

if (avatarBtn && userMenu) {
    avatarBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        userMenu.classList.toggle('active');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!avatarBtn.contains(e.target) && !userMenu.contains(e.target)) {
            userMenu.classList.remove('active');
        }
    });

    // Close dropdown on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && userMenu.classList.contains('active')) {
            userMenu.classList.remove('active');
        }
    });
}
});