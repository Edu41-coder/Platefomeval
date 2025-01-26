<?php
/**
 * @var \Core\View\Template $this
 * @var string $pageTitle
 * @var array $user
 * @var array $matieres
 */

// Configuration du layout
$this->layout('default', [
    'title' => $pageTitle ?? 'Mes matières',
    'user' => $user ?? null,
    'assets' => [
        'css' => ['assets/css/dashboard.css'],
        'js' => ['assets/js/dashboard.js']
    ]
]);
?>

<div class="container mt-4">
    <!-- Navigation -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?= $this->url('dashboard') ?>" class="text-decoration-none">
                    <i class="fas fa-tachometer-alt"></i> Tableau de bord
                </a>
            </li>
            <li class="breadcrumb-item active">
                <i class="fas fa-book"></i> Mes Matières
            </li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Mes matières</h1>

            <?php if (empty($matieres)): ?>
                <div class="alert alert-info">
                    Vous n'êtes inscrit à aucune matière pour le moment.
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($matieres as $matiere): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h5 class="card-title mb-0"><?= $this->e($matiere['nom']) ?></h5>
                                </div>
                                <div class="card-body">
                                    <p class="card-text"><?= $this->e($matiere['description'] ?? 'Aucune description disponible.') ?></p>
                                    
                                    <!-- Informations sur le professeur -->
                                    <?php if (isset($matiere['professeur'])): ?>
                                        <p class="text-muted">
                                            <i class="fas fa-user-tie"></i> 
                                            Professeur: <?= $this->e($matiere['professeur']['prenom'] . ' ' . $matiere['professeur']['nom']) ?>
                                        </p>
                                    <?php endif; ?>

                                    <!-- Moyenne de l'étudiant -->
                                    <?php if (isset($matiere['moyenne'])): ?>
                                        <p class="text-primary">
                                            <i class="fas fa-chart-line"></i>
                                            Moyenne: <?= number_format($matiere['moyenne'], 2) ?>/20
                                        </p>
                                    <?php endif; ?>

                                    <a href="<?= $this->url('student.matieres.evaluations', ['matiere_id' => $matiere['id']]) ?>" 
                                       class="btn btn-primary">
                                        <i class="fas fa-eye"></i> Voir les évaluations
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div> 