'use strict';

window.newRecord = function (data, loadingButton, modalSelector = '#AddModal') {
    let formData = (data.formSelector === '') ? data.formData : new FormData(
        $(data.formSelector)[0]);
    loadingButton.attr('disabled', true);

    // Clear previous errors before submitting
    if (data.formSelector && typeof clearAllFieldErrors === 'function') {
        clearAllFieldErrors(data.formSelector);
    }

    $.ajax({
        url: data.url,
        type: data.type,
        dataType: 'json',
        data: formData,
        processData: false,
        contentType: false,
        success: function (result) {
            if (result.success) {
                displaySuccessMessage(result.message);
                $(modalSelector).modal('hide');
                loadingButton.attr('disabled', false);
                // $(data.tableSelector).DataTable().ajax.reload(null, false);
                Livewire.dispatch('refresh');
            }
        },
        error: function (result) {
            loadingButton.attr('disabled', false);
            // Use enhanced error handling with field-level validation
            if (typeof handleAjaxValidationErrors === 'function') {
                handleAjaxValidationErrors(result, data.formSelector);
            } else {
                displayErrorMessage(result.responseJSON.message);
            }
        },
        complete: function () {
            loadingButton.button('reset');
        },
    });
};
window.editRecord = function (
    data, loadingButton, modalSelector = '#EditModal',
    btnToDisabledSelector = '') {
    loadingButton.attr('disabled', true);
    let formData = (data.formSelector === '') ? data.formData : new FormData(
        $(data.formSelector)[0]);
    
    // Clear previous errors before submitting
    if (data.formSelector && typeof clearAllFieldErrors === 'function') {
        clearAllFieldErrors(data.formSelector);
    }
    
    $.ajax({
        url: data.url,
        type: data.type,
        data: formData,
        processData: false,
        contentType: false,
        success: function (result) {
            if (result.success) {
                displaySuccessMessage(result.message);
                $(modalSelector).modal('hide');
                loadingButton.attr('disabled', false);
                Livewire.dispatch('refresh');
            }
        },
        error: function (result) {
            loadingButton.attr('disabled', false);
            // Use enhanced error handling with field-level validation
            if (typeof handleAjaxValidationErrors === 'function') {
                handleAjaxValidationErrors(result, data.formSelector);
            } else {
                UnprocessableInputError(result);
            }
        },
        complete: function () {
            loadingButton.button('reset');
            $(btnToDisabledSelector).attr('disabled', true);
        },
    });
};
window.editRecordWithForm = function (data, loadingButton, modalSelector = '#EditModal') {
    let formData = (data.formSelector === '') ? data.formData : $(
        data.formSelector).serialize();
    loadingButton.attr('disabled', true);
    
    // Clear previous errors before submitting
    if (data.formSelector && typeof clearAllFieldErrors === 'function') {
        clearAllFieldErrors(data.formSelector);
    }
    
    $.ajax({
        url: data.url,
        type: data.type,
        data: formData,
        success: function (result) {
            if (result.success) {
                displaySuccessMessage(result.message);
                $(modalSelector).modal('hide');
                loadingButton.attr('disabled', false);
                Livewire.dispatch('refresh');
                // $(data.tableSelector).DataTable().ajax.reload(null, false);
            }
        },
        error: function (result) {
            loadingButton.attr('disabled', false);
            // Use enhanced error handling with field-level validation
            if (typeof handleAjaxValidationErrors === 'function') {
                handleAjaxValidationErrors(result, data.formSelector);
            } else {
                UnprocessableInputError(result);
            }
        },
        complete: function () {
            loadingButton.button('reset');
        },
    });
};
