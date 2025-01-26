<?php
namespace App\Interfaces\Repository;

use App\Models\Entity\Evaluation;
use Core\Exception\RepositoryException;

/**
 * Interface pour la gestion des évaluations en base de données
 * @template-extends RepositoryInterface<Evaluation>
 */
interface EvaluationRepositoryInterface extends RepositoryInterface
{
    /**
     * Récupère toutes les évaluations
     * 
     * @return Evaluation[]
     * @throws RepositoryException
     */
    public function findAll(): array;

    /**
     * Récupère une évaluation par son ID
     * 
     * @param int $id ID de l'évaluation
     * @return Evaluation|null L'évaluation trouvée ou null
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function findById(int $id): ?Evaluation;

    /**
     * Récupère les évaluations par ID d'étudiant
     * 
     * @param int $studentId ID de l'étudiant
     * @return Evaluation[]
     * @throws RepositoryException
     */
    public function findByStudentId(int $studentId): array;

    /**
     * Crée une nouvelle évaluation
     * 
     * @param array{
     *     etudiant_id: int,
     *     matiere_id: int,
     *     note: float,
     *     commentaire?: string|null,
     *     prof_id: int,
     *     date_evaluation: string
     * } $data Données de l'évaluation
     * @return int ID de l'évaluation créée
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function create(array $data): int;

    /**
     * Met à jour une évaluation
     * 
     * @param int $id ID de l'évaluation
     * @param array{
     *     type: string,
     *     description?: string|null,
     *     date_evaluation: string
     * } $data Données à mettre à jour
     * @return bool True si la mise à jour a réussi
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function update(int $id, array $data): bool;

    /**
     * Supprime une évaluation
     * 
     * @param int $id ID de l'évaluation
     * @return bool True si la suppression a réussi
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function delete(int $id): bool;

    /**
     * Trouve des évaluations selon des critères
     * 
     * @param array $criteria
     * @return Evaluation[]
     */
    public function findBy(array $criteria): array;

    /**
     * Trouve une évaluation selon des critères
     * 
     * @param array $criteria
     * @return Evaluation|null
     */
    public function findOneBy(array $criteria): ?Evaluation;

    /**
     * Récupère les notes d'une évaluation avec les informations des étudiants
     * 
     * @param int $evaluationId
     * @return array
     * @throws RepositoryException
     */
    public function getNotesForEvaluation(int $evaluationId): array;

    /**
     * Calcule la moyenne d'un étudiant
     * 
     * @param int $studentId
     * @param int|null $matiereId
     * @return float|null
     * @throws RepositoryException
     */
    public function calculateStudentAverage(int $studentId, ?int $matiereId = null): ?float;

    /**
     * Récupère les évaluations d'un étudiant pour une matière donnée
     * 
     * @param int $studentId
     * @param int|null $matiereId
     * @return Evaluation[]
     * @throws RepositoryException
     */
    public function getEvaluationsForStudent(int $studentId, ?int $matiereId = null): array;

    /**
     * Récupère les évaluations d'une matière
     * 
     * @param int $matiereId
     * @return Evaluation[]
     * @throws RepositoryException
     */
    public function getByMatiere(int $matiereId): array;

    /**
     * Récupère les évaluations avec leurs notes pour un étudiant et une matière
     * 
     * @param int $studentId ID de l'étudiant
     * @param int $matiereId ID de la matière
     * @return Evaluation[] Tableau d'évaluations avec leurs notes
     * @throws RepositoryException
     */
    public function getEvaluationsWithNotes(int $studentId, int $matiereId): array;
}