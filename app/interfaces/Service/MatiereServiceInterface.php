<?php
namespace App\Interfaces\Service;

use App\Models\Entity\Matiere;
use Core\Exception\ServiceException;

/**
 * Interface pour le service de gestion des matières
 * @extends ServiceInterface<Matiere>
 */
interface MatiereServiceInterface extends ServiceInterface
{
    /**
     * Trouve une matière par son nom
     *
     * @param string $nom
     * @return Matiere|null
     * @throws ServiceException
     */
    public function findByName(string $nom): ?Matiere;

    /**
     * Vérifie si un nom de matière existe déjà
     *
     * @param string $nom
     * @param int|null $excludeId ID de la matière à exclure de la vérification
     * @return bool
     * @throws ServiceException
     */
    public function nameExists(string $nom, ?int $excludeId = null): bool;

    /**
     * Valide les données d'une matière
     *
     * @param array $data
     * @param array $options
     * @return bool
     */
    public function validate(array $data, array $options = []): bool;

    /**
     * Récupère les étudiants d'une matière avec pagination et tri
     */
    public function getEtudiantsByMatiereWithPagination(
        int $matiereId,
        int $page,
        int $itemsPerPage,
        string $sort = 'nom',
        string $order = 'ASC'
    ): array;

    /**
     * Récupère les matières d'un professeur
     */
    public function getMatieresByProfessor(int $professorId): array;

    /**
     * Vérifie si un professeur enseigne une matière
     */
    public function belongsToProfessor(int $matiereId, int $professorId): bool;

    /**
     * Récupère les matières d'un étudiant
     */
    public function getMatieresForStudent(int $studentId): array;

    /**
     * Vérifie si un étudiant est inscrit à une matière
     */
    public function isStudentEnrolled(int $matiereId, int $studentId): bool;
}