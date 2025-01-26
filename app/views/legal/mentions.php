<?php
/**
 * @var \Core\View\Template $this
 */

$this->layout('default', [
    'title' => 'Mentions légales',
    'assets' => [
        'css' => ['assets/css/legal.css']
    ]
]);
?>

<div class="container mt-4 mb-5">
    <h1 class="mb-4">Mentions légales</h1>

    <div class="card">
        <div class="card-body">
            <section class="mb-4">
                <h2 class="h4 mb-3">1. Éditeur du site</h2>
                <p>Le site Plateformeval est édité par :</p>
                <ul>
                    <li>Nom de l'établissement : [Nom de l'établissement]</li>
                    <li>Adresse : [Adresse complète]</li>
                    <li>Téléphone : [Numéro de téléphone]</li>
                    <li>Email : assistance@plateformeval.fr</li>
                </ul>
            </section>

            <section class="mb-4">
                <h2 class="h4 mb-3">2. Hébergement</h2>
                <p>Le site est hébergé par :</p>
                <ul>
                    <li>Nom de l'hébergeur : [Nom de l'hébergeur]</li>
                    <li>Adresse : [Adresse de l'hébergeur]</li>
                </ul>
            </section>

            <section class="mb-4">
                <h2 class="h4 mb-3">3. Propriété intellectuelle</h2>
                <p>L'ensemble du contenu de ce site (textes, images, vidéos, etc.) est protégé par le droit d'auteur. 
                Toute reproduction ou représentation totale ou partielle de ce site par quelque procédé que ce soit, 
                sans autorisation expresse, est interdite et constituerait une contrefaçon sanctionnée par les articles 
                L.335-2 et suivants du Code de la propriété intellectuelle.</p>
            </section>

            <section class="mb-4">
                <h2 class="h4 mb-3">4. Protection des données personnelles</h2>
                <p>Conformément à la loi "Informatique et Libertés" du 6 janvier 1978 modifiée et au Règlement Général 
                sur la Protection des Données (RGPD), vous disposez d'un droit d'accès, de rectification, de suppression 
                et d'opposition aux données personnelles vous concernant. Pour exercer ces droits, vous pouvez nous 
                contacter à l'adresse : assistance@plateformeval.fr</p>
            </section>

            <section class="mb-4">
                <h2 class="h4 mb-3">5. Cookies</h2>
                <p>Le site utilise des cookies nécessaires à son bon fonctionnement. En naviguant sur ce site, 
                vous acceptez l'utilisation de cookies pour vous proposer une navigation optimale et des services 
                adaptés à vos centres d'intérêt.</p>
            </section>

            <section>
                <h2 class="h4 mb-3">6. Modification des mentions légales</h2>
                <p>L'éditeur se réserve le droit de modifier les présentes mentions légales à tout moment. 
                L'utilisateur est invité à les consulter régulièrement.</p>
            </section>
        </div>
    </div>
</div> 