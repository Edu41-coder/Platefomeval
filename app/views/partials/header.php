<?php

/** 
 * @var \Core\View\Template $this 
 * @var \App\Models\Entity\User|array|null $user
 * @var string $site_title
 * @var string $csrfToken
 */

(function ($template, $user, $site_title, $csrfToken) {
?>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <!-- Logo / Home Link - visible seulement si l'utilisateur n'est pas connecté -->
                <?php if (!isset($user)): ?>
                    <a class="navbar-brand" href="<?= $template->url('home') ?>" title="Retour à l'accueil">
                        <?= $template->e($site_title ?? 'Accueil') ?>
                    </a>
                <?php endif; ?>

                <!-- Toggler for mobile view -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" 
                        aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Navbar links -->
                <div class="collapse navbar-collapse" id="navbarMain">
                    <div class="navbar-nav ms-auto">
                        <?php if (isset($user)): ?>
                            <!-- User greeting -->
                            <span class="nav-item navbar-text me-3">
                                Bienvenue <?= $template->e(is_array($user) ? 
                                    ($user['prenom'] ?? $user['email'] ?? '') : 
                                    ($user->getPrenom() ?? $user->getEmail() ?? '')) ?>
                            </span>

                            <!-- Admin link -->
                            <?php 
                                $isAdmin = is_array($user) ? 
                                    ($user['is_admin'] ?? false) : 
                                    $user->isAdmin();
                            ?>
                            <?php if ($isAdmin): ?>
                                <a href="<?= $template->url('admin') ?>"
                                    class="nav-link <?= $template->isActive('admin') ? 'active' : '' ?>"
                                    title="Accéder à l'administration">
                                    <i class="fas fa-cog" aria-hidden="true"></i>
                                    <span>Administration</span>
                                </a>
                            <?php endif; ?>

                            <!-- Dashboard link -->
                            <a href="<?= $template->url('dashboard') ?>"
                                class="nav-link <?= $template->isActive('dashboard') ? 'active' : '' ?>"
                                title="Tableau de bord">
                                <i class="fas fa-tachometer-alt" aria-hidden="true"></i>
                                <span>Tableau de bord</span>
                            </a>

                            <!-- Profile link -->
                            <a href="<?= $template->url('profile') ?>"
                                class="nav-link <?= $template->isActive('profile') ? 'active' : '' ?>"
                                title="Gérer mon profil">
                                <i class="fas fa-user" aria-hidden="true"></i>
                                <span>Mon profil</span>
                            </a>

                            <!-- Logout form -->
                            <form action="<?= $template->url('logout') ?>" method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= $template->e($csrfToken ?? '') ?>">
                                <button type="submit" class="btn btn-link nav-link" title="Se déconnecter">
                                    <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                                    <span>Déconnexion</span>
                                </button>
                            </form>
                        <?php else: ?>
                            <!-- Login link -->
                            <a href="<?= $template->url('login') ?>"
                                class="nav-link <?= $template->isActive('login') ? 'active' : '' ?>"
                                title="Se connecter">
                                <i class="fas fa-sign-in-alt" aria-hidden="true"></i>
                                <span>Connexion</span>
                            </a>
                            <!-- Register link -->
                            <a href="<?= $template->url('register') ?>"
                                class="nav-link <?= $template->isActive('register') ? 'active' : '' ?>"
                                title="Créer un compte">
                                <i class="fas fa-user-plus" aria-hidden="true"></i>
                                <span>Inscription</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
    </header>
<?php
})($this, $user ?? null, $site_title ?? null, $csrfToken ?? null);
?>