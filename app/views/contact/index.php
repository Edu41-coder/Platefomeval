<?php
/**
 * @var \Core\View\Template $this
 */

$this->layout('default', [
    'title' => 'Contact',
    'assets' => [
        'css' => ['assets/css/contact.css']
    ]
]);
?>

<div class="container mt-4 mb-5">
    <h1 class="mb-4">Contact</h1>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <div class="alert alert-info">
                        <h4 class="alert-heading mb-3">
                            <i class="fas fa-info-circle me-2"></i>
                            Besoin d'assistance ?
                        </h4>
                        <p>En cas de problème technique ou pour toute question concernant l'utilisation de la plateforme, 
                        notre équipe d'assistance est là pour vous aider.</p>
                        <hr>
                        <p class="mb-0">
                            <strong>Email :</strong> 
                            <a href="mailto:assistance@plateformeval.fr" class="text-decoration-none">
                                assistance@plateformeval.fr
                            </a>
                        </p>
                    </div>

                    <form action="<?= $this->url('contact.send') ?>" method="POST" class="mt-4">
                        <?= $this->csrf() ?>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Nom complet *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <div class="mb-3">
                            <label for="subject" class="form-label">Sujet *</label>
                            <select class="form-select" id="subject" name="subject" required>
                                <option value="">Choisissez un sujet</option>
                                <option value="technique">Problème technique</option>
                                <option value="compte">Question sur mon compte</option>
                                <option value="evaluation">Question sur les évaluations</option>
                                <option value="autre">Autre</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="message" class="form-label">Message *</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>
                                Envoyer le message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-clock me-2"></i>
                        Délai de réponse
                    </h5>
                    <p class="card-text">Notre équipe s'engage à vous répondre dans les plus brefs délais, 
                    généralement sous 24-48 heures ouvrées.</p>

                    <h5 class="card-title mb-3 mt-4">
                        <i class="fas fa-shield-alt me-2"></i>
                        Confidentialité
                    </h5>
                    <p class="card-text">Vos informations personnelles sont protégées et ne seront utilisées 
                    que dans le cadre du traitement de votre demande.</p>

                    <h5 class="card-title mb-3 mt-4">
                        <i class="fas fa-question-circle me-2"></i>
                        FAQ
                    </h5>
                    <p class="card-text">Avant de nous contacter, consultez notre 
                        <a href="<?= $this->url('faq') ?>" class="text-decoration-none">FAQ</a> 
                        qui répond aux questions les plus fréquentes.</p>
                </div>
            </div>
        </div>
    </div>
</div> 