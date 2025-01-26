<?php
namespace App\Interfaces\Service;

use App\Models\Entity\Evaluation;
use Core\Exception\ServiceException;

/**
 * Interface pour le service de gestion des évaluations
 */
interface EvaluationServiceInterface
{
    /**
     * Récupère toutes les évaluations.
     *
     * @return Evaluation[]
     * @throws ServiceException
     */
    public function getAllEvaluations(): array;

    /**
     * Récupère les évaluations par ID d'étudiant.
     *
     * @param int $studentId
     * @return Evaluation[]
     * @throws ServiceException
     */
    public function getEvaluationsByStudentId(int $studentId): array;

    /**
     * Récupère toutes les matières.
     *
     * @return array
     * @throws ServiceException
     */
    public function getAllMatieres(): array;

    /**
     * Récupère les évaluations d'un étudiant pour une matière donnée
     *
     * @param int $studentId
     * @param int|null $matiereId
     * @return Evaluation[]
     * @throws ServiceException
     */
    public function getEvaluationsForStudent(int $studentId, ?int $matiereId = null): array;

    /**
     * Vérifie si une évaluation appartient à un professeur
     *
     * @param int $evaluationId
     * @param int $professorId
     * @return bool
     */
    public function belongsToProfessor(int $evaluationId, int $professorId): bool;

    /**
     * Récupère les notes d'une évaluation
     *
     * @param int $evaluationId
     * @return array
     * @throws ServiceException
     */
    public function getNotesForEvaluation(int $evaluationId): array;

    /**
     * Supprime une évaluation
     *
     * @param int $id
     * @return bool
     * @throws ServiceException
     */
    public function delete(int $id): bool;

    /**
     * Calcule la moyenne d'un étudiant
     *
     * @param int $studentId
     * @param int|null $matiereId
     * @return float|null
     * @throws ServiceException
     */
    public function calculateStudentAverage(int $studentId, ?int $matiereId = null): ?float;

    /**
     * Récupère une évaluation par son ID
     *
     * @param int $id
     * @return Evaluation|null
     * @throws ServiceException
     */
    public function getById(int $id): ?Evaluation;

    /**
     * Met à jour une évaluation
     *
     * @param int $id
     * @param array $data
     * @return Evaluation
     * @throws ServiceException
     */
    public function update(int $id, array $data): Evaluation;

    /**
     * Crée une nouvelle évaluation
     *
     * @param array $data
     * @return Evaluation
     * @throws ServiceException
     */
    public function create(array $data): Evaluation;

    /**
     * Récupère les évaluations d'une matière
     *
     * @param int $matiereId
     * @return Evaluation[]
     * @throws ServiceException
     */
    public function getByMatiere(int $matiereId): array;

    /**
     * Récupère les évaluations avec leurs notes pour un étudiant et une matière
     * 
     * @param int $studentId ID de l'étudiant
     * @param int $matiereId ID de la matière
     * @return Evaluation[] Tableau d'évaluations avec leurs notes
     * @throws ServiceException
     */
    public function getEvaluationsWithNotes(int $studentId, int $matiereId): array;
}