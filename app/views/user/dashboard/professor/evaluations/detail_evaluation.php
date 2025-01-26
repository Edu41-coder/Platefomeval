<?php
/**
 * @var Template $this
 * @var string $pageTitle
 * @var array $user
 * @var Matiere $matiere
 * @var Evaluation $evaluation
 * @var array $evaluationNotes Liste des notes actuelles
 * @var array $etudiants Liste des étudiants
 */

use App\Models\Entity\Matiere;
use App\Models\Entity\Evaluation;
use Core\View\Template;
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="<?= $this->url('dashboard') ?>" class="text-decoration-none">
                            <i class="fas fa-tachometer-alt"></i> Tableau de bord
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="<?= $this->url('professor.matieres.show', ['id' => $matiere->getId()]) ?>" class="text-decoration-none">
                            <i class="fas fa-book"></i> 
                            <?= $this->e($matiere->getNom()) ?>
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="<?= $this->url('professor.matieres.evaluations.index', ['matiere_id' => $matiere->getId()]) ?>" class="text-decoration-none">
                            <i class="fas fa-clipboard-list"></i> Évaluations
                        </a>
                    </li>
                    <li class="breadcrumb-item active">
                        <i class="fas fa-eye"></i> Détails de l'évaluation
                    </li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h1 class="h3 mb-0">Détails de l'évaluation - <?= $this->e($matiere->getNom()) ?></h1>
        </div>
        <div class="card-body">
            <!-- Informations de l'évaluation en lecture seule -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <p><strong>Type :</strong> <?= $this->e($evaluation->getType()) ?></p>
                </div>
                <div class="col-md-4">
                    <p><strong>Date :</strong> <?= date('d/m/Y', strtotime($evaluation->getDate())) ?></p>
                </div>
                <div class="col-md-4">
                    <p><strong>Description :</strong> <?= $this->e($evaluation->getDescription()) ?></p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Note</th>
                            <th>Commentaire</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($etudiants as $etudiant): ?>
                            <?php 
                            $note = $evaluationNotes[$etudiant->getId()] ?? null;
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="<?= $this->url('profile.view', [
                                            'id' => $etudiant->getId(),
                                            'matiere_id' => $matiere->getId(),
                                            'evaluation_id' => $evaluation->getId(),
                                            'from_details' => true
                                        ]) ?>" 
                                           class="btn btn-outline-info btn-sm me-2" 
                                           title="Voir le profil">
                                            <i class="fas fa-user"></i>
                                        </a>
                                        <?= $this->e($etudiant->getNom()) ?>
                                    </div>
                                </td>
                                <td><?= $this->e($etudiant->getPrenom()) ?></td>
                                <td>
                                    <?php if ($note && $note->getNote() !== null): ?>
                                        <?= number_format($note->getNote(), 2) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($note && $note->getCommentaire()): ?>
                                        <?= $this->e($note->getCommentaire()) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <a href="<?= $this->url('professor.matieres.evaluations.index', ['matiere_id' => $matiere->getId()]) ?>" 
                   class="btn btn-secondary">Retour à la liste</a>
            </div>
        </div>
    </div>
</div> 