console.log('Fichier evaluations_tri.js chargé');

function initEvaluationsTri() {
    if (window.evaluationsTriInitialized) {
        console.log('Script déjà initialisé, sortie...');
        return;
    }
    
    console.log('Initialisation du script de tri des évaluations...');
    window.evaluationsTriInitialized = true;

    // Initialiser les boutons de suppression
    const deleteButtons = document.querySelectorAll('button[data-action="delete"]');
    console.log('Nombre de boutons de suppression trouvés:', deleteButtons.length);
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('=== CLIC SUR BOUTON SUPPRIMER ===');

            // Récupérer les informations de l'évaluation
            const evaluationId = this.getAttribute('data-evaluation-id');
            const formUrl = this.getAttribute('data-form-url');
            
            if (confirm('Êtes-vous sûr de vouloir supprimer cette évaluation ? Cette action est irréversible.')) {
                console.log('Confirmation acceptée');
                
                // Désactiver le bouton
                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Suppression...';

                // Créer et soumettre le formulaire dynamiquement
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                form.action = formUrl;

                // Ajouter le token CSRF
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                if (csrfToken) {
                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = 'csrf_token';
                    csrfInput.value = csrfToken;
                    form.appendChild(csrfInput);
                }

                // Ajouter l'ID de l'évaluation
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'evaluation_id';
                idInput.value = evaluationId;
                form.appendChild(idInput);

                // Ajouter la méthode DELETE
                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'DELETE';
                form.appendChild(methodInput);

                // Ajouter le formulaire au document et le soumettre
                document.body.appendChild(form);
                console.log('Soumission du formulaire...', {
                    action: form.action,
                    method: form.method,
                    inputs: form.innerHTML
                });
                form.submit();
            } else {
                console.log('Suppression annulée');
            }
        });
    });

    // Gestion du tri (reste inchangé)
    const sortableHeaders = document.querySelectorAll('th[data-sort]');
    sortableHeaders.forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            const sort = this.dataset.sort;
            const currentOrder = this.dataset.order || 'DESC';
            const newOrder = currentOrder === 'DESC' ? 'ASC' : 'DESC';
            
            const matiereMatch = window.location.pathname.match(/\/matieres\/(\d+)/);
            if (!matiereMatch) {
                console.error('ID matière non trouvé dans l\'URL');
                return;
            }
            
            window.location.href = `${window.location.pathname}?sort=${sort}&order=${newOrder}`;
        });
    });
}

// Initialiser une seule fois au chargement du DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEvaluationsTri);
} else {
    initEvaluationsTri();
} 