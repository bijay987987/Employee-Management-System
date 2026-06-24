// Form validation and enhancement
document.addEventListener('DOMContentLoaded', function() {
    // Real-time form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            input.addEventListener('input', function() {
                clearFieldError(this);
            });
        });
    });
    
    // Password strength indicator
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(input => {
        input.addEventListener('input', function() {
            checkPasswordStrength(this.value, this);
        });
    });
});

function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    let message = '';
    
    // Required field validation
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        message = 'This field is required';
    }
    
    // Email validation
    if (field.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
            message = 'Please enter a valid email address';
        }
    }
    
    // Phone validation
    if (field.type === 'tel' && value) {
        const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
        if (!phoneRegex.test(value.replace(/[\s\-\(\)]/g, ''))) {
            isValid = false;
            message = 'Please enter a valid phone number';
        }
    }
    
    // Update field state
    if (!isValid) {
        showFieldError(field, message);
    } else {
        clearFieldError(field);
    }
    
    return isValid;
}

function showFieldError(field, message) {
    field.classList.add('error');
    
    // Remove existing error message
    const existingError = field.parentNode.querySelector('.form-feedback');
    if (existingError) {
        existingError.remove();
    }
    
    // Add new error message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'form-feedback error';
    errorDiv.textContent = message;
    field.parentNode.appendChild(errorDiv);
}

function clearFieldError(field) {
    field.classList.remove('error');
    
    const existingError = field.parentNode.querySelector('.form-feedback');
    if (existingError) {
        existingError.remove();
    }
}

function checkPasswordStrength(password, field) {
    let strength = 0;
    let messages = [];
    
    if (password.length >= 8) strength++;
    else messages.push('at least 8 characters');
    
    if (/[A-Z]/.test(password)) strength++;
    else messages.push('one uppercase letter');
    
    if (/[a-z]/.test(password)) strength++;
    else messages.push('one lowercase letter');
    
    if (/[0-9]/.test(password)) strength++;
    else messages.push('one number');
    
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    else messages.push('one special character');
    
    // Update UI based on strength
    const strengthText = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'][strength] || 'Very Weak';
    const strengthColor = ['#e74c3c', '#e67e22', '#f39c12', '#3498db', '#27ae60'][strength] || '#e74c3c';
    
    // You can add a password strength indicator UI here
}