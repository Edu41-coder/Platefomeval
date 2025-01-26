document.addEventListener('DOMContentLoaded', function() {
    // Gestion du tri des colonnes
    const sortableHeaders = document.querySelectorAll('th a[href*="sort="]');
    
    sortableHeaders.forEach(header => {
        header.addEventListener('click', function(e) {
            e.preventDefault();
            const url = new URL(this.href);
            
            // Mettre à jour l'URL avec les nouveaux paramètres de tri
            window.location.href = url.toString();
        });
    });

    // Mise en évidence de la colonne triée
    const currentSort = new URLSearchParams(window.location.search).get('sort');
    const currentOrder = new URLSearchParams(window.location.search).get('order');
    
    if (currentSort) {
        const header = document.querySelector(`th a[href*="sort=${currentSort}"]`);
        if (header) {
            header.classList.add('fw-bold');
        }
    }
}); 