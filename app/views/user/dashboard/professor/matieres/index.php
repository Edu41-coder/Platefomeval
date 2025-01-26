<?php

use App\Models\Entity\Matiere;
use App\Models\User;
use Core\View\Template;

/**
 * @var Template $this
 * @var string $pageTitle
 * @var array $user
 * @var Matiere[] $matieres Liste des matières du professeur
 */

// Encapsulation dans une fonction anonyme
(function ($template, $pageTitle, $user, $matieres) {
    // Configuration du layout
    $template->layout('default', [
        'title' => $pageTitle ?? 'Mes Matières',
        'user' => $user ?? null,
        'assets' => [
            'css' => [$template->asset('css/dashboard.css')],
            'js' => [$template->asset('js/dashboard.js')]
        ]
    ]);
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="<?= $template->url('dashboard') ?>" class="text-decoration-none">
                            <i class="fas fa-tachometer-alt"></i> Tableau de bord
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <i class="fas fa-book"></i> Mes Matières
                    </li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Mes Matières</h1>
            
            <?php if (empty($matieres)): ?>
                <div class="alert alert-info">
                    Vous n'avez pas encore de matières assignées.
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($matieres as $matiere): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0"><?= $template->e($matiere->getNom()) ?></h5>
                                    <span class="badge bg-primary"><?= count($matiere->getEtudiants()) ?> étudiants</span>
                                </div>
                                <div class="card-body">
                                    <?php if ($matiere->getDescription()): ?>
                                        <p class="card-text"><?= $template->e($matiere->getDescription()) ?></p>
                                    <?php else: ?>
                                        <p class="card-text text-muted">Aucune description disponible</p>
                                    <?php endif; ?>
                                    
                                    <div class="mt-3">
                                        <h6>Actions rapides :</h6>
                                        <div class="list-group">
                                            <a href="<?= $template->url('professor.matieres.show', ['id' => $matiere->getId()]) ?>" 
                                               class="list-group-item list-group-item-action">
                                                <i class="fas fa-users me-2"></i>
                                                Voir les étudiants
                                            </a>
                                            <a href="<?= $template->url('professor.matieres.evaluations.create', ['matiere_id' => $matiere->getId()]) ?>" 
                                               class="list-group-item list-group-item-action">
                                                <i class="fas fa-plus me-2"></i>
                                                Nouvelle évaluation
                                            </a>
                                            <a href="<?= $template->url('professor.matieres.evaluations.index', ['matiere_id' => $matiere->getId()]) ?>" 
                                               class="list-group-item list-group-item-action">
                                                <i class="fas fa-list me-2"></i>
                                                Voir les évaluations
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer text-muted">
                                    <small>Dernière évaluation : 
                                        <?php 
                                        $lastEval = $matiere->getLastEvaluation();
                                        echo $lastEval ? $template->formatDate($lastEval->getDate()) : 'Aucune';
                                        ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
})($this, $pageTitle ?? null, $user ?? null, $matieres ?? []);
?> 