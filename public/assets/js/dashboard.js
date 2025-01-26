document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard script loaded');

    // Initialize Bootstrap components
    initializeBootstrapComponents();
    
    // Initialize dashboard features
    initializeDashboard();
    
    // Initialize flash messages
    initializeFlashMessages();
    
    // Initialize counters if they exist
    initializeCounters();
});

function initializeBootstrapComponents() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
}

function initializeDashboard() {
    // Add event listeners for dashboard interactions
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.addEventListener('click', function(e) {
            // Only handle clicks on the card itself, not its children
            if (e.target === card) {
                const link = card.querySelector('a.btn');
                if (link) {
                    link.click();
                }
            }
        });

        // Add hover effect to cards
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.transition = 'transform 0.3s ease';
        });

        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Initialize confirmation dialogs for destructive actions
    const confirmForms = document.querySelectorAll('form[data-confirm]');
    confirmForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const message = this.dataset.confirm || 'Êtes-vous sûr de vouloir effectuer cette action ?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
}

function initializeFlashMessages() {
    const flashMessages = document.querySelectorAll('.alert');
    flashMessages.forEach(function(flash) {
        const closeBtn = flash.querySelector('.btn-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                flash.classList.add('fade');
                setTimeout(() => {
                    flash.remove();
                }, 150);
            });
        }

        // Auto-close success messages after 5 seconds
        if (flash.classList.contains('alert-success')) {
            setTimeout(() => {
                flash.classList.add('fade');
                setTimeout(() => {
                    flash.remove();
                }, 150);
            }, 5000);
        }
    });
}

function initializeCounters() {
    // Update counters every 30 seconds if they exist
    const counters = document.querySelectorAll('[data-counter]');
    if (counters.length > 0) {
        updateDashboardCounters();
        setInterval(updateDashboardCounters, 30000);
    }
}

function updateDashboardCounters() {
    fetch('/api/dashboard/counters')
        .then(response => response.json())
        .then(data => {
            Object.keys(data).forEach(key => {
                const counter = document.querySelector(`#counter-${key}`);
                if (counter) {
                    counter.textContent = data[key];
                }
            });
        })
        .catch(error => console.error('Erreur lors de la mise à jour des compteurs:', error));
}

function showNotification(message, type = 'info') {
    const container = document.querySelector('.flash-messages') || document.createElement('div');
    if (!container.classList.contains('flash-messages')) {
        container.classList.add('flash-messages');
        document.body.appendChild(container);
    }

    const alert = document.createElement('div');
    alert.classList.add('alert', `alert-${type}`, 'alert-dismissible', 'fade', 'show');
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    `;

    container.appendChild(alert);

    setTimeout(() => {
        alert.classList.remove('show');
        setTimeout(() => alert.remove(), 150);
    }, 5000);
}

// Utility function for AJAX actions
function handleAjaxAction(url, method = 'POST', data = null) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };

    // Ajouter le token CSRF seulement s'il existe
    if (csrfToken) {
        options.headers['X-CSRF-TOKEN'] = csrfToken.content;
    }

    if (data) {
        options.body = JSON.stringify(data);
    }

    return fetch(url, options)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur réseau');
            }
            return response.json();
        })
        .then(data => {
            if (data.message) {
                showNotification(data.message, data.type || 'success');
            }
            return data;
        })
        .catch(error => {
            console.error('Erreur:', error);
            showNotification('Une erreur est survenue', 'error');
            throw error; // Propager l'erreur
        });
}