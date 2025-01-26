<?php

namespace App\Interfaces\Service;

use App\Models\Entity\Photo;
use Core\Exception\ServiceException;

interface PhotoServiceInterface
{
    /**
     * Récupère la photo d'un utilisateur
     */
    public function getUserPhoto(int $userId): ?Photo;

    /**
     * Upload une nouvelle photo pour un utilisateur
     * @throws ServiceException
     */
    public function uploadPhoto(int $userId, array $file): Photo;

    /**
     * Met à jour la photo d'un utilisateur
     * @throws ServiceException
     */
    public function updatePhoto(int $userId, array $file): Photo;

    /**
     * Supprime la photo d'un utilisateur
     */
    public function deletePhoto(int $userId): bool;

    /**
     * Vérifie si le fichier est une image valide
     */
    public function validateImage(array $file): bool;
} 