<?php

namespace App\Services;

use App\Interfaces\Service\PhotoServiceInterface;
use App\Models\Entity\Photo;
use App\Models\Repository\PhotoRepository;
use Core\Exception\ServiceException;

class PhotoService implements PhotoServiceInterface
{
    private PhotoRepository $photoRepository;
    private string $uploadDir = 'uploads/profile_photos';
    private array $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    private int $maxFileSize = 5242880; // 5MB

    public function __construct(PhotoRepository $photoRepository)
    {
        $this->photoRepository = $photoRepository;
        
        // Construire le chemin complet pour la création du dossier
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/Plateformeval/public/' . $this->uploadDir;
        
        // Créer le dossier d'upload s'il n'existe pas
        if (!file_exists($fullPath)) {
            if (!mkdir($fullPath, 0777, true)) {
                error_log('Erreur: Impossible de créer le dossier ' . $fullPath);
                throw new ServiceException("Impossible de créer le dossier d'upload");
            }
        }
    }

    public function getUserPhoto(int $userId): ?Photo
    {
        return $this->photoRepository->findByUserId($userId);
    }

    public function uploadPhoto(int $userId, array $file): Photo
    {
        try {
            if (!$this->validateImage($file)) {
                throw new ServiceException('Le fichier n\'est pas une image valide');
            }

            // Construire le chemin complet pour l'upload
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/Plateformeval/public/' . $this->uploadDir;
            
            // Vérifier que le dossier existe et est accessible en écriture
            if (!is_dir($fullPath)) {
                if (!mkdir($fullPath, 0777, true)) {
                    error_log('Erreur: Impossible de créer le dossier ' . $fullPath);
                    throw new ServiceException("Impossible de créer le dossier d'upload");
                }
            }

            if (!is_writable($fullPath)) {
                error_log('Erreur: Dossier non accessible en écriture ' . $fullPath);
                throw new ServiceException("Le dossier d'upload n'est pas accessible en écriture");
            }

            // Supprimer l'ancienne photo si elle existe
            $existingPhoto = $this->getUserPhoto($userId);
            if ($existingPhoto) {
                $this->deletePhoto($userId);
            }

            // Générer un nom de fichier unique
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid('profile_') . '.' . $extension;

            $destination = $fullPath . '/' . $filename;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                error_log('Erreur: Échec du déplacement du fichier vers ' . $destination);
                throw new ServiceException('Erreur lors du téléchargement de l\'image');
            }

            // Créer l'entrée dans la base de données
            $photoId = $this->photoRepository->create([
                'user_id' => $userId,
                'filename' => $filename
            ]);

            return new Photo([
                'id' => $photoId,
                'user_id' => $userId,
                'filename' => $filename
            ]);
        } catch (\Exception $e) {
            error_log('Erreur lors de l\'upload de la photo: ' . $e->getMessage());
            throw new ServiceException('Erreur lors de l\'upload de la photo: ' . $e->getMessage());
        }
    }

    public function updatePhoto(int $userId, array $file): Photo
    {
        return $this->uploadPhoto($userId, $file);
    }

    public function deletePhoto(int $userId): bool
    {
        $photo = $this->getUserPhoto($userId);
        if (!$photo) {
            return false;
        }

        // Supprimer le fichier
        $filepath = $this->uploadDir . '/' . $photo->getFilename();
        if (file_exists($filepath)) {
            unlink($filepath);
        }

        // Supprimer l'entrée de la base de données
        return $this->photoRepository->delete($photo->getId());
    }

    public function validateImage(array $file): bool
    {
        // Vérifier si le fichier existe
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return false;
        }

        // Vérifier le type MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $this->allowedTypes)) {
            return false;
        }

        // Vérifier la taille
        if ($file['size'] > $this->maxFileSize) {
            return false;
        }

        // Vérifier si c'est vraiment une image
        $imageInfo = getimagesize($file['tmp_name']);
        return $imageInfo !== false;
    }
} 