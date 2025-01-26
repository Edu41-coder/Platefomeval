<?php
namespace App\Interfaces\Repository;

use App\Models\Entity\Matiere;
use App\Models\Entity\User;
use Core\Exception\RepositoryException;

/**
 * Interface pour la gestion des matières en base de données
 * @template-extends RepositoryInterface<Matiere>
 */
interface MatiereRepositoryInterface extends RepositoryInterface
{
    /**
     * Récupère toutes les matières
     * 
     * @return Matiere[] Liste des matières
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function findAll(): array;

    /**
     * Récupère une matière par son ID
     * 
     * @param int $id ID de la matière
     * @return Matiere|null La matière trouvée ou null
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function findById(int $id): ?Matiere;

    /**
     * Trouve des matières selon des critères
     * 
     * @param array $criteria Les critères de recherche
     * @return Matiere[] Liste des matières trouvées
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function findBy(array $criteria): array;

    /**
     * Trouve une matière selon des critères
     * 
     * @param array $criteria Les critères de recherche
     * @return Matiere|null La matière trouvée ou null
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function findOneBy(array $criteria): ?Matiere;

    /**
     * Crée une nouvelle matière
     * 
     * @param array $data Données de la matière
     * @return int ID de la matière créée
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function create(array $data): int;

    /**
     * Met à jour une matière
     * 
     * @param int $id ID de la matière
     * @param array $data Données à mettre à jour
     * @return bool True si la mise à jour a réussi
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function update(int $id, array $data): bool;

    /**
     * Supprime une matière
     * 
     * @param int $id ID de la matière
     * @return bool True si la suppression a réussi
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function delete(int $id): bool;

    /**
     * Vérifie si une matière existe par son ID
     * 
     * @param int $id ID de la matière
     * @return bool True si la matière existe
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function exists(int $id): bool;

    /**
     * Compte le nombre de matières selon des critères
     * 
     * @param array $criteria Les critères de comptage
     * @return int Le nombre de matières
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function count(array $criteria = []): int;

    /**
     * Récupère une matière par son nom
     * 
     * @param string $nom Nom de la matière
     * @return Matiere|null La matière trouvée ou null
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function findByName(string $nom): ?Matiere;

    /**
     * Récupère toutes les matières d'un professeur
     * 
     * @param int $profId ID du professeur
     * @return Matiere[] Liste des matières
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function findByProfessorId(int $profId): array;

    /**
     * Récupère tous les étudiants inscrits à une matière
     * 
     * @param int $matiereId ID de la matière
     * @return User[] Liste des étudiants
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function getStudentsByMatiereId(int $matiereId): array;

    /**
     * Vérifie si un professeur enseigne une matière spécifique
     * 
     * @param int $profId ID du professeur
     * @param int $matiereId ID de la matière
     * @return bool True si le professeur enseigne la matière
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function isProfessorTeachingMatiere(int $profId, int $matiereId): bool;

    /**
     * Récupère une matière complète après création
     * 
     * @param array $data Données de la matière
     * @return Matiere La matière créée
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function createAndReturn(array $data): Matiere;

    /**
     * Met à jour et retourne une matière complète
     * 
     * @param int $id ID de la matière
     * @param array $data Données à mettre à jour
     * @return Matiere La matière mise à jour
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function updateAndReturn(int $id, array $data): Matiere;
}