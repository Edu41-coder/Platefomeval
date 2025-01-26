<?php
/** @var \Core\View\Template $this */
/** @var string $csrfToken */
/** @var array $assets */

// Store CSRF token in session
$_SESSION['csrf_token'] = $csrfToken;
?>

<div class="row justify-content-center align-items-center forgot-password-container">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card shadow forgot-password-card">
            <div class="card-body">
                <h4 class="forgot-password-title">Mot de passe oublié</h4>
                <p class="forgot-password-text">Entrez votre adresse email pour recevoir les instructions de réinitialisation.</p>

                <div id="forgotPasswordError" class="alert alert-danger d-none forgot-password-alert"></div>
                <div id="forgotPasswordSuccess" class="alert alert-success d-none forgot-password-alert"></div>

                <form id="forgotPasswordForm" method="POST" class="forgot-password-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= $this->e($csrfToken) ?>">
                    
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <label for="email" class="form-label">Email:</label>
                        </div>
                        <div class="col">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email"
                                    class="form-control"
                                    id="email"
                                    name="email"
                                    required
                                    autocomplete="email"
                                    placeholder="Votre adresse email">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">
                                <span class="btn-text">Envoyer</span>
                                <span class="spinner-border spinner-border-sm btn-loader d-none" 
                                      role="status" 
                                      aria-hidden="true"></span>
                            </button>
                        </div>
                    </div>
                </form>

                <div class="mt-3">
                    <a href="<?= $this->url('login') ?>" class="forgot-password-back">
                        <i class="fas fa-arrow-left me-1"></i>Retour à la connexion
                    </a>
                </div>
            </div>
        </div>
    </div>
</div> 