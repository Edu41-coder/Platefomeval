<?php
/**
 * @var \Core\View\Template $this
 * @var string $pageTitle
 * @var array $evaluations
 * @var array $matieres
 * @var int|null $selectedMatiereId
 */

// S'assurer que selectedMatiereId est défini
$selectedMatiereId = $selectedMatiereId ?? null;

// Configuration du layout
$this->layout('default', [
    'title' => $pageTitle ?? 'Toutes mes évaluations',
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
                    <li class="breadcrumb-item active">Toutes mes évaluations</li>
                </ol>
            </nav>

            <h1 class="mb-4">Toutes mes évaluations</h1>

            <!-- Filtre par matière -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="get" action="<?= $this->url('student.evaluations.all') ?>" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="matiere_id" class="form-label">Filtrer par matière</label>
                            <select name="matiere_id" id="matiere_id" class="form-select">
                                <option value="">Toutes les matières</option>
                                <?php foreach ($matieres as $matiere): ?>
                                    <option value="<?= $matiere->getId() ?>" 
                                            <?= ($selectedMatiereId !== null && $selectedMatiereId === $matiere->getId()) ? 'selected' : '' ?>>
                                        <?= $this->e($matiere->getNom()) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- Ajouter les champs cachés pour conserver le tri -->
                        <?php if (isset($_GET['sort'])): ?>
                            <input type="hidden" name="sort" value="<?= $this->e($_GET['sort']) ?>">
                        <?php endif; ?>
                        <?php if (isset($_GET['order'])): ?>
                            <input type="hidden" name="order" value="<?= $this->e($_GET['order']) ?>">
                        <?php endif; ?>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary">Filtrer</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (empty($evaluations)): ?>
                <div class="alert alert-info">
                    Aucune évaluation n'est disponible.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>
                                    <?php
                                    $sortParams = [
                                        'sort' => 'matiere',
                                        'order' => isset($_GET['sort']) && $_GET['sort'] === 'matiere' && $_GET['order'] === 'asc' ? 'desc' : 'asc'
                                    ];
                                    // N'ajouter matiere_id que s'il est défini et non vide
                                    if ($selectedMatiereId) {
                                        $sortParams['matiere_id'] = $selectedMatiereId;
                                    }
                                    ?>
                                    <a href="<?= $this->url('student.evaluations.all', $sortParams) ?>" class="text-decoration-none text-dark">
                                        Matière
                                        <?php if (isset($_GET['sort']) && $_GET['sort'] === 'matiere'): ?>
                                            <i class="fas fa-sort-<?= $_GET['order'] === 'asc' ? 'up' : 'down' ?>"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <?php
                                    $sortParams = [
                                        'sort' => 'date',
                                        'order' => isset($_GET['sort']) && $_GET['sort'] === 'date' && $_GET['order'] === 'asc' ? 'desc' : 'asc'
                                    ];
                                    if ($selectedMatiereId) {
                                        $sortParams['matiere_id'] = $selectedMatiereId;
                                    }
                                    ?>
                                    <a href="<?= $this->url('student.evaluations.all', $sortParams) ?>" class="text-decoration-none text-dark">
                                        Date
                                        <?php if (isset($_GET['sort']) && $_GET['sort'] === 'date'): ?>
                                            <i class="fas fa-sort-<?= $_GET['order'] === 'asc' ? 'up' : 'down' ?>"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <?php
                                    $sortParams = [
                                        'sort' => 'type',
                                        'order' => isset($_GET['sort']) && $_GET['sort'] === 'type' && $_GET['order'] === 'asc' ? 'desc' : 'asc'
                                    ];
                                    if ($selectedMatiereId) {
                                        $sortParams['matiere_id'] = $selectedMatiereId;
                                    }
                                    ?>
                                    <a href="<?= $this->url('student.evaluations.all', $sortParams) ?>" class="text-decoration-none text-dark">
                                        Type
                                        <?php if (isset($_GET['sort']) && $_GET['sort'] === 'type'): ?>
                                            <i class="fas fa-sort-<?= $_GET['order'] === 'asc' ? 'up' : 'down' ?>"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>Description</th>
                                <th>
                                    <?php
                                    $sortParams = [
                                        'sort' => 'note',
                                        'order' => isset($_GET['sort']) && $_GET['sort'] === 'note' && $_GET['order'] === 'asc' ? 'desc' : 'asc'
                                    ];
                                    if ($selectedMatiereId) {
                                        $sortParams['matiere_id'] = $selectedMatiereId;
                                    }
                                    ?>
                                    <a href="<?= $this->url('student.evaluations.all', $sortParams) ?>" class="text-decoration-none text-dark">
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
                                    <td><?= $this->e($evaluation->getMatiereName()) ?></td>
                                    <td><?= (new DateTime($evaluation->getDateEvaluation()))->format('d/m/Y') ?></td>
                                    <td><?= $this->e($evaluation->getType()) ?></td>
                                    <td><?= $this->e($evaluation->getDescription()) ?></td>
                                    <td>
                                        <?php if ($evaluation->getNote()): ?>
                                            <span class="badge bg-<?= $evaluation->getNote()->getNote() >= 10 ? 'success' : 'danger' ?>">
                                                <?= number_format($evaluation->getNote()->getNote(), 2) ?>/20
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">En attente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $this->e($evaluation->getNote() ? $evaluation->getNote()->getCommentaire() : '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div> 