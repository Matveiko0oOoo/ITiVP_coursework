
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(form)) {
                e.preventDefault();
            }
        });
    });

    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    function validateForm(form) {
        let isValid = true;
        const inputs = form.querySelectorAll('input[required], textarea[required]');
        
        inputs.forEach(input => {
            if (input.type === 'email' && !validateEmail(input.value)) {
                showError(input, 'Введите корректный email адрес');
                isValid = false;
            } else if (input.type === 'password') {
                if (input.value.length < 8) {
                    showError(input, 'Пароль должен содержать минимум 8 символов');
                    isValid = false;
                } else if (!/[a-zA-Zа-яА-Я]/.test(input.value)) {
                    showError(input, 'Пароль должен содержать хотя бы одну букву');
                    isValid = false;
                }
            } else if (!input.value.trim()) {
                showError(input, 'Это поле обязательно для заполнения');
                isValid = false;
            } else {
                clearError(input);
            }
        });

        return isValid;
    }

    function showError(input, message) {
        clearError(input);
        input.style.borderColor = '#e74c3c';
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-error';
        errorDiv.textContent = message;
        input.parentNode.insertBefore(errorDiv, input.nextSibling);
    }

    function clearError(input) {
        input.style.borderColor = '';
        const errorDiv = input.parentNode.querySelector('.alert-error');
        if (errorDiv) {
            errorDiv.remove();
        }
    }

    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        });
    }, 5000);
});

