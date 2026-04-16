// ANU Lost and Found JavaScript Functions

// Image preview functionality
function initImagePreview() {
    const imageInputs = document.querySelectorAll('input[type="file"][accept="image/*"]');
    
    imageInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewContainer = input.closest('.image-preview');
                    if (previewContainer) {
                        previewContainer.innerHTML = `<img src="${e.target.result}" alt="Preview" style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px;">
                                                    <button type="button" class="remove-image btn btn-sm btn-danger" onclick="removeImage(this)">
                                                        <i class="bi bi-x"></i>
                                                    </button>`;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    });
}

// Remove image functionality
function removeImage(button) {
    const input = button.closest('.image-preview').querySelector('input[type="file"]');
    if (input) {
        input.value = '';
        button.closest('.image-preview').innerHTML = `<i class="bi bi-image"></i>`;
    }
}

// Form validation
function validateReportForm() {
    const form = document.querySelector('form[action="actions/process_report.php"]');
    if (!form) return true;

    const title = form.querySelector('input[name="title"]');
    const type = form.querySelector('select[name="type"]');
    const category = form.querySelector('select[name="category"]');
    const description = form.querySelector('textarea[name="description"]');
    const location = form.querySelector('input[name="location"]');

    if (!title.value.trim() || !type.value || !category.value || !description.value.trim() || !location.value.trim()) {
        showAlert('All fields are required', 'danger');
        return false;
    }

    const image = form.querySelector('input[name="image"]');
    if (image && image.files[0]) {
        const fileSize = image.files[0].size;
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

        if (fileSize > 2 * 1024 * 1024) {
            showAlert('Image size must not exceed 2MB', 'danger');
            return false;
        }

        if (!allowedTypes.includes(image.files[0].type)) {
            showAlert('Invalid image format. Only JPG, PNG, and GIF are allowed.', 'danger');
            return false;
        }
    }

    return true;
}

// Show alert messages
function showAlert(message, type = 'info') {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    const container = document.querySelector('.container-fluid');
    if (container) {
        container.insertBefore(alert, container.firstChild);
        setTimeout(() => alert.remove(), 5000);
    }
}

// Initialize tooltips
function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Initialize popovers
function initPopovers() {
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
}

// Auto-hide alerts
function initAutoHideAlerts() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
}

// Initialize all functionality
document.addEventListener('DOMContentLoaded', function() {
    initImagePreview();
    initTooltips();
    initPopovers();
    initAutoHideAlerts();

    // Handle notification link clicks to update counter dynamically
    const notificationLinks = document.querySelectorAll('.notification-item-link');
    notificationLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Extract the notification ID from the href
            const href = this.getAttribute('href');
            const url = new URL(href, window.location.origin);
            const notificationId = url.searchParams.get('id');
            
            if (notificationId) {
                // Decrement the counter if it exists
                const badge = document.getElementById('notificationBadge');
                if (badge) {
                    let count = parseInt(badge.textContent);
                    count--;
                    if (count > 0) {
                        badge.textContent = count;
                    } else {
                        // Remove badge if count is 0
                        badge.remove();
                    }
                }
            }
        });
    });

    // Add form validation to report form
    const reportForm = document.querySelector('form[action="actions/process_report.php"]');
    if (reportForm) {
        reportForm.addEventListener('submit', function(e) {
            if (!validateReportForm()) {
                e.preventDefault();
            }
        });
    }
});

// AJAX search functionality
function searchItems(query) {
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.value = query;
        const searchForm = searchInput.closest('form');
        if (searchForm) {
            searchForm.submit();
        }
    }
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + K for search
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.focus();
        }
    }
});

// Add loading states
function showLoading(element) {
    const loadingHtml = `<div class="spinner-border spinner-border-sm me-2" role="status"><span class="visually-hidden">Loading...</span></div>`;
    element.innerHTML = loadingHtml + element.innerHTML;
    element.disabled = true;
}

function hideLoading(element) {
    const spinner = element.querySelector('.spinner-border');
    if (spinner) {
        spinner.remove();
        element.disabled = false;
    }
}

// Auto-save form drafts
function initAutoSave() {
    const form = document.querySelector('form[action="actions/process_report.php"]');
    if (!form) return;

    let saveTimer;
    const formData = {};

    form.addEventListener('input', function(e) {
        formData[e.target.name] = e.target.value;

        clearTimeout(saveTimer);
        saveTimer = setTimeout(() => {
            localStorage.setItem('reportDraft', JSON.stringify(formData));
            console.log('Draft saved');
        }, 1000);
    });

    // Load draft on page load
    const savedDraft = localStorage.getItem('reportDraft');
    if (savedDraft) {
        try {
            const draftData = JSON.parse(savedDraft);
            Object.keys(draftData).forEach(key => {
                const field = form.querySelector(`[name="${key}"]`);
                if (field) {
                    field.value = draftData[key];
                }
            });
            showAlert('Draft loaded', 'info');
        } catch (e) {
            console.error('Error loading draft:', e);
        }
    }

    // Clear draft on successful submission
    form.addEventListener('submit', function() {
        localStorage.removeItem('reportDraft');
    });
}

// Initialize auto-save
initAutoSave();