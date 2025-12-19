'use strict';

/**
 * Enhanced Form Validation with Field-Level Error Handling
 * - Popup-style errors using toastr
 * - Red input box styling for invalid fields
 * - Error messages displayed near the input fields
 */

// Initialize form validation on document ready
document.addEventListener('DOMContentLoaded', initFormValidation);

function initFormValidation() {
    // Add validation styles to the document
    addValidationStyles();
    
    // Initialize validation on all forms
    initializeFormValidation();
    
    // Clear errors on input focus
    initializeClearErrorsOnFocus();
}

/**
 * Add CSS styles for validation errors dynamically
 */
function addValidationStyles() {
    if (document.getElementById('form-validation-styles')) return;
    
    const styles = `
        /* Invalid input field styling - red border */
        .form-control.is-invalid,
        .form-select.is-invalid,
        input.is-invalid,
        select.is-invalid,
        textarea.is-invalid {
            border-color: #dc3545 !important;
            background-color: #fff8f8 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.15) !important;
        }
        
        .form-control.is-invalid:focus,
        .form-select.is-invalid:focus,
        input.is-invalid:focus,
        select.is-invalid:focus,
        textarea.is-invalid:focus {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25) !important;
        }
        
        /* Select2 invalid styling */
        .select2-container--default .select2-selection--single.is-invalid,
        .is-invalid + .select2-container--default .select2-selection--single {
            border-color: #dc3545 !important;
            background-color: #fff8f8 !important;
        }
        
        /* Error message styling near input field */
        .field-error-message {
            color: #dc3545;
            font-size: 0.85rem;
            margin-top: 0.35rem;
            display: flex;
            align-items: center;
            animation: fadeInError 0.3s ease-in-out;
        }
        
        .field-error-message i {
            margin-right: 0.35rem;
            font-size: 0.9rem;
        }
        
        @keyframes fadeInError {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Shake animation for invalid fields */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        .shake-error {
            animation: shake 0.5s ease-in-out;
        }
        
        /* Valid input field styling - green border */
        .form-control.is-valid,
        .form-select.is-valid,
        input.is-valid,
        select.is-valid,
        textarea.is-valid {
            border-color: #198754 !important;
        }
        
        /* Form group with error */
        .form-group.has-error .form-label {
            color: #dc3545;
        }
    `;
    
    const styleSheet = document.createElement('style');
    styleSheet.id = 'form-validation-styles';
    styleSheet.textContent = styles;
    document.head.appendChild(styleSheet);
}

/**
 * Initialize form validation handlers
 */
function initializeFormValidation() {
    // Handle form submissions
    $(document).on('submit', 'form', function(e) {
        const form = $(this);
        clearAllFieldErrors(form);
    });
}

/**
 * Initialize clear errors on input focus
 */
function initializeClearErrorsOnFocus() {
    $(document).on('focus', '.form-control, .form-select, input, select, textarea', function() {
        clearFieldError($(this));
    });
    
    $(document).on('change', '.form-control, .form-select, select', function() {
        clearFieldError($(this));
    });
    
    // Handle Select2 focus
    $(document).on('select2:open', function(e) {
        const select = $(e.target);
        clearFieldError(select);
    });
}

/**
 * Show field-level error with red border and message near the input
 * @param {string|jQuery} fieldSelector - Field selector or jQuery object
 * @param {string} errorMessage - Error message to display
 */
window.showFieldError = function(fieldSelector, errorMessage) {
    const field = $(fieldSelector);
    if (!field.length) return;
    
    // Add invalid class to the field
    field.addClass('is-invalid shake-error');
    
    // Add error class to parent form-group
    field.closest('.form-group').addClass('has-error');
    
    // Handle Select2 fields
    if (field.hasClass('select2-hidden-accessible')) {
        field.next('.select2-container').find('.select2-selection').addClass('is-invalid');
    }
    
    // Remove existing error message for this field
    field.siblings('.field-error-message').remove();
    field.parent().find('.field-error-message').remove();
    
    // Create and insert error message near the field
    const errorElement = $('<div class="field-error-message"><i class="fa-solid fa-circle-exclamation"></i><span>' + errorMessage + '</span></div>');
    
    // Insert after the field or after Select2 container
    if (field.hasClass('select2-hidden-accessible')) {
        field.next('.select2-container').after(errorElement);
    } else {
        field.after(errorElement);
    }
    
    // Remove shake animation after it completes
    setTimeout(function() {
        field.removeClass('shake-error');
    }, 500);
    
    // Show popup error using toastr
    if (typeof toastr !== 'undefined') {
        toastr.error(errorMessage);
    }
};

/**
 * Clear error from a specific field
 * @param {string|jQuery} fieldSelector - Field selector or jQuery object
 */
window.clearFieldError = function(fieldSelector) {
    const field = $(fieldSelector);
    if (!field.length) return;
    
    // Remove invalid class
    field.removeClass('is-invalid shake-error');
    
    // Remove error class from parent form-group
    field.closest('.form-group').removeClass('has-error');
    
    // Handle Select2 fields
    if (field.hasClass('select2-hidden-accessible')) {
        field.next('.select2-container').find('.select2-selection').removeClass('is-invalid');
    }
    
    // Remove error message
    field.siblings('.field-error-message').remove();
    field.parent().find('.field-error-message').remove();
};

/**
 * Clear all field errors in a form
 * @param {string|jQuery} formSelector - Form selector or jQuery object
 */
window.clearAllFieldErrors = function(formSelector) {
    const form = $(formSelector);
    if (!form.length) return;
    
    form.find('.is-invalid').removeClass('is-invalid shake-error');
    form.find('.has-error').removeClass('has-error');
    form.find('.field-error-message').remove();
    form.find('.select2-selection.is-invalid').removeClass('is-invalid');
};

/**
 * Show multiple field errors at once
 * @param {Object} errors - Object with field names as keys and error messages as values
 * @param {string|jQuery} formSelector - Optional form selector to scope the errors
 */
window.showFieldErrors = function(errors, formSelector) {
    const form = formSelector ? $(formSelector) : $(document);
    
    // Clear existing errors first
    if (formSelector) {
        clearAllFieldErrors(form);
    }
    
    // Show errors for each field
    let firstErrorField = null;
    $.each(errors, function(fieldName, errorMessage) {
        // Handle array notation (e.g., "items.0.name" -> "items[0][name]")
        let selector = '[name="' + fieldName + '"]';
        let field = form.find(selector);
        
        // Try alternative selectors if not found
        if (!field.length) {
            // Try with array notation
            const arrayNotation = fieldName.replace(/\.(\d+)\./g, '[$1].').replace(/\./g, '][').replace('][', '[') + ']';
            field = form.find('[name="' + arrayNotation + '"]');
        }
        
        if (!field.length) {
            // Try by ID
            field = form.find('#' + fieldName);
        }
        
        if (field.length) {
            // Get the first error message if it's an array
            const message = Array.isArray(errorMessage) ? errorMessage[0] : errorMessage;
            showFieldError(field, message);
            
            if (!firstErrorField) {
                firstErrorField = field;
            }
        }
    });
    
    // Focus on the first error field
    if (firstErrorField) {
        firstErrorField.focus();
        
        // Scroll to the first error field
        $('html, body').animate({
            scrollTop: firstErrorField.offset().top - 100
        }, 300);
    }
};

/**
 * Enhanced AJAX error handler that shows field-level validation errors
 * @param {Object} xhr - XMLHttpRequest object from AJAX error
 * @param {string|jQuery} formSelector - Optional form selector
 */
window.handleAjaxValidationErrors = function(xhr, formSelector) {
    if (xhr.status === 422 && xhr.responseJSON) {
        const response = xhr.responseJSON;
        
        // Show popup error for the main message
        if (response.message && typeof toastr !== 'undefined') {
            toastr.error(response.message);
        }
        
        // Show field-level errors
        if (response.errors) {
            showFieldErrors(response.errors, formSelector);
        }
    } else if (xhr.responseJSON && xhr.responseJSON.message) {
        // Show general error message
        if (typeof toastr !== 'undefined') {
            toastr.error(xhr.responseJSON.message);
        }
    } else {
        // Show generic error
        if (typeof toastr !== 'undefined') {
            toastr.error('An error occurred. Please try again.');
        }
    }
};

/**
 * Validate required fields in a form
 * @param {string|jQuery} formSelector - Form selector or jQuery object
 * @returns {boolean} - True if all required fields are valid
 */
window.validateRequiredFields = function(formSelector) {
    const form = $(formSelector);
    if (!form.length) return true;
    
    let isValid = true;
    const errors = {};
    
    // Check required inputs
    form.find('[required]').each(function() {
        const field = $(this);
        const fieldName = field.attr('name');
        const fieldValue = field.val();
        
        if (!fieldValue || (typeof fieldValue === 'string' && fieldValue.trim() === '')) {
            const label = field.closest('.form-group').find('label').first().text().replace(':', '').replace('*', '').trim();
            errors[fieldName] = label + ' is required';
            isValid = false;
        }
    });
    
    if (!isValid) {
        showFieldErrors(errors, form);
    }
    
    return isValid;
};

/**
 * Validate email format
 * @param {string} email - Email to validate
 * @returns {boolean} - True if valid email format
 */
window.isValidEmail = function(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
};

/**
 * Validate phone number format
 * @param {string} phone - Phone number to validate
 * @returns {boolean} - True if valid phone format
 */
window.isValidPhone = function(phone) {
    const phoneRegex = /^[\d\s\-\+\(\)]{10,}$/;
    return phoneRegex.test(phone);
};

/**
 * Show success state on a field
 * @param {string|jQuery} fieldSelector - Field selector or jQuery object
 */
window.showFieldSuccess = function(fieldSelector) {
    const field = $(fieldSelector);
    if (!field.length) return;
    
    clearFieldError(field);
    field.addClass('is-valid');
    
    // Remove valid class after 3 seconds
    setTimeout(function() {
        field.removeClass('is-valid');
    }, 3000);
};
