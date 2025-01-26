<?php

use App\Models\Entity\Matiere;
use App\Models\User;
use Core\View\Template;

/**
 * @var Template $this
 * @var string $pageTitle
 * @var array $user
 * @var Matiere $matiere La matière sélectionnée
 * @var array $etudiants Liste des étudiants de la matière
 * @var array $errors Les erreurs de validation du formulaire
 * @var string $csrfToken Le jeton CSRF
 */
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
                    <li class="breadcrumb-item active" aria-current="page">Nouvelle évaluation</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h1 class="h3 mb-0">Nouvelle évaluation - <?= $this->e($matiere->getNom()) ?></h1>
                </div>
                <div class="card-body">
                    <form action="<?= $this->url('professor.matieres.evaluations.store', ['matiere_id' => $matiere->getId()]) ?>" method="POST" class="needs-validation" novalidate>
                        <!-- CSRF token -->
                        <input type="hidden" name="csrf_token" value="<?= $this->e($csrfToken) ?>">
                        <input type="hidden" name="matiere_id" value="<?= $matiere->getId() ?>">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="type" class="form-label">Type d'évaluation *</label>
                                <select id="type" 
                                        name="type" 
                                        class="form-select <?= isset($errors['type']) ? 'is-invalid' : '' ?>" 
                                        required>
                                    <option value="">Choisir un type</option>
                                    <?php
                                    $validTypes = [
                                        'Examen',
                                        'Contrôle continu',
                                        'TP',
                                        'Projet',
                                        'Oral'
                                    ];
                                    
                                    // Récupérer et nettoyer la valeur POST
                                    $selectedType = isset($_POST['type']) ? 
                                        trim(html_entity_decode($_POST['type'], ENT_QUOTES | ENT_HTML5, 'UTF-8')) : '';
                                    
                                    foreach ($validTypes as $type): 
                                        // Normaliser la comparaison pour la sélection
                                        $isSelected = (mb_strtolower($type) === mb_strtolower($selectedType));
                                    ?>
                                        <option value="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>" 
                                                <?= $isSelected ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['type'])): ?>
                                    <div class="invalid-feedback"><?= $errors['type'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="date" class="form-label">Date de l'évaluation *</label>
                            <input type="date" 
                                   class="form-control <?= isset($errors['date']) ? 'is-invalid' : '' ?>" 
                                   id="date" 
                                   name="date" 
                                   value="<?= $_POST['date'] ?? date('Y-m-d') ?>"
                                   required>
                            <?php if (isset($errors['date'])): ?>
                                <div class="invalid-feedback"><?= $errors['date'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>" 
                                      id="description" 
                                      name="description" 
                                      rows="3"><?= $_POST['description'] ?? '' ?></textarea>
                            <?php if (isset($errors['description'])): ?>
                                <div class="invalid-feedback"><?= $errors['description'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Notes des étudiants</label>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>
                                                <a href="<?= $this->url('professor.matieres.evaluations.create', [
                                                    'matiere_id' => $matiere->getId(),
                                                    'sort' => 'nom',
                                                    'order' => ($sort === 'nom' && $order === 'ASC') ? 'DESC' : 'ASC'
                                                ]) ?>" class="text-decoration-none text-dark">
                                                    Nom
                                                    <?php if ($sort === 'nom'): ?>
                                                        <i class="fas fa-sort-<?= $order === 'ASC' ? 'up' : 'down' ?>"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-sort text-muted"></i>
                                                    <?php endif; ?>
                                                </a>
                                            </th>
                                            <th>
                                                <a href="<?= $this->url('professor.matieres.evaluations.create', [
                                                    'matiere_id' => $matiere->getId(),
                                                    'sort' => 'prenom',
                                                    'order' => ($sort === 'prenom' && $order === 'ASC') ? 'DESC' : 'ASC'
                                                ]) ?>" class="text-decoration-none text-dark">
                                                    Prénom
                                                    <?php if ($sort === 'prenom'): ?>
                                                        <i class="fas fa-sort-<?= $order === 'ASC' ? 'up' : 'down' ?>"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-sort text-muted"></i>
                                                    <?php endif; ?>
                                                </a>
                                            </th>
                                            <th>Note</th>
                                            <th>Commentaire</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        
                                        // Vérifier si $etudiants est déjà un tableau ou s'il faut accéder à la clé 'etudiants'
                                        $etudiantsList = is_array($etudiants) ? $etudiants : [];
                                        
                                        if (!empty($etudiantsList)):
                                            foreach ($etudiantsList as $etudiant): 
                                        ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <a href="<?= $this->url('profile.view', [
                                                            'id' => $etudiant->getId(),
                                                            'matiere_id' => $matiere->getId(),
                                                            'evaluation_create' => true
                                                        ]) ?>" 
                                                           class="btn btn-outline-info btn-sm me-2" 
                                                           title="Voir le profil">
                                                            <i class="fas fa-user"></i>
                                                        </a>
                                                        <?= $this->e($etudiant->getNom()) ?>
                                                    </div>
                                                </td>
                                                <td><?= $this->e($etudiant->getPrenom()) ?></td>
                                                <td>
                                                    <input type="number" 
                                                           class="form-control form-control-sm" 
                                                           name="notes[<?= $etudiant->getId() ?>]" 
                                                           min="0" 
                                                           max="20" 
                                                           step="0.5">
                                                </td>
                                                <td>
                                                    <input type="text" 
                                                           class="form-control form-control-sm" 
                                                           name="commentaires[<?= $etudiant->getId() ?>]" 
                                                           maxlength="255">
                                                </td>
                                            </tr>
                                        <?php 
                                            endforeach;
                                        else:
                                        ?>
                                            <tr>
                                                <td colspan="4" class="text-center">Aucun étudiant trouvé</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="<?= $this->url('professor.matieres.evaluations.index', ['matiere_id' => $matiere->getId()]) ?>" 
                               class="btn btn-secondary">
                                Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                Enregistrer l'évaluation
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
    'title' => $pageTitle ?? 'Nouvelle évaluation',
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