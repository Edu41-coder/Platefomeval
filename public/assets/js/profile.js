// profile.js

document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form.needs-validation');

    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }

        form.classList.add('was-validated');
    }, false);
});