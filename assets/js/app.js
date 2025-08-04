// IT Inventory Management System JavaScript

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
