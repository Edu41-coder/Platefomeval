<?php

namespace App\Interfaces\Service;

use App\Models\Entity\User;
use Core\Exception\AuthenticationException;
use Core\Exception\ServiceException;

/**
 * Interface pour le service d'authentification
 * @template-extends ServiceInterface<User>
 */
interface AuthInterface extends ServiceInterface
{
    /**
     * Authentifie un utilisateur avec email et mot de passe
     *
     * @param string $email Email de l'utilisateur
     * @param string $password Mot de passe
     * @return User Utilisateur authentifié
     * @throws AuthenticationException Si l'authentification échoue
     */
    public function authenticate(string $email, string $password): User;

    /**
     * Déconnecte l'utilisateur courant
     */
    public function logout(): void;

    /**
     * Vérifie si un utilisateur est actuellement authentifié
     */
    public function check(): bool;

    /**
     * Obtient l'utilisateur actuellement authentifié
     *
     * @return User|null Utilisateur authentifié ou null si non authentifié
     */
    public function user(): ?User;

    /**
     * Vérifie si l'utilisateur authentifié a un rôle spécifique
     */
    public function hasRole(string $role): bool;

    /**
     * Vérifie si l'utilisateur authentifié a une permission spécifique
     */
    public function hasPermission(string $permission): bool;

    /**
     * Crée un nouveau compte utilisateur
     * Note: Cette méthode est spécifique à l'auth, différente du create() de ServiceInterface
     *
     * @param array $data Données de l'utilisateur
     * @return User Utilisateur créé
     * @throws AuthenticationException Si la création échoue
     */
    public function register(array $data): User;

    /**
     * Vérifie si un email est déjà utilisé
     */
    public function emailExists(string $email): bool;

    /**
     * Génère un token de réinitialisation de mot de passe
     *
     * @throws AuthenticationException Si l'email n'existe pas
     */
    public function generatePasswordResetToken(string $email): string;

    /**
     * Réinitialise le mot de passe d'un utilisateur
     *
     * @throws AuthenticationException Si le token est invalide
     */
    public function resetPassword(string $token, string $newPassword): bool;

    /**
     * Met à jour le mot de passe de l'utilisateur authentifié
     *
     * @throws AuthenticationException Si le mot de passe actuel est incorrect
     */
    public function updatePassword(string $currentPassword, string $newPassword): bool;

    /**
     * Vérifie si un token de réinitialisation est valide
     */
    public function isValidResetToken(string $token): bool;

    /**
     * Obtient l'ID de la session courante
     */
    public function getSessionId(): ?string;

    /**
     * Régénère l'ID de session
     */
    public function regenerateSession(): bool;

    /**
     * Surcharge de get pour retourner User
     * 
     * @param int $id
     * @return User|null
     * @throws ServiceException
     */
    public function get(int $id): ?User;

    /**
     * Surcharge de getAll pour retourner User[]
     * 
     * @return User[]
     * @throws ServiceException
     */
    public function getAll(): array;

    /**
     * Surcharge de create pour retourner User
     * 
     * @param array $data
     * @return User
     * @throws ServiceException
     */
    public function create(array $data): User;

    /**
     * Surcharge de update pour retourner User
     * 
     * @param int $id
     * @param array $data
     * @return User
     * @throws ServiceException
     */
    public function update(int $id, array $data): User;
}