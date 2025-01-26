document.addEventListener('DOMContentLoaded', function() {
    // Gestion du tri des colonnes
    const sortableHeaders = document.querySelectorAll('th a[href*="sort="]');
    
    sortableHeaders.forEach(header => {
        header.addEventListener('click', function(e) {
            e.preventDefault();
            const url = new URL(this.href);
            
            // Conserver l'ID de la matière dans l'URL
            const currentUrl = new URL(window.location.href);
            const matiereId = currentUrl.pathname.split('/').pop();
            
            // Construire la nouvelle URL avec les paramètres de tri et l'ID
            const newUrl = new URL(`${currentUrl.origin}${currentUrl.pathname}`);
            newUrl.searchParams.set('sort', url.searchParams.get('sort'));
            newUrl.searchParams.set('order', url.searchParams.get('order'));
            
            // Rediriger vers la nouvelle URL
            window.location.href = newUrl.toString();
        });
    });

    // Mise en évidence de la colonne triée
    const currentSort = new URLSearchParams(window.location.search).get('sort');
    const currentOrder = new URLSearchParams(window.location.search).get('order');
    
    if (currentSort) {
        const header = document.querySelector(`th a[href*="sort=${currentSort}"]`);
        if (header) {
            header.classList.add('fw-bold');
            
            // Mettre à jour l'icône de tri
            const icon = header.querySelector('i.fas');
            if (icon) {
                icon.className = `fas fa-sort-${currentOrder === 'ASC' ? 'up' : 'down'}`;
            }
        }
    }
}); 