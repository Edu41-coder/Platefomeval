<?php
/**
 * @var \Core\View\Template $this
 * @var string $pageTitle
 * @var array $user
 * @var array $matieres
 * @var \App\Models\Entity\Photo|null $photo
 */

// Encapsulation dans une fonction anonyme pour éviter l'utilisation de $this dans le code global
(function ($template, $pageTitle, $user, $photo) {
    // Configuration du layout
    $template->layout('default', [
        'title' => $pageTitle ?? 'Tableau de bord',
        'user' => $user ?? null,
        'assets' => [
            'css' => ['assets/css/dashboard.css'],
            'js' => ['assets/js/dashboard.js']
        ]
    ]);

    // Helper function to check role
    $hasRole = function($roleToCheck) use ($user) {
        if (isset($user['role_id'])) {
            return $user['role_id'] === ($roleToCheck === 'professeur' ? 2 : ($roleToCheck === 'etudiant' ? 3 : null));
        }
        return isset($user['role']) && $user['role'] === $roleToCheck;
    };
?>
<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-12 text-center my-5">
            <div class="d-flex align-items-center justify-content-center">
                <?php if (isset($photo) && $photo): ?>
                    <img src="<?= PUBLIC_PATH . '/' . $this->e($photo->getFullPath()) . '?v=' . time() ?>" 
                         alt="Photo de profil" 
                         class="rounded-circle me-3"
                         style="width: 80px; height: 80px; object-fit: cover;">
                <?php else: ?>
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3" 
                         style="width: 80px; height: 80px;">
                        <i class="fas fa-user fa-2x text-muted"></i>
                    </div>
                <?php endif; ?>
                <div>
                    <h2 class="fw-bold mb-3">Bienvenue, <?= $this->e($user['prenom']) ?> <?= $this->e($user['nom']) ?></h2>
                    <p class="lead mb-3 text-primary">Il est <?= date('H:i') ?> heures.</p>
                    <p>Voici votre espace personnel.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <?php if ($user['is_admin'] ?? false): ?>
            <!-- Section administration -->
            <div class="col-md-6">
                <div class="card hover-animate text-center admin-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-cog icon-animate"></i>
                            Espace Administrateur
                        </h5>
                    </div>
                    <div class="card-body d-flex flex-column align-items-center">
                        <p class="card-text">Gérez ici les utilisateurs de l'application.</p>
                        <a href="<?= $template->url('admin') ?>" class="btn btn-primary mt-auto">Accéder à l'administration</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($hasRole('professeur')): ?>
            <!-- Section des matières pour les professeurs -->
            <div class="col-md-6">
                <div class="card hover-animate text-center admin-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-book icon-animate"></i>
                            Mes matières
                        </h5>
                    </div>
                    <div class="card-body d-flex flex-column align-items-center">
                        <p class="card-text">Gérez vos matières, les étudiants inscrits et leurs évaluations.</p>
                        <a href="<?= $template->url('professor.matieres') ?>" class="btn btn-primary mt-auto">Voir mes matières</a>
                    </div>
                </div>
            </div>
        <?php elseif ($hasRole('etudiant')): ?>
            <!-- Section des matières pour les étudiants -->
            <div class="col-md-6">
                <div class="card hover-animate text-center admin-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-book icon-animate"></i>
                            Mes matières
                        </h5>
                    </div>
                    <div class="card-body d-flex flex-column align-items-center">
                        <p class="card-text">Consultez vos matières et leur contenu.</p>
                        <a href="<?= $template->url('student.matieres') ?>" class="btn btn-primary mt-auto">Voir mes matières</a>
                    </div>
                </div>
            </div>

            <!-- Section des évaluations pour les étudiants -->
            <div class="col-md-6">
                <div class="card hover-animate text-center admin-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-bar icon-animate"></i>
                            Mes évaluations
                        </h5>
                    </div>
                    <div class="card-body d-flex flex-column align-items-center">
                        <p class="card-text">Consultez vos évaluations et résultats.</p>
                        <?php 
                        $url = $template->url('student.evaluations.all');
                        ?>
                        <a href="<?= $url ?>" class="btn btn-primary mt-auto">
                            Voir toutes mes évaluations
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Section du profil -->
        <div class="col-md-6">
            <div class="card hover-animate text-center admin-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user icon-animate"></i>
                        Mon profil
                    </h5>
                </div>
                <div class="card-body d-flex flex-column align-items-center">
                    <p class="card-text">Gérez vos informations personnelles.</p>
                    <a href="<?= $template->url('profile') ?>" class="btn btn-primary mt-auto">Voir mon profil</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
})($this, $pageTitle ?? null, $user ?? null, $photo ?? null);
?>
 