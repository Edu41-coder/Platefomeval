<?php
/**
 * @var \Core\View\Template $this
 * @var array $errors
 * @var array $old
 * @var string $csrfToken
 */

// Configuration du layout et des assets
$this->layout('default', [
    'title' => 'Inscription'
]);

$this->with('assets', [
    'css' => ['css/auth.css'],
    'js' => ['js/register.js', 'js/password-strength.js']
]);
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm mt-5 animate__animated animate__fadeIn">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4">Inscription</h2>

                    <?php if (isset($errors) && !empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show animate__animated animate__shakeX" role="alert">
                            <ul class="list-unstyled mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><i class="fas fa-exclamation-circle me-2"></i><?= $this->e($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form id="registerForm" action="<?= url('register') ?>" method="POST" class="needs-validation" novalidate>
                        <!-- Prénom -->
                        <div class="mb-3">
                            <label for="prenom" class="form-label">Prénom</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-user"></i>
                                </span>
                                <input type="text"
                                    class="form-control <?= isset($errors['prenom']) ? 'is-invalid' : '' ?>"
                                    id="prenom"
                                    name="prenom"
                                    value="<?= $this->e($old['prenom'] ?? '') ?>"
                                    required
                                    minlength="2"
                                    maxlength="50"
                                    autocomplete="given-name">
                                <div class="invalid-feedback">
                                    <?= $errors['prenom'] ?? 'Veuillez entrer un prénom valide (2-50 caractères)' ?>
                                </div>
                            </div>
                        </div>

                        <!-- Nom -->
                        <div class="mb-3">
                            <label for="nom" class="form-label">Nom</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-user"></i>
                                </span>
                                <input type="text"
                                    class="form-control <?= isset($errors['nom']) ? 'is-invalid' : '' ?>"
                                    id="nom"
                                    name="nom"
                                    value="<?= $this->e($old['nom'] ?? '') ?>"
                                    required
                                    minlength="2"
                                    maxlength="50"
                                    autocomplete="family-name">
                                <div class="invalid-feedback">
                                    <?= $errors['nom'] ?? 'Veuillez entrer un nom valide (2-50 caractères)' ?>
                                </div>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email universitaire</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email"
                                    class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                    id="email"
                                    name="email"
                                    value="<?= $this->e($old['email'] ?? '') ?>"
                                    required
                                    pattern=".+@.+univ-fr\.net"
                                    placeholder="prenom.nom@univ-fr.net"
                                    autocomplete="email">
                                <div class="invalid-feedback">
                                    <?= $errors['email'] ?? 'Veuillez utiliser votre adresse email universitaire (@univ-fr.net)' ?>
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                Utilisez votre adresse email universitaire se terminant par @univ-fr.net
                            </small>
                        </div>

                        <!-- Mot de passe -->
                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password"
                                    class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                                    id="password"
                                    name="password"
                                    required
                                    minlength="8"
                                    autocomplete="new-password">
                            </div>
                            <div class="invalid-feedback">
                                <?= $errors['password'] ?? 'Le mot de passe doit contenir au moins 8 caractères' ?>
                            </div>
                        </div>

                        <!-- Indicateur de force du mot de passe -->
                        <div class="password-strength mt-2">
                            <div class="progress" style="height: 5px;">
                                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                            </div>
                            <small class="strength-text text-muted mt-1"></small>
                            <div class="password-requirements mt-2">
                                <small class="text-muted">Le mot de passe doit contenir :</small>
                                <ul class="list-unstyled small text-muted mt-1">
                                    <li id="length-check"><i class="fas fa-times-circle me-2"></i>Au moins 8 caractères</li>
                                    <li id="letter-check"><i class="fas fa-times-circle me-2"></i>Au moins une lettre</li>
                                    <li id="number-check"><i class="fas fa-times-circle me-2"></i>Au moins un chiffre</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Confirmation du mot de passe -->
                        <div class="mb-4">
                            <label for="password_confirm" class="form-label">Confirmer le mot de passe</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password"
                                    class="form-control"
                                    id="password_confirm"
                                    name="password_confirm"
                                    required
                                    minlength="8"
                                    autocomplete="new-password">
                            </div>
                            <div class="invalid-feedback">
                                <?= $errors['password_confirm'] ?? 'Les mots de passe ne correspondent pas' ?>
                            </div>
                        </div>

                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?= $this->e($csrfToken) ?>">

                        <!-- Bouton de soumission -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg position-relative">
                                <span class="btn-text">S'inscrire</span>
                                <span class="spinner-border spinner-border-sm btn-loader position-absolute d-none" role="status"></span>
                            </button>
                        </div>

                        <!-- Liens -->
                        <div class="text-center mt-4">
                            <a href="<?= $this->url('login') ?>" class="text-decoration-none">
                                <i class="fas fa-arrow-left me-2"></i>Déjà inscrit ? Se connecter
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>