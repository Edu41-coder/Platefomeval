<?php
/**
 * @var \Core\View\Template $this
 * @var string $pageTitle
 * @var array $user
 * @var array $evaluations
 * @var \App\Models\Entity\Matiere $matiere
 */

// Configuration du layout
$this->layout('default', [
    'title' => $pageTitle ?? 'Mes évaluations',
    'user' => $user ?? null,
    'assets' => [
        'css' => ['assets/css/dashboard.css'],
        'js' => ['assets/js/dashboard.js']
    ]
]);
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="<?= $this->url('dashboard') ?>" class="text-decoration-none">
                            <i class="fas fa-tachometer-alt"></i> Tableau de bord
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="<?= $this->url('student.matieres') ?>" class="text-decoration-none">
                            <i class="fas fa-book"></i> Mes matières
                        </a>
                    </li>
                    <li class="breadcrumb-item active">
                        <i class="fas fa-tasks"></i> <?= $this->e($matiere->getNom()) ?>
                    </li>
                </ol>
            </nav>

            <h1 class="mb-4">
                <i class="fas fa-tasks"></i> Mes évaluations <?= isset($matiere) ? ' - ' . $this->e($matiere->getNom()) : '' ?>
            </h1>

            <?php if (empty($evaluations)): ?>
                <div class="alert alert-info">
                    Aucune évaluation n'est disponible pour le moment.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>
                                    <a href="<?= $this->url('student.evaluations', [
                                        'matiere_id' => $matiere->getId(),
                                        'sort' => 'date',
                                        'order' => isset($_GET['sort']) && $_GET['sort'] === 'date' && $_GET['order'] === 'asc' ? 'desc' : 'asc'
                                    ]) ?>" class="text-decoration-none text-dark">
                                        Date
                                        <?php if (isset($_GET['sort']) && $_GET['sort'] === 'date'): ?>
                                            <i class="fas fa-sort-<?= $_GET['order'] === 'asc' ? 'up' : 'down' ?>"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= $this->url('student.evaluations', [
                                        'matiere_id' => $matiere->getId(),
                                        'sort' => 'type',
                                        'order' => isset($_GET['sort']) && $_GET['sort'] === 'type' && $_GET['order'] === 'asc' ? 'desc' : 'asc'
                                    ]) ?>" class="text-decoration-none text-dark">
                                        Type
                                        <?php if (isset($_GET['sort']) && $_GET['sort'] === 'type'): ?>
                                            <i class="fas fa-sort-<?= $_GET['order'] === 'asc' ? 'up' : 'down' ?>"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort"></i>
                                        <?php endif; ?>
                                    </th>
                                <th>Description</th>
                                <th>
                                    <a href="<?= $this->url('student.evaluations', [
                                        'matiere_id' => $matiere->getId(),
                                        'sort' => 'note',
                                        'order' => isset($_GET['sort']) && $_GET['sort'] === 'note' && $_GET['order'] === 'asc' ? 'desc' : 'asc'
                                    ]) ?>" class="text-decoration-none text-dark">
                                        Note
                                        <?php if (isset($_GET['sort']) && $_GET['sort'] === 'note'): ?>
                                            <i class="fas fa-sort-<?= $_GET['order'] === 'asc' ? 'up' : 'down' ?>"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>Commentaire</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($evaluations as $evaluation): ?>
                                <tr>
                                    <td><?= (new DateTime($evaluation['date_evaluation']))->format('d/m/Y') ?></td>
                                    <td><?= $this->e($evaluation['type']) ?></td>
                                    <td><?= $this->e($evaluation['description']) ?></td>
                                    <td>
                                        <?php if (isset($evaluation['note'])): ?>
                                            <span class="badge bg-<?= $evaluation['note'] >= 10 ? 'success' : 'danger' ?>">
                                                <?= number_format($evaluation['note'], 2) ?>/20
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">En attente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $this->e($evaluation['commentaire'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <?php if (isset($moyenne)): ?>
                            <tfoot>
                                <tr class="table-primary">
                                    <td colspan="3" class="text-end"><strong>Moyenne :</strong></td>
                                    <td colspan="2">
                                        <strong><?= number_format($moyenne, 2) ?>/20</strong>
                                    </td>
                                </tr>
                            </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div> 