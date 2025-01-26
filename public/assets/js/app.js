// Gestion des messages flash
document.addEventListener('DOMContentLoaded', () => {
    // Fermeture des messages flash
    const flashMessages = document.querySelectorAll('.flash-message');
    flashMessages.forEach(message => {
        const closeBtn = message.querySelector('.flash-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                message.remove();
            });
        }
    });

    // Gestion du bouton de déconnexion
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async () => {
            try {
                const response = await fetch('/api/auth/logout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        csrf_token: document.querySelector('meta[name="csrf-token"]').content
                    })
                });

                if (response.ok) {
                    window.location.href = '/login';
                }
            } catch (error) {
                console.error('Erreur lors de la déconnexion:', error);
            }
        });
    }

    // Protection CSRF pour toutes les requêtes AJAX
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (csrfToken) {
        document.addEventListener('fetch', function(event) {
            if (event.request.method !== 'GET') {
                event.request.headers.append('X-CSRF-TOKEN', csrfToken);
            }
        });
    }
});