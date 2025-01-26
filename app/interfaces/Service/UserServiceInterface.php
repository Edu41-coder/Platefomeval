<?php

namespace App\Interfaces\Service;

use App\Models\Entity\User;
use Core\Exception\ServiceException;

/**
 * Interface pour le service de gestion des utilisateurs
 * @extends ServiceInterface<User>
 */
interface UserServiceInterface extends ServiceInterface
{
    /**
     * Trouve un utilisateur par son email
     *
     * @param string $email
     * @return User|null
     * @throws ServiceException
     */
    public function findByEmail(string $email): ?User;

    /**
     * Vérifie si un email existe déjà
     *
     * @param string $email
     * @param int|null $excludeId ID de l'utilisateur à exclure de la vérification
     * @return bool
     * @throws ServiceException
     */
    public function emailExists(string $email, ?int $excludeId = null): bool;

    /**
     * Valide les données d'un utilisateur
     *
     * @param array $data
     * @param array $options
     * @return bool
     */
    public function validate(array $data, array $options = []): bool;

    /**
     * Sauvegarde un token de réinitialisation
     * 
     * @param int $userId
     * @param string $token
     * @param \DateTime $expiresAt
     * @return bool
     * @throws ServiceException
     */
    public function saveResetToken(int $userId, string $token, \DateTime $expiresAt): bool;

    /**
     * Trouve un token de réinitialisation
     * 
     * @param string $token
     * @return array|null
     * @throws ServiceException
     */
    public function findResetToken(string $token): ?array;

    /**
     * Supprime un token de réinitialisation
     * 
     * @param string $token
     * @return bool
     * @throws ServiceException
     */
    public function deleteResetToken(string $token): bool;
}

