<?php

/** 
 * @var \Core\View\Template $this 
 * @var array|null $user
 * @var string $pageTitle
 */
?>

<div class="container py-4">
    <?php if (isset($user)): ?>
        <?php if (isset($user['role']) && $user['role'] === 'admin'): ?>
            <!-- Section Admin -->
            <section class="dashboard-admin" aria-labelledby="dashboard-title">
                <h1 id="dashboard-title" class="display-4 mb-4">Tableau de bord administrateur</h1>

                <div class="row g-4">
                    <!-- Gestion Utilisateurs -->
                    <div class="col-12 col-md-6">
                        <div class="card h-100 dashboard-card shadow-sm">
                            <div class="card-body d-flex flex-column">
                                <h3 class="card-title text-primary d-flex align-items-center gap-2">
                                    <i class="fas fa-users" aria-hidden="true"></i> Utilisateurs
                                </h3>
                                <p class="card-text text-muted mb-4">Gérer les utilisateurs et leurs droits</p>
                                <a href="<?= $this->url('admin/users') ?>"
                                    class="btn btn-primary mt-auto"
                                    aria-label="Accéder à la gestion des utilisateurs">
                                    <i class="fas fa-arrow-right me-2" aria-hidden="true"></i>
                                    Voir les utilisateurs
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Gestion Évaluations -->
                    <div class="col-12 col-md-6">
                        <div class="card h-100 dashboard-card shadow-sm">
                            <div class="card-body d-flex flex-column">
                                <h3 class="card-title text-primary d-flex align-items-center gap-2">
                                    <i class="fas fa-tasks" aria-hidden="true"></i> Évaluations
                                </h3>
                                <p class="card-text text-muted mb-4">Gérer les évaluations et les résultats</p>
                                <a href="<?= $this->url('admin/evaluations') ?>"
                                    class="btn btn-primary mt-auto"
                                    aria-label="Accéder à la gestion des évaluations">
                                    <i class="fas fa-arrow-right me-2" aria-hidden="true"></i>
                                    Voir les évaluations
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        <?php else: ?>
            <!-- Section Utilisateur -->
            <section class="dashboard-user" aria-labelledby="dashboard-title">
                <h1 id="dashboard-title" class="display-4 mb-4">Mon tableau de bord</h1>

                <div class="row g-4">
                    <!-- Mes évaluations -->
                    <div class="col-12 col-md-6">
                        <div class="card h-100 dashboard-card shadow-sm">
                            <div class="card-body d-flex flex-column">
                                <h3 class="card-title text-primary d-flex align-items-center gap-2">
                                    <i class="fas fa-tasks" aria-hidden="true"></i> Mes évaluations
                                </h3>
                                <p class="card-text text-muted mb-4">Voir et gérer mes évaluations</p>
                                <a href="<?= $this->url('evaluations') ?>"
                                    class="btn btn-primary mt-auto"
                                    aria-label="Accéder à mes évaluations">
                                    <i class="fas fa-arrow-right me-2" aria-hidden="true"></i>
                                    Voir mes évaluations
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Mon profil -->
                    <div class="col-12 col-md-6">
                        <div class="card h-100 dashboard-card shadow-sm">
                            <div class="card-body d-flex flex-column">
                                <h3 class="card-title text-primary d-flex align-items-center gap-2">
                                    <i class="fas fa-user" aria-hidden="true"></i> Mon profil
                                </h3>
                                <p class="card-text text-muted mb-4">Gérer mes informations personnelles</p>
                                <a href="<?= $this->url('profile') ?>"
                                    class="btn btn-primary mt-auto"
                                    aria-label="Accéder à mon profil">
                                    <i class="fas fa-arrow-right me-2" aria-hidden="true"></i>
                                    Voir mon profil
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    <?php else: ?>
        <!-- Section Bienvenue -->
        <section class="welcome-section text-center py-5" aria-labelledby="welcome-title">
            <h1 id="welcome-title" class="display-4 mb-3">
                Bienvenue sur la plateforme d'évaluation
            </h1>
            <p class="lead text-muted mb-4">
                La plateforme d'évaluation simple et efficace
            </p>

            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="<?= $this->url('login') ?>"
                    class="btn btn-primary btn-lg"
                    aria-label="Se connecter à votre compte">
                    <i class="fas fa-sign-in-alt me-2" aria-hidden="true"></i> Se connecter
                </a>
                <a href="<?= $this->url('register') ?>"
                    class="btn btn-secondary btn-lg"
                    aria-label="Créer un nouveau compte">
                    <i class="fas fa-user-plus me-2" aria-hidden="true"></i> S'inscrire
                </a>
            </div>
        </section>
    <?php endif; ?>
</div>

<?php
$this->layout('default', [
    'title' => $pageTitle,
    'user' => $user ?? null,
    'assets' => [
        'css' => [
            'assets/css/dashboard.css',
            'assets/css/home.css'
        ]
    ]
]);
?>