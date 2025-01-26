document.addEventListener('DOMContentLoaded', function() {
    // Gestion du tri des colonnes
    const sortableColumns = document.querySelectorAll('th[data-sort]');
    sortableColumns.forEach(column => {
        column.addEventListener('click', function() {
            const sort = this.dataset.sort;
            const currentOrder = this.dataset.order || 'DESC';
            const newOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
            
            // Construire l'URL avec les paramètres de tri
            const url = new URL(window.location.href);
            url.searchParams.set('sort', sort);
            url.searchParams.set('order', newOrder);
            
            // Rediriger vers la nouvelle URL
            window.location.href = url.toString();
        });
    });

    // Gestion de la pagination
    const paginationLinks = document.querySelectorAll('.pagination .page-link');
    paginationLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!this.parentElement.classList.contains('disabled')) {
                const url = new URL(this.href);
                const currentUrl = new URL(window.location.href);
                
                // Conserver les paramètres de tri actuels
                const sort = currentUrl.searchParams.get('sort');
                const order = currentUrl.searchParams.get('order');
                if (sort) url.searchParams.set('sort', sort);
                if (order) url.searchParams.set('order', order);
                
                window.location.href = url.toString();
            }
            e.preventDefault();
        });
    });
}); 