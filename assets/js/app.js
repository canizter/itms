// Ensure modal functions are always available after AJAX updates
function reattachAssetModalFunctions() {
    window.showAssetModal = window.showAssetModal;
    window.showAssignmentHistoryModal = window.showAssignmentHistoryModal;
}
document.addEventListener('DOMContentLoaded', reattachAssetModalFunctions);
// If you use AJAX to update the asset table, call reattachAssetModalFunctions() after update.
// Asset Modal Functions (moved from assets.php for global access)
window.showAssetModal = function(asset) {
    const modal = document.getElementById('assetDetailsModal');
    const content = document.getElementById('assetDetailsContent');
    let html = '';
    html += `<div class="flex justify-between py-2"><dt class="font-medium text-gray-700">Asset Tag:</dt><dd class="text-gray-900">${asset.asset_tag || ''}</dd></div>`;
    html += `<div class="flex justify-between py-2"><dt class="font-medium text-gray-700">Category:</dt><dd class="text-gray-900">${asset.category_name || ''}</dd></div>`;
    html += `<div class="flex justify-between py-2"><dt class="font-medium text-gray-700">Vendor:</dt><dd class="text-gray-900">${asset.vendor_name || ''}</dd></div>`;
    html += `<div class="flex justify-between py-2"><dt class="font-medium text-gray-700">Location:</dt><dd class="text-gray-900">${asset.location_name || ''}</dd></div>`;
    let statusLabel = '';
    let statusClass = '';
    switch (asset.status) {
        case 'active': statusLabel = 'In Use'; statusClass = 'bg-green-100 text-green-800'; break;
        case 'inactive': statusLabel = 'Available'; statusClass = 'bg-blue-100 text-blue-800'; break;
        case 'maintenance': statusLabel = 'In Repair'; statusClass = 'bg-yellow-100 text-yellow-800'; break;
        case 'retired': case 'disposed': statusLabel = 'Retired'; statusClass = 'bg-gray-200 text-gray-700'; break;
        default: statusLabel = asset.status || ''; statusClass = 'bg-gray-100 text-gray-700';
    }
    html += `<div class="flex justify-between py-2"><dt class="font-medium text-gray-700">Status:</dt><dd><span class="inline-block px-3 py-1 rounded-full text-xs font-semibold ${statusClass}">${statusLabel}</span></dd></div>`;
    html += `<div class="flex justify-between py-2"><dt class="font-medium text-gray-700">Serial Number:</dt><dd class="text-gray-900">${asset.serial_number || ''}</dd></div>`;
    html += `<div class="flex justify-between py-2"><dt class="font-medium text-gray-700">LAN MAC Address:</dt><dd class="text-gray-900">${asset.lan_mac || ''}</dd></div>`;
    html += `<div class="flex justify-between py-2"><dt class="font-medium text-gray-700">WLAN MAC Address:</dt><dd class="text-gray-900">${asset.wlan_mac || ''}</dd></div>`;
    html += `<div class="flex justify-between py-2"><dt class="font-medium text-gray-700">Assigned Employee ID:</dt><dd class="text-gray-900">${asset.assigned_employee_id || ''}</dd></div>`;
    html += `<div class="flex justify-between py-2"><dt class="font-medium text-gray-700">Assigned Employee Name:</dt><dd class="text-gray-900">${asset.assigned_employee_name || ''}</dd></div>`;
    html += `<div class="flex justify-between py-2"><dt class="font-medium text-gray-700">Note / Remarks:</dt><dd class="text-gray-900">${asset.notes || ''}</dd></div>`;
    let footer = document.querySelector('#assetDetailsModal .modal-footer');
    if (!footer) {
        footer = document.createElement('div');
        footer.className = 'modal-footer flex justify-end gap-2 px-6 py-4 border-t bg-gray-50 rounded-b-lg';
        document.querySelector('#assetDetailsModal > div').appendChild(footer);
    }
    footer.innerHTML = '';
    if (asset.can_edit_delete) {
        const editBtn = document.createElement('a');
        editBtn.href = `asset_edit.php?id=${asset.id}`;
        editBtn.className = 'inline-flex items-center gap-1 px-3 py-1 bg-yellow-100 text-yellow-800 rounded hover:bg-yellow-200 text-xs font-medium transition';
        editBtn.title = 'Edit';
        editBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a4 4 0 01-2.828 1.172H7v-2a4 4 0 011.172-2.828z" /></svg>Edit`;
        footer.appendChild(editBtn);
        const delBtn = document.createElement('a');
        delBtn.href = `asset_delete.php?id=${asset.id}`;
        delBtn.className = 'inline-flex items-center gap-1 px-3 py-1 bg-red-100 text-red-800 rounded hover:bg-red-200 text-xs font-medium transition';
        delBtn.title = 'Delete';
        delBtn.onclick = function() { return confirm('Are you sure you want to delete this asset?'); };
        delBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>Delete`;
        footer.appendChild(delBtn);
        const assignBtn = document.createElement('button');
        assignBtn.type = 'button';
        assignBtn.className = 'inline-flex items-center gap-1 px-3 py-1 bg-purple-100 text-purple-800 rounded hover:bg-purple-200 text-xs font-medium transition';
        assignBtn.title = 'Assignment History';
        assignBtn.onclick = function() {
            modal.classList.add('hidden');
            window.showAssignmentHistoryModal(asset.id, asset.asset_tag);
        };
        assignBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>Assignment`;
        footer.appendChild(assignBtn);
    }
    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300';
    closeBtn.onclick = function() { modal.classList.add('hidden'); };
    closeBtn.textContent = 'Close';
    footer.appendChild(closeBtn);
    content.innerHTML = html;
    modal.classList.remove('hidden');
};

window.showAssignmentHistoryModal = function(assetId, assetTag) {
    const modal = document.getElementById('assignmentHistoryModal');
    const content = document.getElementById('assignmentHistoryContent');
    const tagSpan = document.getElementById('assignmentHistoryAssetTag');
    tagSpan.textContent = assetTag;
    content.innerHTML = '<div class="text-gray-500 text-sm">Loading...</div>';
    modal.classList.remove('hidden');
    fetch('asset_assignment_history_api.php?asset_id=' + encodeURIComponent(assetId))
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                content.innerHTML = `<div class='bg-red-100 text-red-700 px-4 py-2 rounded mb-3 text-sm'>${data.error}</div>`;
                return;
            }
            if (!data.history || data.history.length === 0) {
                content.innerHTML = '<div class="text-gray-500 text-sm">No assignment history found for this asset.</div>';
                return;
            }
            let html = '<div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200"><thead><tr>' +
                '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Employee ID</th>' +
                '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Employee Name</th>' +
                '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Assigned By</th>' +
                '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Assigned Date</th>' +
                '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Return Date</th>' +
                '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>' +
                '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Recorded</th>' +
                '</tr></thead><tbody>';
            for (const row of data.history) {
                html += '<tr>' +
                    `<td class="px-4 py-2 whitespace-nowrap text-gray-700">${row.employee_id || ''}</td>` +
                    `<td class="px-4 py-2 whitespace-nowrap text-gray-700">${row.employee_name || ''}</td>` +
                    `<td class="px-4 py-2 whitespace-nowrap text-gray-700">${row.assigned_by || ''}</td>` +
                    `<td class="px-4 py-2 whitespace-nowrap text-gray-700">${row.assigned_date || ''}</td>` +
                    `<td class="px-4 py-2 whitespace-nowrap text-gray-700">${row.return_date || ''}</td>` +
                    `<td class="px-4 py-2 whitespace-nowrap text-gray-700">${row.notes || ''}</td>` +
                    `<td class="px-4 py-2 whitespace-nowrap text-gray-700">${row.created_at || ''}</td>` +
                    '</tr>';
            }
            html += '</tbody></table></div>';
            content.innerHTML = html;
        })
        .catch(err => {
            content.innerHTML = `<div class='bg-red-100 text-red-700 px-4 py-2 rounded mb-3 text-sm'>Error loading assignment history.</div>`;
        });
};
// IT Management System JavaScript

// Global variables
let currentPage = 1;
let itemsPerPage = 20;

// DOM Content Loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

// Initialize application
function initializeApp() {
    // Initialize tooltips
    initializeTooltips();
    
    // Initialize form validation
    initializeFormValidation();
    
    // Initialize search functionality
    initializeSearch();
    
    // Initialize confirmation dialogs
    initializeConfirmations();
    
    // Initialize date pickers
    initializeDatePickers();
    
    // Auto-logout timer
    initializeAutoLogout();
}

// Initialize tooltips
function initializeTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

// Show tooltip
function showTooltip(event) {
    const element = event.target;
    const text = element.getAttribute('data-tooltip');
    
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = text;
    tooltip.style.position = 'absolute';
    tooltip.style.background = '#333';
    tooltip.style.color = 'white';
    tooltip.style.padding = '5px 10px';
    tooltip.style.borderRadius = '4px';
    tooltip.style.fontSize = '12px';
    tooltip.style.zIndex = '1000';
    tooltip.style.whiteSpace = 'nowrap';
    
    document.body.appendChild(tooltip);
    
    const rect = element.getBoundingClientRect();
    tooltip.style.left = rect.left + 'px';
    tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
    
    element.tooltip = tooltip;
}

// Hide tooltip
function hideTooltip(event) {
    const element = event.target;
    if (element.tooltip) {
        document.body.removeChild(element.tooltip);
        element.tooltip = null;
    }
}

// Initialize form validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', validateForm);
    });
}

// Validate form
function validateForm(event) {
    const form = event.target;
    let isValid = true;
    const errors = [];
    
    // Clear previous errors
    const errorElements = form.querySelectorAll('.error-message');
    errorElements.forEach(el => el.remove());
    
    // Validate required fields
    const requiredFields = form.querySelectorAll('[required]');
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            errors.push(`${getFieldLabel(field)} is required`);
            showFieldError(field, 'This field is required');
        }
    });
    
    // Validate email fields
    const emailFields = form.querySelectorAll('input[type="email"]');
    emailFields.forEach(field => {
        if (field.value && !isValidEmail(field.value)) {
            isValid = false;
            errors.push(`${getFieldLabel(field)} must be a valid email`);
            showFieldError(field, 'Please enter a valid email address');
        }
    });
    
    // Validate number fields
    const numberFields = form.querySelectorAll('input[type="number"]');
    numberFields.forEach(field => {
        const min = field.getAttribute('min');
        const max = field.getAttribute('max');
        const value = parseFloat(field.value);
        
        if (field.value && isNaN(value)) {
            isValid = false;
            showFieldError(field, 'Please enter a valid number');
        } else if (min && value < parseFloat(min)) {
            isValid = false;
            showFieldError(field, `Value must be at least ${min}`);
        } else if (max && value > parseFloat(max)) {
            isValid = false;
            showFieldError(field, `Value must be no more than ${max}`);
        }
    });
    
    if (!isValid) {
        event.preventDefault();
        showAlert('Please correct the errors below', 'error');
    }
}

// Get field label
function getFieldLabel(field) {
    const label = field.previousElementSibling;
    if (label && label.tagName === 'LABEL') {
        return label.textContent.replace('*', '').trim();
    }
    return field.name || field.id || 'Field';
}

// Show field error
function showFieldError(field, message) {
    field.classList.add('error');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.style.color = '#dc3545';
    errorDiv.style.fontSize = '0.8rem';
    errorDiv.style.marginTop = '0.25rem';
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
}

// Validate email
function isValidEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

// Initialize search functionality
function initializeSearch() {
    const searchInputs = document.querySelectorAll('.search-input');
    searchInputs.forEach(input => {
        input.addEventListener('input', debounce(performSearch, 300));
    });
    
    const filterSelects = document.querySelectorAll('.filter-select');
    filterSelects.forEach(select => {
        select.addEventListener('change', performSearch);
    });
}

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Perform search
function performSearch() {
    const searchForm = document.querySelector('#searchForm');
    if (!searchForm) return;
    
    const formData = new FormData(searchForm);
    const params = new URLSearchParams(formData);
    
    // Add current page to maintain pagination
    params.set('page', currentPage);
    
    // Update URL without refreshing page
    const newUrl = window.location.pathname + '?' + params.toString();
    history.pushState(null, '', newUrl);
    
    // Perform AJAX search if supported
    if (window.performAjaxSearch) {
        performAjaxSearch(params);
    } else {
        // Fallback to form submission
        window.location.href = newUrl;
    }
}

// Initialize confirmation dialogs
function initializeConfirmations() {
    const confirmButtons = document.querySelectorAll('[data-confirm]');
    confirmButtons.forEach(button => {
        button.addEventListener('click', handleConfirmation);
    });
}

// Handle confirmation
function handleConfirmation(event) {
    const element = event.target;
    const message = element.getAttribute('data-confirm');
    const confirmText = element.getAttribute('data-confirm-text') || 'Are you sure?';
    
    if (!confirm(confirmText + '\n\n' + message)) {
        event.preventDefault();
        return false;
    }
    
    return true;
}

// Initialize date pickers
function initializeDatePickers() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        // Set max date to today for past dates
        if (input.classList.contains('past-date-only')) {
            input.max = new Date().toISOString().split('T')[0];
        }
        
        // Set min date to today for future dates
        if (input.classList.contains('future-date-only')) {
            input.min = new Date().toISOString().split('T')[0];
        }
    });
}

// Initialize auto-logout
function initializeAutoLogout() {
    let timeoutWarning;
    let timeoutLogout;
    const warningTime = 55 * 60 * 1000; // 55 minutes
    const logoutTime = 60 * 60 * 1000; // 60 minutes
    
    function resetTimer() {
        clearTimeout(timeoutWarning);
        clearTimeout(timeoutLogout);
        
        timeoutWarning = setTimeout(() => {
            if (confirm('Your session will expire in 5 minutes. Click OK to continue working.')) {
                resetTimer();
            }
        }, warningTime);
        
        timeoutLogout = setTimeout(() => {
            alert('Your session has expired. You will be redirected to the login page.');
            window.location.href = 'login.php?timeout=1';
        }, logoutTime);
    }
    
    // Reset timer on user activity
    document.addEventListener('mousedown', resetTimer);
    document.addEventListener('mousemove', resetTimer);
    document.addEventListener('keypress', resetTimer);
    document.addEventListener('scroll', resetTimer);
    document.addEventListener('touchstart', resetTimer);
    
    // Start timer
    resetTimer();
}

// Utility functions
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    // Insert at top of container
    const container = document.querySelector('.container') || document.body;
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.parentNode.removeChild(alertDiv);
        }
    }, 5000);
}

function showLoading(element) {
    const spinner = document.createElement('div');
    spinner.className = 'spinner';
    
    if (element) {
        element.appendChild(spinner);
    } else {
        document.body.appendChild(spinner);
    }
    
    return spinner;
}

function hideLoading(spinner) {
    if (spinner && spinner.parentNode) {
        spinner.parentNode.removeChild(spinner);
    }
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

function formatDate(dateString, options = {}) {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        ...options
    }).format(date);
}

// AJAX functions
function makeAjaxRequest(url, options = {}) {
    const defaultOptions = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    return fetch(url, { ...defaultOptions, ...options })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .catch(error => {
            console.error('AJAX request failed:', error);
            showAlert('An error occurred while processing your request', 'error');
            throw error;
        });
}

// Export functions for global use
window.ITMS = {
    showAlert,
    showLoading,
    hideLoading,
    formatCurrency,
    formatDate,
    makeAjaxRequest,
    debounce
};
