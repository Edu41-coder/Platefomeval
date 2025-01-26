<?php

use Core\View\Template;
use App\Models\Entity\Matiere;
use App\Models\Entity\User;
use App\Models\Entity\Evaluation;

/**
 * @var Template $this
 * @var string $pageTitle
 * @var Matiere $matiere
 * @var User $student
 * @var array $evaluations
 * @var float $moyenne
 */

// Définir les assets
$assets = [
    'css' => [
        'css/dashboard.css',
        'css/professor/evaluations.css'
    ],
    'js' => [
        'js/dashboard.js'
    ]
];

// Récupérer les assets existants
$currentAssets = $this->get('assets') ?? ['css' => [], 'js' => []];

// Fusionner avec les nouveaux assets
$mergedAssets = [
    'css' => array_unique(array_merge($currentAssets['css'] ?? [], $assets['css'])),
    'js' => array_unique(array_merge($currentAssets['js'] ?? [], $assets['js']))
];

// Mettre à jour les assets partagés avant de configurer le layout
$this->shares(['assets' => $mergedAssets]);

// Configuration du layout avec tous les paramètres nécessaires
$this->layout('default', [
    'pageTitle' => $pageTitle ?? 'Évaluations de l\'étudiant',
    'site_title' => 'PlateformeEval',
    'user' => $user ?? null,
    'bodyClass' => 'evaluations-page',
    'pageDescription' => 'Page des évaluations de l\'étudiant'
]);

// Encapsulation dans une fonction anonyme
(function(
    $template, 
    $pageTitle, 
    $matiere, 
    $student, 
    $evaluations, 
    $moyenne,
    $sort,
    $order,
    $currentPage,
    $totalPages,
    $itemsPerPage,
    $totalResults
) {
?>

<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <h1 class="h3 mb-4">
            <?= $template->e($pageTitle) ?> - <?= $template->e($matiere->getNom()) ?>
        </h1>
        <p class="h5">
            Moyenne générale : <span class="fw-bold"><?= number_format($moyenne, 2) ?>/20</span>
        </p>
    </div>

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="evaluations-table">
            <thead>
                <tr>
                    <th class="text-center">
                        <?php
                        $dateParams = ['sort' => 'date_evaluation', 'order' => $sort === 'date_evaluation' && $order === 'ASC' ? 'DESC' : 'ASC'];
                        ?>
                        <a href="<?= $template->url('professor.matieres.evaluations.student', array_merge(
                            ['matiere_id' => $matiere->getId(), 'student_id' => $student->getId()],
                            $dateParams
                        )) ?>" class="text-decoration-none text-dark">
                            Date
                            <?php if ($sort === 'date_evaluation'): ?>
                                <i class="fas fa-sort-<?= $order === 'ASC' ? 'up' : 'down' ?>"></i>
                            <?php else: ?>
                                <i class="fas fa-sort text-muted"></i>
                            <?php endif; ?>
                        </a>
                    </th>
                    <th class="text-center">
                        <?php
                        $typeParams = ['sort' => 'type', 'order' => $sort === 'type' && $order === 'ASC' ? 'DESC' : 'ASC'];
                        ?>
                        <a href="<?= $template->url('professor.matieres.evaluations.student', array_merge(
                            ['matiere_id' => $matiere->getId(), 'student_id' => $student->getId()],
                            $typeParams
                        )) ?>" class="text-decoration-none text-dark">
                            Type
                            <?php if ($sort === 'type'): ?>
                                <i class="fas fa-sort-<?= $order === 'ASC' ? 'up' : 'down' ?>"></i>
                            <?php else: ?>
                                <i class="fas fa-sort text-muted"></i>
                            <?php endif; ?>
                        </a>
                    </th>
                    <th class="text-center">
                        <?php
                        $noteParams = ['sort' => 'note', 'order' => $sort === 'note' && $order === 'ASC' ? 'DESC' : 'ASC'];
                        ?>
                        <a href="<?= $template->url('professor.matieres.evaluations.student', array_merge(
                            ['matiere_id' => $matiere->getId(), 'student_id' => $student->getId()],
                            $noteParams
                        )) ?>" class="text-decoration-none text-dark">
                            Note
                            <?php if ($sort === 'note'): ?>
                                <i class="fas fa-sort-<?= $order === 'ASC' ? 'up' : 'down' ?>"></i>
                            <?php else: ?>
                                <i class="fas fa-sort text-muted"></i>
                            <?php endif; ?>
                        </a>
                    </th>
                    <th class="text-center">Commentaire</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($evaluations)): ?>
                    <tr>
                        <td colspan="4" class="no-evaluations">
                            Aucune évaluation disponible pour cet étudiant.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($evaluations as $evaluation): ?>
                        <tr>
                            <td>
                                <?= (new DateTime($evaluation->getDateEvaluation()))->format('d/m/Y') ?>
                            </td>
                            <td>
                                <?= $template->e($evaluation->getType()) ?>
                            </td>
                            <td>
                                <?= $evaluation->getNote() ? $evaluation->getNote()->getNote() . '/20' : '-' ?>
                            </td>
                            <td>
                                <?= $template->e($evaluation->getNote() ? ($evaluation->getNote()->getCommentaire() ?? '-') : '-') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if (!empty($evaluations)): ?>
        <?php
        $paginationParams = [
            'matiere_id' => $matiere->getId(),
            'student_id' => $student->getId(),
            'sort' => $sort ?? 'date_evaluation',
            'order' => $order ?? 'DESC'
        ];

        // Variables pour le composant de pagination
        $_currentPage = $currentPage;
        $_totalPages = $totalPages;
        $_itemsPerPage = $itemsPerPage;
        $_totalResults = $totalResults;
        $_params = $paginationParams;
        $_route = 'professor.matieres.evaluations.student';
        $template = $this;

        include __DIR__ . '/../../../../components/pagination.php';
        ?>
    <?php endif; ?>

    <div class="mt-6">
        <a href="<?= $template->url('professor.matieres.show', ['id' => $matiere->getId()]) ?>" 
           class="return-button">
            Retour à la liste des étudiants
        </a>
    </div>
</div>

<?php
// Appel de la fonction anonyme avec les variables nécessaires
})(
    $this, 
    $pageTitle ?? null, 
    $matiere ?? null, 
    $student ?? null, 
    $evaluations ?? [], 
    $moyenne ?? 0,
    $sort ?? 'date_evaluation',
    $order ?? 'DESC',
    $currentPage ?? 1,
    $totalPages ?? 1,
    $itemsPerPage ?? 10,
    $totalResults ?? 0
);
?> 