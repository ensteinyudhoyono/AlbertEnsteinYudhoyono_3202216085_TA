$(document).ready(function() {
    // Email validation for registration form
    $('#email').on('blur', function() {
        var email = $(this).val();
        var emailField = $(this);
        
        if (email && isValidEmail(email)) {
            // Show loading indicator
            emailField.addClass('is-loading');
            
            // Check if email exists (for registration)
            $.ajax({
                url: '/check-email',
                method: 'POST',
                data: {
                    email: email,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    emailField.removeClass('is-loading');
                    if (response.exists) {
                        emailField.addClass('is-invalid');
                        emailField.removeClass('is-valid');
                        if (!emailField.next('.invalid-feedback').length) {
                            emailField.after('<div class="invalid-feedback" style="display: block; color: #dc3545; font-size: 0.875rem; margin-top: 0.25rem;"><i class="bi bi-exclamation-triangle-fill"></i> Email ini sudah terdaftar dalam sistem. Silakan gunakan email lain atau hubungi admin untuk bantuan.</div>');
                        }
                    } else {
                        emailField.removeClass('is-invalid');
                        emailField.addClass('is-valid');
                        emailField.next('.invalid-feedback').remove();
                        emailField.after('<div class="valid-feedback" style="display: block; color: #198754; font-size: 0.875rem; margin-top: 0.25rem;"><i class="bi bi-check-circle-fill"></i> Email tersedia untuk digunakan.</div>');
                    }
                },
                error: function() {
                    emailField.removeClass('is-loading');
                    emailField.removeClass('is-invalid is-valid');
                    emailField.next('.invalid-feedback, .valid-feedback').remove();
                }
            });
        } else if (email && !isValidEmail(email)) {
            emailField.addClass('is-invalid');
            emailField.removeClass('is-valid');
            if (!emailField.next('.invalid-feedback').length) {
                emailField.after('<div class="invalid-feedback" style="display: block; color: #dc3545; font-size: 0.875rem; margin-top: 0.25rem;"><i class="bi bi-exclamation-triangle-fill"></i> Format email tidak valid. Silakan masukkan email yang benar.</div>');
            }
        } else {
            emailField.removeClass('is-invalid is-valid');
            emailField.next('.invalid-feedback, .valid-feedback').remove();
        }
    });

    // Email validation for add user modal
    $('#addUser #email').on('blur', function() {
        var email = $(this).val();
        var emailField = $(this);
        
        if (email && isValidEmail(email)) {
            // Show loading indicator
            emailField.addClass('is-loading');
            
            // Check if email exists (for user creation)
            $.ajax({
                url: '/check-email',
                method: 'POST',
                data: {
                    email: email,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    emailField.removeClass('is-loading');
                    if (response.exists) {
                        emailField.addClass('is-invalid');
                        emailField.removeClass('is-valid');
                        if (!emailField.next('.invalid-feedback').length) {
                            emailField.after('<div class="invalid-feedback" style="display: block; color: #dc3545; font-size: 0.875rem; margin-top: 0.25rem;"><i class="bi bi-exclamation-triangle-fill"></i> Email ini sudah terdaftar dalam sistem. Silakan gunakan email lain.</div>');
                        }
                    } else {
                        emailField.removeClass('is-invalid');
                        emailField.addClass('is-valid');
                        emailField.next('.invalid-feedback').remove();
                        emailField.after('<div class="valid-feedback" style="display: block; color: #198754; font-size: 0.875rem; margin-top: 0.25rem;"><i class="bi bi-check-circle-fill"></i> Email tersedia untuk digunakan.</div>');
                    }
                },
                error: function() {
                    emailField.removeClass('is-loading');
                    emailField.removeClass('is-invalid is-valid');
                    emailField.next('.invalid-feedback, .valid-feedback').remove();
                }
            });
        } else if (email && !isValidEmail(email)) {
            emailField.addClass('is-invalid');
            emailField.removeClass('is-valid');
            if (!emailField.next('.invalid-feedback').length) {
                emailField.after('<div class="invalid-feedback" style="display: block; color: #dc3545; font-size: 0.875rem; margin-top: 0.25rem;"><i class="bi bi-exclamation-triangle-fill"></i> Format email tidak valid. Silakan masukkan email yang benar.</div>');
            }
        } else {
            emailField.removeClass('is-invalid is-valid');
            emailField.next('.invalid-feedback, .valid-feedback').remove();
        }
    });

    // Email validation for edit user modal
    $('#edituser #email').on('blur', function() {
        var email = $(this).val();
        var emailField = $(this);
        var userId = $('#edituser #id').val();
        
        if (email && isValidEmail(email)) {
            // Show loading indicator
            emailField.addClass('is-loading');
            
            // Check if email exists (for user editing)
            $.ajax({
                url: '/check-email',
                method: 'POST',
                data: {
                    email: email,
                    user_id: userId,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    emailField.removeClass('is-loading');
                    if (response.exists) {
                        emailField.addClass('is-invalid');
                        emailField.removeClass('is-valid');
                        if (!emailField.next('.invalid-feedback').length) {
                            emailField.after('<div class="invalid-feedback" style="display: block; color: #dc3545; font-size: 0.875rem; margin-top: 0.25rem;"><i class="bi bi-exclamation-triangle-fill"></i> Email ini sudah terdaftar dalam sistem. Silakan gunakan email lain.</div>');
                        }
                    } else {
                        emailField.removeClass('is-invalid');
                        emailField.addClass('is-valid');
                        emailField.next('.invalid-feedback').remove();
                        emailField.after('<div class="valid-feedback" style="display: block; color: #198754; font-size: 0.875rem; margin-top: 0.25rem;"><i class="bi bi-check-circle-fill"></i> Email tersedia untuk digunakan.</div>');
                    }
                },
                error: function() {
                    emailField.removeClass('is-loading');
                    emailField.removeClass('is-invalid is-valid');
                    emailField.next('.invalid-feedback, .valid-feedback').remove();
                }
            });
        } else if (email && !isValidEmail(email)) {
            emailField.addClass('is-invalid');
            emailField.removeClass('is-valid');
            if (!emailField.next('.invalid-feedback').length) {
                emailField.after('<div class="invalid-feedback" style="display: block; color: #dc3545; font-size: 0.875rem; margin-top: 0.25rem;"><i class="bi bi-exclamation-triangle-fill"></i> Format email tidak valid. Silakan masukkan email yang benar.</div>');
            }
        } else {
            emailField.removeClass('is-invalid is-valid');
            emailField.next('.invalid-feedback, .valid-feedback').remove();
        }
    });

    // Function to validate email format
    function isValidEmail(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Clear validation messages when modal is closed
    $('#addUser, #edituser').on('hidden.bs.modal', function() {
        $(this).find('.is-invalid, .is-valid').removeClass('is-invalid is-valid');
        $(this).find('.invalid-feedback, .valid-feedback').remove();
    });
}); 