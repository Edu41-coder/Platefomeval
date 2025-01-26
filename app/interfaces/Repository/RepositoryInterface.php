<?php

namespace App\Interfaces\Repository;

use Core\Exception\RepositoryException;

/**
 * Interface générique pour les repositories
 * 
 * @template T
 */
interface RepositoryInterface
{
    /**
     * Récupère tous les enregistrements
     * 
     * @return object[] Liste des enregistrements
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function findAll(): array;

    /**
     * Récupère un enregistrement par son ID
     * 
     * @param int $id ID de l'enregistrement
     * @return T|null L'enregistrement trouvé ou null
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function findById(int $id): ?object;

    /**
     * Trouve des enregistrements selon des critères
     * 
     * @param array $criteria Critères de recherche
     * @return object[] Liste des enregistrements trouvés
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function findBy(array $criteria): array;

    /**
     * Trouve un seul enregistrement selon des critères
     * 
     * @param array $criteria Critères de recherche
     * @return T|null L'enregistrement trouvé ou null
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function findOneBy(array $criteria): ?object;

    /**
     * Crée un nouvel enregistrement
     * 
     * @param array $data Données de l'enregistrement
     * @return int ID de l'enregistrement créé
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function create(array $data): int;

    /**
     * Met à jour un enregistrement
     * 
     * @param int $id ID de l'enregistrement
     * @param array $data Données à mettre à jour
     * @return bool True si la mise à jour a réussi
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function update(int $id, array $data): bool;

    /**
     * Supprime un enregistrement
     * 
     * @param int $id ID de l'enregistrement
     * @return bool True si la suppression a réussi
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function delete(int $id): bool;

    /**
     * Vérifie si un enregistrement existe
     * 
     * @param int $id ID de l'enregistrement
     * @return bool True si l'enregistrement existe
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function exists(int $id): bool;

    /**
     * Compte le nombre d'enregistrements selon des critères
     * 
     * @param array $criteria Critères de recherche
     * @return int Nombre d'enregistrements
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function count(array $criteria = []): int;
}