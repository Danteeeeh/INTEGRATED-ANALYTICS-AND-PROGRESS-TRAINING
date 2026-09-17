// Form Validation Enhancement for LMS
document.addEventListener('DOMContentLoaded', function() {
    
    // Handle Laravel validation errors
    const errorBags = document.querySelectorAll('.field-error');
    errorBags.forEach(error => {
        if (error.textContent.trim()) {
            const field = error.closest('.form-field');
            if (field) {
                field.classList.add('has-error');
                const input = field.querySelector('input, select, textarea');
                if (input) {
                    input.classList.add('input-error');
                }
            }
        }
    });

    // Real-time validation for form fields
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            // Remove error state on input
            input.addEventListener('input', function() {
                const field = this.closest('.form-field');
                if (field) {
                    field.classList.remove('has-error');
                    this.classList.remove('input-error');
                    const errorEl = field.querySelector('.field-error');
                    if (errorEl) {
                        errorEl.textContent = '';
                        errorEl.style.display = 'none';
                    }
                }
            });

            // Add focus effects
            input.addEventListener('focus', function() {
                const field = this.closest('.form-field');
                if (field) {
                    field.classList.add('focused');
                }
            });

            input.addEventListener('blur', function() {
                const field = this.closest('.form-field');
                if (field) {
                    field.classList.remove('focused');
                }
            });
        });

        // Form submission validation
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const requiredInputs = form.querySelectorAll('[required]');
            
            requiredInputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    const field = input.closest('.form-field');
                    if (field) {
                        field.classList.add('has-error');
                        input.classList.add('input-error');
                        
                        let errorEl = field.querySelector('.field-error');
                        if (!errorEl) {
                            errorEl = document.createElement('span');
                            errorEl.className = 'field-error';
                            field.appendChild(errorEl);
                        }
                        errorEl.textContent = 'This field is required';
                        errorEl.style.display = 'block';
                    }
                }
            });

            if (!isValid) {
                e.preventDefault();
                // Scroll to first error
                const firstError = form.querySelector('.has-error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    });

    // Loading state for forms
    const submitButtons = document.querySelectorAll('button[type="submit"]');
    submitButtons.forEach(button => {
        const originalText = button.innerHTML;
        
        button.closest('form')?.addEventListener('submit', function() {
            button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
            button.disabled = true;
            button.style.opacity = '0.7';
        });

        // Reset on page load (in case of validation errors)
        if (button.disabled && !button.closest('form').checkValidity()) {
            button.innerHTML = originalText;
            button.disabled = false;
            button.style.opacity = '1';
        }
    });

    // Success message handling
    const successMessages = document.querySelectorAll('.auth-success, .alert-success');
    successMessages.forEach(msg => {
        setTimeout(() => {
            msg.style.transition = 'opacity 0.5s ease';
            msg.style.opacity = '0';
            setTimeout(() => msg.remove(), 500);
        }, 5000);
    });

    // Error message auto-dismiss
    const errorMessages = document.querySelectorAll('.auth-error, .alert-error');
    errorMessages.forEach(msg => {
        setTimeout(() => {
            msg.style.transition = 'opacity 0.5s ease';
            msg.style.opacity = '0';
            setTimeout(() => msg.remove(), 500);
        }, 8000);
    });
});