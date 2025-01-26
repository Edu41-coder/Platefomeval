<?php
/** 
 * @var \Core\View\Template $this 
 * @var string $site_title
 */

(function($template, $site_title) {
?>
<footer class="bg-light mt-auto py-4 border-top" role="contentinfo">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-8">
                <div class="row align-items-center">
                    <!-- Copyright -->
                    <div class="col-12 col-md-6 mb-3 mb-md-0">
                        <p class="text-muted mb-0 text-center text-md-start">
                            &copy; <?= date('Y') ?> <?= $template->e($site_title ?? 'Notre Site') ?>. 
                            <span class="d-none d-sm-inline">Tous droits réservés.</span>
                        </p>
                    </div>
                    
                    <!-- Navigation -->
                    <div class="col-12 col-md-6">
                        <nav class="nav justify-content-center justify-content-md-end" 
                             aria-label="Liens légaux et contact">
                            <a href="<?= $template->url('mentions-legales') ?>" 
                               class="nav-link text-muted px-2"
                               title="Voir nos mentions légales"
                               aria-label="Accéder aux mentions légales">
                                <i class="fas fa-gavel me-1" aria-hidden="true"></i>
                                <span>Mentions légales</span>
                            </a>
                            <a href="<?= $template->url('confidentialite') ?>" 
                               class="nav-link text-muted px-2"
                               title="Consulter notre politique de confidentialité"
                               aria-label="Accéder à la politique de confidentialité">
                                <i class="fas fa-shield-alt me-1" aria-hidden="true"></i>
                                <span>Confidentialité</span>
                            </a>
                            <a href="<?= $template->url('contact') ?>" 
                               class="nav-link text-muted px-2"
                               title="Nous contacter"
                               aria-label="Accéder au formulaire de contact">
                                <i class="fas fa-envelope me-1" aria-hidden="true"></i>
                                <span>Contact</span>
                            </a>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
<?php
})($this, $site_title ?? null);
?>