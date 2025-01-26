<?php
/**
 * @var \Core\View\Template $this
 * @var \App\Models\Entity\User $user
 * @var \App\Models\Entity\Photo|null $photo
 */

// Définir les assets nécessaires
$this->with('assets', [
    'css' => ['css/profile.css']
]);

// S'assurer que $photo est défini, même si null
$photo = $photo ?? null;
?>

<div class="container mt-4">
    <h1>Profil de <?= $this->e($user->getPrenom()) ?> <?= $this->e($user->getNom()) ?></h1>
    
    <?php if (isset($from_admin) && $from_admin): ?>
        <a href="<?= $this->url('admin') ?>" class="btn btn-outline-primary mb-3">
            <i class="fas fa-arrow-left me-2"></i>Retour à l'espace administrateur
        </a>
    <?php elseif (isset($matiere_id)): ?>
        <?php if (isset($from_notes) && $from_notes && isset($evaluation_id)): ?>
            <a href="<?= $this->url('professor.matieres.evaluations.notes', [
                'matiere_id' => $matiere_id,
                'id' => $evaluation_id
            ]) ?>" 
               class="btn btn-outline-primary mb-3">
                <i class="fas fa-arrow-left me-2"></i>Retour à la gestion des notes
            </a>
        <?php elseif (isset($from_details) && isset($evaluation_id)): ?>
            <a href="<?= $this->url('professor.matieres.evaluations.details', [
                'matiere_id' => $matiere_id,
                'id' => $evaluation_id
            ]) ?>" 
               class="btn btn-outline-primary mb-3">
                <i class="fas fa-arrow-left me-2"></i>Retour aux détails de l'évaluation
            </a>
        <?php elseif (isset($evaluation_create) && $evaluation_create === true): ?>
            <a href="<?= $this->url('professor.matieres.evaluations.create', ['matiere_id' => $matiere_id]) ?>" 
               class="btn btn-outline-primary mb-3">
                <i class="fas fa-arrow-left me-2"></i>Retour à la création d'évaluation
            </a>
        <?php else: ?>
            <a href="<?= $this->url('professor.matieres.show', ['id' => $matiere_id]) ?>" 
               class="btn btn-outline-primary mb-3">
                <i class="fas fa-arrow-left me-2"></i>Retour à la matière
            </a>
        <?php endif; ?>
    <?php else: ?>
        <a href="<?= $this->url('dashboard') ?>" class="btn btn-outline-primary mb-3">
            <i class="fas fa-arrow-left me-2"></i>Retour au tableau de bord
        </a>
    <?php endif; ?>

    <!-- Section Photo de profil -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Photo de profil</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <?php if ($photo): ?>
                        <img src="<?= PUBLIC_PATH . '/' . $this->e($photo->getFullPath()) . '?v=' . time() ?>" 
                             alt="Photo de profil de <?= $this->e($user->getPrenom()) ?>" 
                             class="img-fluid rounded mb-3">
                    <?php else: ?>
                        <div class="text-center p-4 bg-light rounded">
                            <i class="fas fa-user fa-4x text-muted mb-3"></i>
                            <p class="text-muted">Aucune photo de profil</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Informations du profil -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Informations</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Nom :</strong> <?= $this->e($user->getNom()) ?></p>
                    <p><strong>Prénom :</strong> <?= $this->e($user->getPrenom()) ?></p>
                    <p><strong>Email :</strong> <?= $this->e($user->getEmail()) ?></p>
                    <p>
                        <strong>Adresse :</strong> 
                        <?php if ($user->getAdresse()): ?>
                            <?= $this->e($user->getAdresse()) ?>
                        <?php else: ?>
                            <span class="text-muted fst-italic">Pas d'adresse insérée</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div> 