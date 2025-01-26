document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('forgotPasswordForm');
    const errorDiv = document.getElementById('forgotPasswordError');
    const successDiv = document.getElementById('forgotPasswordSuccess');
    const submitButton = form.querySelector('button[type="submit"]');
    const buttonText = submitButton.querySelector('.btn-text');
    const buttonLoader = submitButton.querySelector('.btn-loader');

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Réinitialiser les messages
        errorDiv.classList.add('d-none');
        successDiv.classList.add('d-none');
        
        // Validation du formulaire
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        // Désactiver le bouton et afficher le loader
        submitButton.disabled = true;
        buttonText.classList.add('opacity-0');
        buttonLoader.classList.remove('d-none');

        try {
            const formData = new FormData(form);
            const response = await fetch('/Plateformeval/public/mot-de-passe-oublie/send', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(Object.fromEntries(formData))
            });

            const data = await response.json();

            if (data.success) {
                // Afficher le message de succès
                successDiv.textContent = data.message || 'Les instructions de réinitialisation ont été envoyées à votre adresse email.';
                successDiv.classList.remove('d-none');
                form.reset();
                form.classList.remove('was-validated');
            } else {
                // Afficher le message d'erreur
                errorDiv.textContent = data.message || 'Une erreur est survenue. Veuillez réessayer.';
                errorDiv.classList.remove('d-none');
            }
        } catch (error) {
            console.error('Error:', error);
            errorDiv.textContent = 'Une erreur est survenue. Veuillez réessayer.';
            errorDiv.classList.remove('d-none');
        } finally {
            // Réactiver le bouton et cacher le loader
            submitButton.disabled = false;
            buttonText.classList.remove('opacity-0');
            buttonLoader.classList.add('d-none');
        }
    });

    // Validation en temps réel de l'email
    const emailInput = form.querySelector('#email');
    emailInput.addEventListener('input', function() {
        if (emailInput.validity.valid) {
            emailInput.classList.remove('is-invalid');
            emailInput.classList.add('is-valid');
        } else {
            emailInput.classList.remove('is-valid');
            emailInput.classList.add('is-invalid');
        }
    });
}); 