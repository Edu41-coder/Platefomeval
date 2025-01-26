<?php
/**
 * @var \Core\View\Template $this
 */

$this->layout('default', [
    'title' => 'FAQ - Foire aux questions',
    'assets' => [
        'css' => ['assets/css/legal.css']
    ]
]);
?>

<div class="container mt-4 mb-5">
    <h1 class="mb-4">Foire aux questions (FAQ)</h1>

    <div class="accordion" id="faqAccordion">
        <!-- Section Générale -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingGeneral">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGeneral" aria-expanded="true" aria-controls="collapseGeneral">
                    Questions générales
                </button>
            </h2>
            <div id="collapseGeneral" class="accordion-collapse collapse show" aria-labelledby="headingGeneral" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    <div class="faq-item">
                        <h3>Qu'est-ce que Plateformeval ?</h3>
                        <p>Plateformeval est une plateforme d'évaluation en ligne qui permet aux professeurs de créer et gérer des évaluations, et aux étudiants de les consulter de manière simple et efficace.</p>
                    </div>

                    <div class="faq-item">
                        <h3>Comment créer un compte ?</h3>
                        <p>Pour créer un compte, cliquez sur "Inscription" dans le menu principal. Remplissez le formulaire avec vos informations personnelles et suivez les instructions.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Étudiants -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingStudents">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseStudents" aria-expanded="false" aria-controls="collapseStudents">
                    Pour les étudiants
                </button>
            </h2>
            <div id="collapseStudents" class="accordion-collapse collapse" aria-labelledby="headingStudents" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    <div class="faq-item">
                        <h3>Comment accéder à mes évaluations ?</h3>
                        <p>Une fois connecté, accédez à votre tableau de bord étudiant. Vous y trouverez la liste de toutes vos évaluations à venir et passées.</p>
                    </div>

                    <div class="faq-item">
                        <h3>Comment voir mes résultats ?</h3>
                        <p>Vos résultats sont disponibles dans la section "Mes évaluations" de votre tableau de bord, une fois que les professeurs les ont publiés.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Professeurs -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingTeachers">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTeachers" aria-expanded="false" aria-controls="collapseTeachers">
                    Pour les professeurs
                </button>
            </h2>
            <div id="collapseTeachers" class="accordion-collapse collapse" aria-labelledby="headingTeachers" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    <div class="faq-item">
                        <h3>Comment créer une évaluation ?</h3>
                        <p>Dans votre tableau de bord professeur, cliquez sur "Créer une évaluation". Vous pourrez alors définir le titre, la description, la date et ajouter des questions.</p>
                    </div>

                    <div class="faq-item">
                        <h3>Comment gérer mes matières ?</h3>
                        <p>La section "Mes matières" de votre tableau de bord vous permet de créer et gérer vos matières, ainsi que d'y associer des évaluations.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Technique -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingTechnical">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTechnical" aria-expanded="false" aria-controls="collapseTechnical">
                    Questions techniques
                </button>
            </h2>
            <div id="collapseTechnical" class="accordion-collapse collapse" aria-labelledby="headingTechnical" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    <div class="faq-item">
                        <h3>J'ai oublié mon mot de passe, que faire ?</h3>
                        <p>Cliquez sur "Mot de passe oublié" sur la page de connexion. Vous recevrez un email avec les instructions pour réinitialiser votre mot de passe.</p>
                    </div>

                    <div class="faq-item">
                        <h3>Comment mettre à jour mes informations personnelles ?</h3>
                        <p>Accédez à votre profil en cliquant sur votre nom en haut à droite. Vous pourrez y modifier vos informations personnelles et votre mot de passe.</p>
                    </div>

                    <div class="faq-item">
                        <h3>Que faire en cas de problème technique ?</h3>
                        <p>En cas de problème technique, vous pouvez :</p>
                        <ul>
                            <li>Consulter cette FAQ pour les problèmes courants</li>
                            <li>Contacter le support technique via le <a href="<?= $this->url('contact') ?>">formulaire de contact</a></li>
                            <li>Envoyer un email à <a href="mailto:assistance@plateformeval.fr">assistance@plateformeval.fr</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 