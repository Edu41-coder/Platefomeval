<?php
namespace App\Services;

use App\Models\Entity\Evaluation;
use App\Models\Repository\EvaluationRepository;
use App\Models\Repository\MatiereRepository;
use App\Interfaces\Service\EvaluationServiceInterface;
use Core\Exception\ServiceException;
use Core\Database\DatabaseInterface;
use Core\Exception\ValidatorException;
use Core\Exception\RepositoryException;

class EvaluationService implements EvaluationServiceInterface
{
    private EvaluationRepository $evaluationRepository;
    private MatiereRepository $matiereRepository;
    private DatabaseInterface $db;

    public function __construct(
        EvaluationRepository $evaluationRepository, 
        MatiereRepository $matiereRepository,
        DatabaseInterface $db
    ) {
        $this->evaluationRepository = $evaluationRepository;
        $this->matiereRepository = $matiereRepository;
        $this->db = $db;
    }

    /**
     * @inheritDoc
     */
    public function getAllEvaluations(): array
    {
        try {
            return $this->evaluationRepository->findAll();
        } catch (\Exception $e) {
            throw new ServiceException('Erreur lors de la récupération des évaluations: ' . $e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function getEvaluationsByStudentId(int $studentId): array
    {
        try {
            return $this->evaluationRepository->findByStudentId($studentId);
        } catch (\Exception $e) {
            throw new ServiceException('Erreur lors de la récupération des évaluations: ' . $e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function getAllMatieres(): array
    {
        try {
            return $this->matiereRepository->findAll();
        } catch (\Exception $e) {
            throw new ServiceException('Erreur lors de la récupération des matières: ' . $e->getMessage());
        }
    }

    /**
     * Retourne un tableau d'objets Evaluation pour un étudiant
     * 
     * @param int $studentId ID de l'étudiant
     * @param int|null $matiereId ID de la matière (optionnel)
     * @return Evaluation[] Tableau d'objets Evaluation
     * @throws ServiceException
     */
    public function getEvaluationsForStudent(int $studentId, ?int $matiereId = null): array
    {
        try {
            return $this->evaluationRepository->getEvaluationsForStudent($studentId, $matiereId);
        } catch (\Exception $e) {
            error_log('Error in EvaluationService::getEvaluationsForStudent: ' . $e->getMessage());
            throw new ServiceException('Erreur lors de la récupération des évaluations');
        }
    }

    /**
     * @inheritDoc
     */
    public function belongsToProfessor(int $evaluationId, int $professorId): bool
    {
        try {
            $evaluation = $this->evaluationRepository->findById($evaluationId);
            return $evaluation !== null && $evaluation->getProfessorId() === $professorId;
        } catch (\Exception $e) {
            error_log('Error in EvaluationService::belongsToProfessor: ' . $e->getMessage());
            throw new ServiceException('Erreur lors de la vérification de l\'appartenance');
        }
    }

    /**
     * @inheritDoc
     */
    public function getNotesForEvaluation(int $evaluationId): array
    {
        try {
            return $this->evaluationRepository->getNotesForEvaluation($evaluationId);
        } catch (\Exception $e) {
            error_log('Error in EvaluationService::getNotesForEvaluation: ' . $e->getMessage());
            throw new ServiceException('Erreur lors de la récupération des notes');
        }
    }

    /**
     * @inheritDoc
     */
    public function delete(int $id): bool
    {
        try {
            return $this->evaluationRepository->delete($id);
        } catch (\Exception $e) {
            throw new ServiceException('Erreur lors de la suppression de l\'évaluation: ' . $e->getMessage());
        }
    }

    /**
     * Calcule la moyenne d'un étudiant pour une matière
     * 
     * @param int $studentId ID de l'étudiant
     * @param int|null $matiereId ID de la matière (optionnel)
     * @return float|null La moyenne ou null si pas de notes
     */
    public function calculateStudentAverage(int $studentId, ?int $matiereId = null): ?float
    {
        try {
            if ($matiereId === null) {
                $notes = $this->evaluationRepository->getStudentNotes($studentId, null);
            } else {
                $notes = $this->evaluationRepository->getStudentNotes($studentId, $matiereId);
            }
            
            if (empty($notes)) {
                return null;
            }

            $sum = 0;
            $count = 0;
            foreach ($notes as $note) {
                if (is_numeric($note['note'])) {
                    $sum += floatval($note['note']);
                    $count++;
                }
            }

            if ($count === 0) {
                return null;
            }

            return round($sum / $count, 2);
        } catch (\Exception $e) {
            error_log("Erreur lors du calcul de la moyenne : " . $e->getMessage());
            return null;
        }
    }

    /**
     * @inheritDoc
     */
    public function getById(int $id): ?Evaluation
    {
        try {
            return $this->evaluationRepository->findById($id);
        } catch (\Exception $e) {
            error_log("EvaluationService::findById - Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Met à jour une évaluation
     * 
     * @param int $id ID de l'évaluation
     * @param array $data Données de mise à jour
     * @return Evaluation
     * @throws ServiceException
     */
    public function update(int $id, array $data): Evaluation
    {
        if (!$this->validateUpdateData($data)) {
            throw new ServiceException('Les données de l\'évaluation sont invalides');
        }

        try {
            $success = $this->evaluationRepository->update($id, $data);
            if (!$success) {
                throw new ServiceException('Erreur lors de la mise à jour de l\'évaluation');
            }

            return $this->getById($id);
        } catch (\Exception $e) {
            throw new ServiceException('Erreur lors de la mise à jour : ' . $e->getMessage());
        }
    }

    /**
     * Valide les données de mise à jour d'une évaluation
     * 
     * @param array $data
     * @return bool
     */
    protected function validateUpdateData(array $data): bool
    {
        // Validation des champs requis
        if (!isset($data['type']) || empty($data['type'])) {
            return false;
        }
        if (!isset($data['date_evaluation']) || empty($data['date_evaluation'])) {
            return false;
        }

        // Validation du type d'évaluation
        $validTypes = ['Contrôle continu', 'Examen', 'TP', 'Projet', 'Oral'];
        if (!in_array($data['type'], $validTypes)) {
            return false;
        }

        // Validation de la date
        if (!strtotime($data['date_evaluation'])) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function create(array $data): Evaluation
    {
        try {
            // Valider les données
            $data = $this->validateEvaluationData($data);
            
            // Préparer les données de l'évaluation
            $evaluationData = [
                'matiere_id' => $data['matiere_id'],
                'prof_id' => $data['prof_id'],
                'type' => $data['type'],
                'description' => $data['description'] ?? '',
                'date_evaluation' => $data['date']
            ];
            
            // Préparer les notes si présentes
            $notes = [];
            if (isset($data['notes']) && is_array($data['notes'])) {
                foreach ($data['notes'] as $studentId => $note) {
                    // Modifié pour permettre un commentaire sans note
                    if (($note !== '' && $note !== null) || !empty($data['commentaires'][$studentId])) {
                        $notes[$studentId] = [
                            'note' => ($note !== '' && $note !== null) ? floatval($note) : null,
                            'commentaire' => $data['commentaires'][$studentId] ?? null
                        ];
                    }
                }
            }
            
            // Ajouter les notes aux données d'évaluation
            if (!empty($notes)) {
                $evaluationData['notes'] = $notes;
            }
            
            // Créer l'évaluation avec les notes
            $evaluationId = $this->evaluationRepository->create($evaluationData);
            
            return $this->getById($evaluationId);
            
        } catch (\Exception $e) {
            error_log("EvaluationService::create - Error: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
            throw new ServiceException("Une erreur est survenue lors de la création de l'évaluation");
        }
    }

    /**
     * @inheritDoc
     */
    public function getByMatiere(int $matiereId): array
    {
        try {
            return $this->evaluationRepository->getByMatiere($matiereId);
        } catch (\Exception $e) {
            error_log('Error in EvaluationService::getByMatiere: ' . $e->getMessage());
            throw new ServiceException('Erreur lors de la récupération des évaluations de la matière');
        }
    }

    public function validate(array $data): bool
    {
        // Validation des champs requis
        $requiredFields = ['matiere_id', 'prof_id', 'type', 'date'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }

        // Valider que les notes sont dans un format valide
        if (isset($data['notes']) && is_array($data['notes'])) {
            foreach ($data['notes'] as $note) {
                if ($note === '' || $note === null) {
                    continue;
                }
                
                if (!is_numeric($note) || $note < 0 || $note > 20) {
                    return false;
                }
            }
        }

        // Validation du type d'évaluation
        $validTypes = ['Contrôle continu', 'Examen', 'TP', 'Projet', 'Oral'];
        if (!in_array($data['type'], $validTypes)) {
            return false;
        }

        // Validation de la date
        if (!strtotime($data['date'])) {
            return false;
        }

        return true;
    }

    public function validateEvaluationData(array $data): array
    {
        $errors = [];
        $validTypes = [
            'Examen',
            'Contrôle continu',
            'TP',
            'Projet',
            'Oral'
        ];

        // Validation du type (obligatoire)
        if (empty($data['type'])) {
            error_log("Type manquant");
            $errors['type'] = "Le type d'évaluation est requis";
        } else {
            $submittedType = trim(html_entity_decode($data['type'], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            
            $typeFound = false;
            foreach ($validTypes as $validType) {
                if (mb_strtolower($submittedType) === mb_strtolower($validType)) {
                    $data['type'] = $validType;
                    $typeFound = true;
                    break;
                }
            }
            
            if (!$typeFound) {
                $errors['type'] = "Le type d'évaluation doit être l'un des suivants : " . implode(', ', $validTypes);
            }
        }

        // Validation de la date (obligatoire)
        if (empty($data['date'])) {
            $errors['date'] = "La date est requise";
        } else {
            $date = \DateTime::createFromFormat('Y-m-d', $data['date']);
            if (!$date || $date->format('Y-m-d') !== $data['date']) {
                $errors['date'] = "Le format de la date est invalide";
            }
        }

        // Validation des notes (optionnelles)
        if (isset($data['notes']) && is_array($data['notes'])) {
            foreach ($data['notes'] as $studentId => $note) {
                // Permettre les notes vides
                if ($note !== '' && $note !== null) {
                    // Valider uniquement si une note est fournie
                    if (!is_numeric($note) || $note < 0 || $note > 20) {
                        $errors['notes'][$studentId] = "La note doit être comprise entre 0 et 20";
                    }
                }
            }
        }

        // Validation des commentaires (optionnels)
        if (isset($data['commentaires']) && is_array($data['commentaires'])) {
            foreach ($data['commentaires'] as $studentId => $commentaire) {
                if (!empty($commentaire)) {  // Valider uniquement si un commentaire est fourni
                    if (strlen($commentaire) > 255) {
                        $errors['commentaires'][$studentId] = "Le commentaire ne doit pas dépasser 255 caractères";
                    }
                }
            }
        }

        if (!empty($errors)) {
            throw new ValidatorException($errors, "Erreurs de validation");
        }

        return $data;
    }

    /**
     * Rcupère les évaluations d'une matière avec pagination
     */
    public function getEvaluationsByMatiereWithPagination(
        int $matiereId, 
        int $page = 1, 
        int $itemsPerPage = 10,
        string $sort = 'date_evaluation',
        string $order = 'DESC'
    ): array {
        try {
            return $this->evaluationRepository->findEvaluationsByMatiereWithPagination(
                $matiereId,
                $page,
                $itemsPerPage,
                $sort,
                $order
            );
        } catch (RepositoryException $e) {
            error_log("EvaluationService::getEvaluationsByMatiereWithPagination - " . $e->getMessage());
            throw new ServiceException("Une erreur est survenue lors de la récupération des évaluations");
        }
    }

    /**
     * Récupère les notes d'une évaluation avec les informations des étudiants
     */
    public function getNotesByEvaluationId(int $evaluationId): array
    {
        try {
            return $this->evaluationRepository->findNotesByEvaluationId($evaluationId);
        } catch (\Exception $e) {
            error_log('Error in EvaluationService::getNotesByEvaluationId: ' . $e->getMessage());
            throw new ServiceException('Erreur lors de la récupération des notes');
        }
    }

    /**
     * Supprime une note
     */
    public function deleteNote(int $evaluationId, int $etudiantId): bool
    {
        try {
            return $this->evaluationRepository->deleteNote($evaluationId, $etudiantId);
        } catch (\Exception $e) {
            error_log('Error in EvaluationService::deleteNote: ' . $e->getMessage());
            throw new ServiceException('Erreur lors de la suppression de la note');
        }
    }

    /**
     * Récupère les données nécessaires pour le formulaire de gestion des notes
     */
    public function getNotesFormData(int $evaluationId, int $matiereId): array
    {
        try {
            // Récupérer l'évaluation
            $evaluation = $this->getById($evaluationId);
            if (!$evaluation) {
                throw new ServiceException('Évaluation non trouvée');
            }

            // Vérifier que l'évaluation appartient à la matière
            if ($evaluation->getMatiereId() !== $matiereId) {
                throw new ServiceException('Cette évaluation n\'appartient pas à cette matière');
            }

            // Récupérer les notes existantes
            $notes = $this->getNotesByEvaluationId($evaluationId);

            // Récupérer la matière
            $matiere = $this->matiereRepository->findById($matiereId);
            if (!$matiere) {
                throw new ServiceException('Matière non trouvée');
            }

            return [
                'evaluation' => $evaluation,
                'matiere' => $matiere,
                'notes' => $notes
            ];
        } catch (\Exception $e) {
            error_log('Error in EvaluationService::getNotesFormData: ' . $e->getMessage());
            throw new ServiceException('Erreur lors de la récupération des données du formulaire');
        }
    }

    public function updateNotes(int $evaluationId, array $notes, array $commentaires): bool
    {
        try {
            // Formater les données pour le repository
            $formattedNotes = [];
            foreach ($notes as $etudiantId => $note) {
                if (empty($note) && $note !== '0') continue;
                
                $formattedNotes[$etudiantId] = [
                    'note' => $note,
                    'commentaire' => $commentaires[$etudiantId] ?? null
                ];
            }
            
            return $this->evaluationRepository->updateNotes($evaluationId, $formattedNotes);
            
        } catch (\Exception $e) {
            error_log("Erreur dans EvaluationService::updateNotes: " . $e->getMessage());
            throw new ServiceException('Erreur lors de la mise à jour des notes');
        }
    }

    public function exists(int $id): bool
    {
        try {
            return $this->evaluationRepository->exists($id);
        } catch (\Exception $e) {
            throw new ServiceException('Erreur lors de la vérification de l\'existence: ' . $e->getMessage());
        }
    }

    public function getErrors(): array
    {
        // Retourner les erreurs de validation
        return [];
    }

    /**
     * Récupère la dernière évaluation d'un étudiant pour une matière
     */
    public function getLastEvaluation(int $studentId, int $matiereId): ?array
    {
        try {
            return $this->evaluationRepository->getLastEvaluation($studentId, $matiereId);
        } catch (\Exception $e) {
            error_log("Erreur lors de la récupération de la dernière évaluation : " . $e->getMessage());
            return null;
        }
    }

    public function getEvaluationsWithNotes(int $studentId, int $matiereId, string $sort = 'date', string $order = 'desc'): array
    {
        return $this->evaluationRepository->getEvaluationsWithNotes($studentId, $matiereId, $sort, $order);
    }

    /**
     * Récupère toutes les évaluations avec les détails des matières et étudiants
     * 
     * @return array
     * @throws ServiceException
     */
    public function getAllEvaluationsWithDetails(): array
    {
        try {
            return $this->evaluationRepository->findAllWithDetails();
        } catch (\Exception $e) {
            error_log('Error in EvaluationService::getAllEvaluationsWithDetails: ' . $e->getMessage());
            throw new ServiceException('Erreur lors de la récupération des évaluations');
        }
    }

    /**
     * Récupère une évaluation avec les détails de la matière et de l'étudiant
     * 
     * @param int $id
     * @return array|null
     * @throws ServiceException
     */
    public function getEvaluationWithDetails(int $id): ?array
    {
        try {
            return $this->evaluationRepository->findOneWithDetails($id);
        } catch (\Exception $e) {
            error_log('Error in EvaluationService::getEvaluationWithDetails: ' . $e->getMessage());
            throw new ServiceException('Erreur lors de la récupération de l\'évaluation');
        }
    }
}