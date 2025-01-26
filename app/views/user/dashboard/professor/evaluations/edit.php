<?php
/**
 * @var Template $this
 * @var string $pageTitle
 * @var array $user
 * @var Matiere $matiere
 * @var Evaluation $evaluation
 * @var array $errors
 * @var string $csrfToken
 */

use App\Models\Entity\Matiere;
use App\Models\Entity\Evaluation;
use Core\View\Template;

// Initialisation des variables nécessaires
$formAction = $this->url('professor.matieres.evaluations.update', [
    'matiere_id' => $matiere->getId(),
    'id' => $evaluation->getId()
]);

// Logs de debug
error_log('=== FORM DEBUG ===');
error_log('Evaluation ID: ' . $evaluation->getId());
error_log('Form action: ' . $formAction);
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="<?= $this->url('dashboard') ?>" class="text-decoration-none">
                            <i class="fas fa-tachometer-alt"></i> Tableau de bord
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="<?= $this->url('professor.matieres') ?>" class="text-decoration-none">
                            <i class="fas fa-book"></i> Mes Matières
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="<?= $this->url('professor.matieres.show', ['id' => $matiere->getId()]) ?>" class="text-decoration-none">
                            <?= $this->e($matiere->getNom()) ?>
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="<?= $this->url('professor.matieres.evaluations.index', ['matiere_id' => $matiere->getId()]) ?>" class="text-decoration-none">
                            <i class="fas fa-tasks"></i> Évaluations
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Modifier l'évaluation</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h1 class="h3 mb-0">Modifier l'évaluation - <?= $this->e($matiere->getNom()) ?></h1>
                </div>
                <div class="card-body">
                    <?php if (isset($errors) && !empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= $this->e($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="<?= $this->url('professor.matieres.evaluations.update', [
                        'matiere_id' => $matiere->getId()
                    ]) ?>" method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="id" value="<?= $evaluation->getId() ?>">
                        <input type="hidden" name="csrf_token" value="<?= $this->e($csrfToken) ?>">
                        <input type="hidden" name="_method" value="PUT">

                        <div class="mb-3">
                            <label for="type" class="form-label">Type d'évaluation *</label>
                            <select class="form-select <?= isset($errors['type']) ? 'is-invalid' : '' ?>" 
                                    id="type" 
                                    name="type" 
                                    required>
                                <?php 
                                $selectedType = $old['type'] ?? $evaluation->getType();
                                $types = ['Contrôle continu', 'Examen', 'TP', 'Projet', 'Oral'];
                                foreach ($types as $type): 
                                ?>
                                    <option value="<?= $type ?>" <?= $selectedType === $type ? 'selected' : '' ?>>
                                        <?= $type ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['type'])): ?>
                                <div class="invalid-feedback"><?= $errors['type'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>" 
                                      id="description" 
                                      name="description" 
                                      rows="3"><?= $this->e($old['description'] ?? $evaluation->getDescription()) ?></textarea>
                            <?php if (isset($errors['description'])): ?>
                                <div class="invalid-feedback"><?= $errors['description'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="date_evaluation" class="form-label">Date de l'évaluation *</label>
                            <input type="date" 
                                   class="form-control <?= isset($errors['date_evaluation']) ? 'is-invalid' : '' ?>" 
                                   id="date_evaluation" 
                                   name="date_evaluation" 
                                   value="<?= $this->e($old['date_evaluation'] ?? $evaluation->getDate()) ?>"
                                   required>
                            <?php if (isset($errors['date_evaluation'])): ?>
                                <div class="invalid-feedback"><?= $errors['date_evaluation'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="<?= $this->url('professor.matieres.evaluations.index', ['matiere_id' => $matiere->getId()]) ?>" 
                               class="btn btn-secondary">
                                Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Configuration du layout
$this->layout('default', [
    'title' => $pageTitle ?? 'Modifier l\'évaluation',
    'user' => $user ?? null,
    'csrfToken' => $csrfToken ?? null,
    'assets' => [
        'css' => ['assets/css/dashboard.css'],
        'js' => [
            'assets/js/dashboard.js',
            'assets/js/professor/evaluations.js'
        ]
    ]
]);
?>