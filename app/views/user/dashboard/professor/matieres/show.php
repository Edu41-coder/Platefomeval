<?php

use App\Models\Entity\Matiere;
use App\Models\User;
use Core\View\Template;

/**
 * @var Template $this
 * @var string $pageTitle
 * @var array $user
 * @var Matiere $matiere La matière sélectionnée
 * @var array $etudiants Liste des étudiants inscrits
 * @var string $sort Colonne de tri actuelle
 * @var string $order Direction du tri (ASC/DESC)
 * @var int $currentPage Page actuelle
 * @var int $totalPages Nombre total de pages
 * @var int $itemsPerPage Éléments par page
 * @var int $totalResults Nombre total de résultats
 */

// Encapsulation dans une fonction anonyme
(function ($template, $pageTitle, $user, $matiere, $etudiantsData, $sort, $order, $currentPage, $totalPages, $itemsPerPage, $totalResults) {

// Configuration du layout
$template->layout('default', [
    'title' => $pageTitle ?? $matiere->getNom(),
    'user' => $user ?? null,
    'assets' => [
        'css' => ['assets/css/dashboard.css'],
        'js' => [
            'assets/js/dashboard.js',
            'assets/js/professor/matieres.js'
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
                    <li class="breadcrumb-item active" aria-current="page"><?= $template->e($matiere->getNom()) ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="mb-3"><?= $template->e($matiere->getNom()) ?></h1>
            <?php if ($matiere->getDescription()): ?>
                <p class="lead"><?= $template->e($matiere->getDescription()) ?></p>
            <?php endif; ?>
        </div>
        <div class="col-md-4 text-md-end">
            <div class="btn-group" role="group">
                <a href="<?= $template->url('professor.matieres.evaluations.create', ['matiere_id' => $matiere->getId()]) ?>" 
                   class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>
                    Nouvelle évaluation
                </a>
                <a href="<?= $template->url('professor.matieres.evaluations.index', ['matiere_id' => $matiere->getId()]) ?>" 
                   class="btn btn-outline-primary">
                    <i class="fas fa-list me-2"></i>
                    Voir les évaluations
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Liste des étudiants inscrits</h5>
                    <span class="badge bg-primary"><?= $totalResults ?> étudiants</span>
                </div>
                <div class="card-body">
                    <?php if (empty($etudiantsData)): ?>
                        <div class="alert alert-info">
                            Aucun étudiant n'est inscrit à cette matière pour le moment.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th style="width: 15%">
                                            <?php
                                            $currentParams = ['sort' => 'nom', 'order' => $sort === 'nom' && $order === 'ASC' ? 'DESC' : 'ASC'];
                                            ?>
                                            <a href="<?= $template->url('professor.matieres.show', array_merge(['id' => $matiere->getId()], $currentParams)) ?>" 
                                               class="text-decoration-none text-dark">
                                                Nom
                                                <?php if ($sort === 'nom'): ?>
                                                    <i class="fas fa-sort-<?= $order === 'ASC' ? 'up' : 'down' ?>"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-sort text-muted"></i>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th style="width: 15%">
                                            <?php
                                            $currentParams = ['sort' => 'prenom', 'order' => $sort === 'prenom' && $order === 'ASC' ? 'DESC' : 'ASC'];
                                            ?>
                                            <a href="<?= $template->url('professor.matieres.show', array_merge(['id' => $matiere->getId()], $currentParams)) ?>"
                                               class="text-decoration-none text-dark">
                                                Prénom
                                                <?php if ($sort === 'prenom'): ?>
                                                    <i class="fas fa-sort-<?= $order === 'ASC' ? 'up' : 'down' ?>"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-sort text-muted"></i>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th style="width: 25%">
                                            <?php
                                            $currentParams = ['sort' => 'email', 'order' => $sort === 'email' && $order === 'ASC' ? 'DESC' : 'ASC'];
                                            ?>
                                            <a href="<?= $template->url('professor.matieres.show', array_merge(['id' => $matiere->getId()], $currentParams)) ?>"
                                               class="text-decoration-none text-dark">
                                                Email
                                                <?php if ($sort === 'email'): ?>
                                                    <i class="fas fa-sort-<?= $order === 'ASC' ? 'up' : 'down' ?>"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-sort text-muted"></i>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th style="width: 10%">
                                            <?php
                                            $currentParams = ['sort' => 'moyenne', 'order' => $sort === 'moyenne' && $order === 'ASC' ? 'DESC' : 'ASC'];
                                            ?>
                                            <a href="<?= $template->url('professor.matieres.show', array_merge(['id' => $matiere->getId()], $currentParams)) ?>"
                                               class="text-decoration-none text-dark">
                                                Moyenne
                                                <?php if ($sort === 'moyenne'): ?>
                                                    <i class="fas fa-sort-<?= $order === 'ASC' ? 'up' : 'down' ?>"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-sort text-muted"></i>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th style="width: 15%">
                                            <?php
                                            $currentParams = ['sort' => 'derniere_eval', 'order' => $sort === 'derniere_eval' && $order === 'ASC' ? 'DESC' : 'ASC'];
                                            ?>
                                            <a href="<?= $template->url('professor.matieres.show', array_merge(['id' => $matiere->getId()], $currentParams)) ?>"
                                               class="text-decoration-none text-dark">
                                                Dernière évaluation
                                                <?php if ($sort === 'derniere_eval'): ?>
                                                    <i class="fas fa-sort-<?= $order === 'ASC' ? 'up' : 'down' ?>"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-sort text-muted"></i>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th style="width: 20%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($etudiantsData as $etudiantData): ?>
                                        <tr>
                                            <td><?= $etudiantData['etudiant']->getNom() ?></td>
                                            <td><?= $etudiantData['etudiant']->getPrenom() ?></td>
                                            <td><?= $etudiantData['etudiant']->getEmail() ?></td>
                                            <td>
                                                <?php if (is_numeric($etudiantData['moyenne'])): ?>
                                                    <span class="badge bg-<?= $etudiantData['moyenne'] >= 10 ? 'success' : 'danger' ?>">
                                                        <?= number_format($etudiantData['moyenne'], 2) ?>/20
                                                    </span>
                                                <?php else: ?>
                                                    Non évalué
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $etudiantData['derniere_eval'] ?: 'Aucune' ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?= $template->url('profile.view', [
                                                        'id' => $etudiantData['etudiant']->getId(),
                                                        'matiere_id' => $matiere->getId()
                                                    ]) ?>" 
                                                       class="btn btn-outline-info" 
                                                       title="Voir le profil">
                                                        <i class="fas fa-user"></i>
                                                    </a>
                                                    <a href="<?= $template->url('professor.matieres.evaluations.student', [
                                                        'matiere_id' => $matiere->getId(),
                                                        'student_id' => $etudiantData['etudiant']->getId()
                                                    ]) ?>" 
                                                       class="btn btn-outline-primary" 
                                                       title="Voir les évaluations">
                                                        <i class="fas fa-chart-line"></i>
                                                    </a>
                                                    <a href="<?= $template->url('professor.matieres.evaluations.create', [
                                                        'matiere_id' => $matiere->getId(),
                                                        'etudiant_id' => $etudiantData['etudiant']->getId()
                                                    ]) ?>" 
                                                       class="btn btn-outline-success" 
                                                       title="Nouvelle évaluation">
                                                        <i class="fas fa-plus"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php 
                        $paginationParams = [
                            'id' => $matiere->getId(),
                            'sort' => $sort ?? 'nom',
                            'order' => $order ?? 'ASC'
                        ];

                        // Variables pour le composant de pagination
                        $_currentPage = $currentPage;
                        $_totalPages = $totalPages;
                        $_itemsPerPage = $itemsPerPage;
                        $_totalResults = $totalResults;
                        $_params = $paginationParams;
                        $_route = 'professor.matieres.show';
                        $template = $this;

                        include __DIR__ . '/../../../../components/pagination.php';
                        ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
})($this, $pageTitle ?? null, $user ?? null, $matiere ?? null, $etudiantsData ?? [], $sort ?? 'nom', $order ?? 'ASC', $currentPage ?? 1, $totalPages ?? 1, $itemsPerPage ?? 10, $totalResults ?? 0);
?> 