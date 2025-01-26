<?php
/**
 * @var \Core\View\Template $this
 * @var \App\Models\Entity\User $user
 * @var \App\Models\Entity\Photo|null $photo
 * @var string $csrfToken
 */

// Définir les assets nécessaires
$this->with('assets', [
    'css' => ['css/profile.css'],
    'js' => ['js/profile.js']
]);

// S'assurer que $photo est défini, même si null
$photo = $photo ?? null;
?>

<div class="container mt-4">
    <h1>Mon profil</h1>
    <a href="<?= url('dashboard') ?>" class="btn btn-outline-primary mb-3">
        <i class="fas fa-tachometer-alt me-2"></i>Retour au tableau de bord
    </a>

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
                        
                        <!-- Formulaire de suppression -->
                        <form action="<?= url('profile/photo/delete') ?>" method="post">
                            <input type="hidden" name="csrf_token" value="<?= $this->e($csrfToken) ?>">
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash me-2"></i>Supprimer la photo
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="text-center p-4 bg-light rounded">
                            <i class="fas fa-user fa-4x text-muted mb-3"></i>
                            <p class="text-muted">Aucune photo de profil</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-8">
                    <!-- Formulaire d'upload -->
                    <form action="<?= url('profile/photo') ?>" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $this->e($csrfToken) ?>">
                        <div class="mb-3">
                            <label for="photo" class="form-label">Choisir une nouvelle photo</label>
                            <input type="file" 
                                   class="form-control" 
                                   id="photo" 
                                   name="photo" 
                                   accept="image/jpeg,image/png,image/gif" 
                                   required>
                            <div class="form-text">
                                Formats acceptés : JPG, PNG, GIF. Taille maximale : 5 MB
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload me-2"></i>Télécharger
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulaire de mise à jour du profil -->
    <form action="<?= url('profile/update') ?>" method="post" class="needs-validation" novalidate>
        <!-- CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?= $this->e($csrfToken) ?>">

        <div class="form-group">
            <label for="nom">Nom</label>
            <input type="text" class="form-control" id="nom" name="nom" value="<?= $this->e($user->getNom()) ?>" readonly>
        </div>
        <div class="form-group">
            <label for="prenom">Prénom</label>
            <input type="text" class="form-control" id="prenom" name="prenom" value="<?= $this->e($user->getPrenom()) ?>" readonly>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= $this->e($user->getEmail()) ?>" readonly>
        </div>
        <div class="form-group mb-3">
            <label for="adresse" class="form-label">Adresse</label>
            <input type="text" 
                   class="form-control <?= isset($errors['adresse']) ? 'is-invalid' : '' ?>" 
                   id="adresse" 
                   name="adresse" 
                   value="<?= $this->e($user->getAdresse() ?? '') ?>" 
                   placeholder="Votre adresse"
                   required>
            <?php if (isset($errors['adresse'])): ?>
                <div class="invalid-feedback">
                    <?= $errors['adresse'] ?>
                </div>
            <?php else: ?>
                <div class="form-text">
                    Cette adresse sera utilisée pour les communications officielles.
                </div>
            <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary">Mettre à jour</button>
    </form>
</div>

<?php if (isset($flash)): ?>
    <?php include __DIR__ . '/../../partials/flash.php'; ?>
<?php endif; ?> 