// Scripts spécifiques au tableau de bord étudiant

document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des fonctionnalités héritées
    initializeDashboard();

    // Fonctionnalités spécifiques aux étudiants
    const studentSpecificElement = document.querySelector('.student-specific');
    if (studentSpecificElement) {
        studentSpecificElement.addEventListener('click', function() {
            alert('Fonctionnalité spécifique aux étudiants activée!');
        });
    }
});

// Fonction pour initialiser les fonctionnalités du tableau de bord général
function initializeDashboard() {
    // Copiez ici les fonctionnalités de dashboard.js
    // Par exemple, initialisation des tooltips, popovers, etc.
}