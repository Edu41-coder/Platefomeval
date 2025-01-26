<?php

use App\Models\Entity\Matiere;
use App\Models\User;
use App\Models\Entity\Evaluation;
use Core\View\Template;

/**
 * @var Template $this
 * @var string $pageTitle
 * @var array $user
 * @var Matiere $matiere La matière sélectionnée
 * @var Evaluation[] $evaluations Liste des évaluations
 * @var int $currentPage Page actuelle
 * @var int $totalPages Nombre total de pages
 * @var int $itemsPerPage Éléments par page
 * @var int $totalResults Nombre total de résultats
 * @var string $sort Colonne de tri
 * @var string $order Direction du tri
 * @var string $csrfToken Token CSRF
 */

// Encapsulation dans une fonction anonyme
(function ($template, $pageTitle, $user, $matiere, $evaluations, $currentPage, $totalPages, $itemsPerPage, $totalResults, $sort, $order, $csrfToken) {
    // Configuration du layout
    $template->layout('default', [
        'title' => $pageTitle ?? 'Évaluations - ' . $matiere->getNom(),
        'user' => $user ?? null,
        'additionalAssets' => [
            'css' => [
                'assets/css/dashboard.css'
            ],
            'js' => [
                'assets/js/dashboard.js',
                'assets/js/professor/evaluations_tri.js'
            ]
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
                    <li class="breadcrumb-item">
                        <a href="<?= $template->url('professor.matieres') ?>" class="text-decoration-none">
                            <i class="fas fa-book"></i> Mes Matières
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="<?= $template->url('professor.matieres.show', ['id' => $matiere->getId()]) ?>" class="text-decoration-none">
                            <?= $template->e($matiere->getNom()) ?>
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <i class="fas fa-tasks"></i> Évaluations
                    </li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="mb-3">Évaluations - <?= $template->e($matiere->getNom()) ?></h1>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="<?= $template->url('professor.matieres.evaluations.create', ['matiere_id' => $matiere->getId()]) ?>" 
               class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>
                Nouvelle évaluation
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Liste des évaluations</h5>
                    <span class="badge bg-primary"><?= $totalResults ?> évaluation<?= $totalResults > 1 ? 's' : '' ?></span>
                </div>
                <div class="card-body">
                    <?php if (empty($evaluations)): ?>
                        <div class="alert alert-info">
                            Aucune évaluation n'a été créée pour cette matière.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>
                                            <a href="<?= $template->url('professor.matieres.evaluations.index', array_merge(
                                                ['matiere_id' => $matiere->getId()],
                                                ['sort' => 'date_evaluation', 'order' => $sort === 'date_evaluation' && $order === 'ASC' ? 'DESC' : 'ASC']
                                            )) ?>" style="cursor: pointer; display: inline-flex; align-items: center; background: none; border: none; padding: 0.5rem; color: #212529; font-weight: 600; text-decoration: none; transition: all 0.2s;">
                                                Date
                                                <?php if ($sort === 'date_evaluation'): ?>
                                                    <i class="fas fa-sort-<?= strtolower($order) === 'asc' ? 'up' : 'down' ?>" style="margin-left: 0.5rem; opacity: 1;"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-sort" style="margin-left: 0.5rem; opacity: 0.5;"></i>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="<?= $template->url('professor.matieres.evaluations.index', array_merge(
                                                ['matiere_id' => $matiere->getId()],
                                                ['sort' => 'type', 'order' => $sort === 'type' && $order === 'ASC' ? 'DESC' : 'ASC']
                                            )) ?>" style="cursor: pointer; display: inline-flex; align-items: center; background: none; border: none; padding: 0.5rem; color: #212529; font-weight: 600; text-decoration: none; transition: all 0.2s;">
                                                Type
                                                <?php if ($sort === 'type'): ?>
                                                    <i class="fas fa-sort-<?= strtolower($order) === 'asc' ? 'up' : 'down' ?>" style="margin-left: 0.5rem; opacity: 1;"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-sort" style="margin-left: 0.5rem; opacity: 0.5;"></i>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th style="padding:  1rem; color: #212529; font-weight: 600;">Description</th>
                                        <th>
                                            <a href="<?= $template->url('professor.matieres.evaluations.index', array_merge(
                                                ['matiere_id' => $matiere->getId()],
                                                ['sort' => 'moyenne_classe', 'order' => $sort === 'moyenne_classe' && $order === 'ASC' ? 'DESC' : 'ASC']
                                            )) ?>" style="cursor: pointer; display: inline-flex; align-items: center; background: none; border: none; padding: 0.5rem; color: #212529; font-weight: 600; text-decoration: none; transition: all 0.2s;">
                                                Moyenne classe
                                                <?php if ($sort === 'moyenne_classe'): ?>
                                                    <i class="fas fa-sort-<?= strtolower($order) === 'asc' ? 'up' : 'down' ?>" style="margin-left: 0.5rem; opacity: 1;"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-sort" style="margin-left: 0.5rem; opacity: 0.5;"></i>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($evaluations as $evaluation): ?>
                                        <tr>
                                            <td><?= date('d/m/Y', strtotime($evaluation->getDate())) ?></td>
                                            <td><?= $template->e($evaluation->getType()) ?></td>
                                            <td><?= $template->e($evaluation->getDescription()) ?></td>
                                            <td><?= $evaluation->moyenne_classe ? number_format($evaluation->moyenne_classe, 2) : '-' ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?= $template->url('professor.matieres.evaluations.details', [
                                                        'matiere_id' => $matiere->getId(),
                                                        'id' => $evaluation->getId()
                                                    ]) ?>" 
                                                       class="btn btn-outline-info" 
                                                       title="Voir les détails">
                                                        <i class="fas fa-eye"></i>
                                                    </a>

                                                    <?php if ($evaluation->getProfessorId() === $user->getId()): ?>
                                                        <a href="<?= $template->url('professor.matieres.evaluations.edit', [
                                                            'matiere_id' => $matiere->getId(),
                                                            'id' => $evaluation->getId()
                                                        ]) ?>" 
                                                           class="btn btn-outline-primary" 
                                                           title="Modifier">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        
                                                        <a href="<?= $template->url('professor.matieres.evaluations.notes', [
                                                            'matiere_id' => $matiere->getId(),
                                                            'id' => $evaluation->getId()
                                                        ]) ?>" 
                                                           class="btn btn-outline-success" 
                                                           title="Gérer les notes">
                                                            <i class="fas fa-graduation-cap"></i>
                                                        </a>
                                                        
                                                        <button type="button" 
                                                                class="btn btn-outline-danger" 
                                                                title="Supprimer"
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#deleteModal<?= $evaluation->getId() ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>

                                        <?php if ($evaluation->getProfessorId() === $user->getId()): ?>
                                            <div class="modal fade" id="deleteModal<?= $evaluation->getId() ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Confirmer la suppression</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            Êtes-vous sûr de vouloir supprimer cette évaluation ?
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                            <form action="<?= $template->url('professor.matieres.evaluations.delete', ['matiere_id' => $matiere->getId()]) ?>" 
                                                                  method="POST">
                                                                <input type="hidden" name="evaluation_id" value="<?= $evaluation->getId() ?>">
                                                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                                <button type="submit" class="btn btn-danger">Supprimer</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php 
                        $paginationParams = [
                            'matiere_id' => $matiere->getId(),
                            'sort' => $sort ?? 'date_evaluation',
                            'order' => $order ?? 'DESC'
                        ];

                        $_currentPage = $currentPage;
                        $_totalPages = $totalPages;
                        $_itemsPerPage = $itemsPerPage;
                        $_totalResults = $totalResults;
                        $_params = $paginationParams;
                        $_route = 'professor.matieres.evaluations.index';
                        $_template = $template;

                        include __DIR__ . '/../../../../components/pagination.php';
                        ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
})($this, $pageTitle ?? null, $user ?? null, $matiere ?? null, $evaluations ?? [], 
   $currentPage ?? 1, $totalPages ?? 1, $itemsPerPage ?? 10, $totalResults ?? 0,
   $sort ?? 'date_evaluation', $order ?? 'DESC', $csrfToken ?? '');
?>