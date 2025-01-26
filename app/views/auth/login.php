<?php
/** @var \Core\View\Template $this */
/** @var string $csrfToken */
/** @var array $assets */

// Store CSRF token in session
$_SESSION['csrf_token'] = $csrfToken;
?>

<div id="loginError" class="alert alert-danger d-none"></div>

<form id="loginForm" action="<?= $this->url('login') ?>" method="POST" class="needs-validation" novalidate>
    <!-- CSRF token -->
    <input type="hidden" name="csrf_token" value="<?= $this->e($csrfToken) ?>">
    
    <!-- Email -->
    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <div class="input-group">
            <span class="input-group-text">
                <i class="fas fa-envelope"></i>
            </span>
            <input type="email"
                class="form-control"
                id="email"
                name="email"
                required
                autocomplete="email">
            <div class="invalid-feedback"></div>
        </div>
    </div>

    <!-- Mot de passe -->
    <div class="mb-3">
        <label for="password" class="form-label">Mot de passe</label>
        <div class="input-group">
            <span class="input-group-text">
                <i class="fas fa-lock"></i>
            </span>
            <input type="password"
                class="form-control"
                id="password"
                name="password"
                required
                autocomplete="current-password">
            <div class="invalid-feedback"></div>
        </div>
    </div>

    <!-- Bouton de soumission -->
    <div class="d-grid gap-2">
        <button type="button" class="btn btn-primary btn-lg position-relative" id="loginButton">
            <span class="btn-text">Se connecter</span>
            <span class="spinner-border spinner-border-sm btn-loader position-absolute d-none" role="status" aria-hidden="true"></span>
        </button>
    </div>
</form>

<!-- Liens -->
<div class="mt-4 text-center">
    <div class="mb-2">
        <a href="<?= $this->url('mot-de-passe-oublie') ?>" class="text-decoration-none">
            <i class="fas fa-key me-1"></i>Mot de passe oublié ?
        </a>
    </div>
    <div>
        <a href="<?= $this->url('register') ?>" class="text-decoration-none">
            <i class="fas fa-user-plus me-1"></i>Créer un compte
        </a>
    </div>
</div>

