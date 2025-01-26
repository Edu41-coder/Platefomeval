<?php

namespace App\Interfaces\Repository;

use App\Models\Entity\User;
use Core\Exception\RepositoryException;

/**
 * Interface pour la gestion des utilisateurs en base de données
 * @template-extends RepositoryInterface<User>
 */
interface UserRepositoryInterface extends RepositoryInterface
{
    /**
     * Récupère tous les utilisateurs
     * 
     * @return User[] Liste des utilisateurs
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function findAll(): array;

    /**
     * Récupère un utilisateur par son ID
     * 
     * @param int $id ID de l'utilisateur
     * @return User|null L'utilisateur trouvé ou null
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function findById(int $id): ?User;

    /**
     * Récupère un utilisateur par son email
     * 
     * @param string $email Email de l'utilisateur
     * @return User|null L'utilisateur trouvé ou null
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function findByEmail(string $email): ?User;

    /**
     * Crée un nouvel utilisateur
     * 
     * @param array{
     *     nom: string,
     *     prenom: string,
     *     email: string,
     *     password: string,
     *     adresse?: string|null,
     *     role_id: int,
     *     is_admin?: bool
     * } $data Données de l'utilisateur
     * @return int ID de l'utilisateur créé
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function create(array $data): int;

    /**
     * Met à jour un utilisateur
     * 
     * @param int $id ID de l'utilisateur
     * @param array{
     *     nom?: string,
     *     prenom?: string,
     *     email?: string,
     *     password?: string,
     *     adresse?: string|null,
     *     role_id?: int,
     *     is_admin?: bool
     * } $data Données à mettre à jour
     * @return bool True si la mise à jour a réussi
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function update(int $id, array $data): bool;

    /**
     * Vérifie les identifiants de connexion
     * 
     * @param string $email Email de l'utilisateur
     * @param string $password Mot de passe en clair
     * @return User|null L'utilisateur si les identifiants sont valides, null sinon
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function verifyCredentials(string $email, string $password): ?User;

    /**
     * Vérifie si un email existe déjà
     * 
     * @param string $email Email à vérifier
     * @param int|null $excludeId ID de l'utilisateur à exclure de la vérification
     * @return bool True si l'email existe déjà
     * @throws RepositoryException En cas d'erreur de base de données
     */
    public function emailExists(string $email, ?int $excludeId = null): bool;
     /**
     * Sauvegarde un token de réinitialisation de mot de passe
     *
     * @param int $userId
     * @param string $token
     * @param \DateTime $expiresAt
     * @return bool
     */
    public function saveResetToken(int $userId, string $token, \DateTime $expiresAt): bool;

    /**
     * Trouve un token de réinitialisation
     *
     * @param string $token
     * @return array|null ['user_id' => int, 'token' => string, 'expires_at' => string]
     */
    public function findResetToken(string $token): ?array;

    /**
     * Supprime un token de réinitialisation
     *
     * @param string $token
     * @return bool
     */
    public function deleteResetToken(string $token): bool;

    /**
     * Trouve des utilisateurs selon des critères
     * 
     * @param array $criteria
     * @return User[]
     */
    public function findBy(array $criteria): array;

    /**
     * Trouve un utilisateur selon des critères
     * 
     * @param array $criteria
     * @return User|null
     */
    public function findOneBy(array $criteria): ?User;

    /**
     * Trouve des utilisateurs par rôle
     * 
     * @param string $role
     * @return User[]
     * @throws RepositoryException
     */
    public function findByRole(string $role): array;

    /**
     * Vérifie si une adresse existe déjà
     * 
     * @param string $address
     * @param int|null $excludeId ID de l'utilisateur à exclure de la vérification
     * @return bool
     * @throws RepositoryException
     */
    public function addressExists(string $address, ?int $excludeId = null): bool;
}