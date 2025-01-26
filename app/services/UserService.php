<?php

namespace App\Services;

use App\Interfaces\Repository\UserRepositoryInterface;
use App\Interfaces\Service\UserServiceInterface;
use App\Models\Entity\User;
use Core\Exception\ServiceException;

/**
 * @extends AbstractService<User>
 */
class UserService extends AbstractService implements UserServiceInterface
{
    private UserRepositoryInterface $userRepository;

    protected array $rules = [
        'email' => ['required', 'email'],
        'password' => ['required', ['min' => 6]],
        'nom' => ['required', ['min' => 2]],
        'prenom' => ['required', ['min' => 2]],
        'role_id' => ['required', 'numeric'],
        'is_admin' => ['boolean']
    ];

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function get(int $id): ?User
    {
        try {
            return $this->userRepository->findById($id);
        } catch (\Exception $e) {
            throw new ServiceException(
                "Erreur lors de la récupération de l'utilisateur",
                500,
                'UserService',
                'get',
                [],
                $e
            );
        }
    }

    public function getAll(): array
    {
        try {
            return $this->userRepository->findAll();
        } catch (\Exception $e) {
            throw new ServiceException(
                "Erreur lors de la récupération des utilisateurs",
                500,
                'UserService',
                'getAll',
                [],
                $e
            );
        }
    }

    public function create(array $data): User
    {
        if (!$this->validate($data)) {
            throw new ServiceException(
                "Erreur de validation",
                422,
                'UserService',
                'create',
                $this->getErrors()
            );
        }

        try {
            $userId = $this->userRepository->create($data);
            $user = $this->get($userId);
            if (!$user) {
                throw new ServiceException(
                    "Erreur lors de la création de l'utilisateur",
                    500,
                    'UserService',
                    'create'
                );
            }
            return $user;
        } catch (\Exception $e) {
            throw new ServiceException(
                "Erreur lors de la création de l'utilisateur",
                500,
                'UserService',
                'create',
                [],
                $e
            );
        }
    }

    public function update(int $id, array $data): User
    {
        $user = $this->get($id);
        if (!$user) {
            throw new ServiceException(
                "Utilisateur non trouvé",
                404,
                'UserService',
                'update'
            );
        }

        if (!$this->validate($data, ['update' => true])) {
            throw new ServiceException(
                "Erreur de validation",
                422,
                'UserService',
                'update',
                $this->getErrors()
            );
        }

        try {
            if (!$this->userRepository->update($id, $data)) {
                throw new ServiceException(
                    "Échec de la mise à jour de l'utilisateur",
                    500,
                    'UserService',
                    'update'
                );
            }
            return $this->get($id);
        } catch (\Exception $e) {
            throw new ServiceException(
                "Erreur lors de la mise à jour de l'utilisateur",
                500,
                'UserService',
                'update',
                [],
                $e
            );
        }
    }

    public function delete(int $id): bool
    {
        try {
            return $this->userRepository->delete($id);
        } catch (\Exception $e) {
            throw new ServiceException(
                "Erreur lors de la suppression de l'utilisateur",
                500,
                'UserService',
                'delete',
                [],
                $e
            );
        }
    }

    public function findByEmail(string $email): ?User
    {
        try {
            return $this->userRepository->findByEmail($email);
        } catch (\Exception $e) {
            throw new ServiceException(
                "Erreur lors de la recherche de l'utilisateur par email",
                500,
                'UserService',
                'findByEmail',
                [],
                $e
            );
        }
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        return $this->userRepository->emailExists($email, $excludeId);
    }

    public function validate(array $data, array $options = []): bool
    {
        // Si c'est une mise à jour, on allège les règles
        if (!empty($options['update'])) {
            $currentRules = $this->rules;
            foreach ($this->rules as $field => $fieldRules) {
                if (!isset($data[$field])) {
                    unset($this->rules[$field]);
                }
            }
        }

        if (!parent::validate($data)) {
            // Restaurer les règles originales si on les a modifiées
            if (isset($currentRules)) {
                $this->rules = $currentRules;
            }
            return false;
        }

        // Validation spécifique pour l'email unique
        if (isset($data['email'])) {
            $excludeId = $options['update'] ? ($data['id'] ?? null) : null;
            if ($this->emailExists($data['email'], $excludeId)) {
                $this->addError('email', "Cet email est déjà utilisé");
                // Restaurer les règles originales si on les a modifiées
                if (isset($currentRules)) {
                    $this->rules = $currentRules;
                }
                return false;
            }
        }

        // Restaurer les règles originales si on les a modifiées
        if (isset($currentRules)) {
            $this->rules = $currentRules;
        }

        return empty($this->errors);
    }

    /**
     * Vérifie si un utilisateur existe
     * 
     * @param int $id
     * @return bool
     * @throws ServiceException
     */
    public function exists(int $id): bool
    {
        try {
            return $this->userRepository->exists($id);
        } catch (\Exception $e) {
            throw new ServiceException(
                "Erreur lors de la vérification de l'existence de l'utilisateur",
                500,
                'UserService',
                'exists',
                [],
                $e
            );
        }
    }

    /**
     * Sauvegarde un token de réinitialisation
     * 
     * @param int $userId
     * @param string $token
     * @param \DateTime $expiresAt
     * @return bool
     * @throws ServiceException
     */
    public function saveResetToken(int $userId, string $token, \DateTime $expiresAt): bool
    {
        try {
            return $this->userRepository->saveResetToken($userId, $token, $expiresAt);
        } catch (\Exception $e) {
            throw new ServiceException(
                "Erreur lors de la sauvegarde du token",
                500,
                'UserService',
                'saveResetToken',
                [],
                $e
            );
        }
    }

    /**
     * Trouve un token de réinitialisation
     * 
     * @param string $token
     * @return array|null
     * @throws ServiceException
     */
    public function findResetToken(string $token): ?array
    {
        try {
            return $this->userRepository->findResetToken($token);
        } catch (\Exception $e) {
            throw new ServiceException(
                "Erreur lors de la recherche du token",
                500,
                'UserService',
                'findResetToken',
                [],
                $e
            );
        }
    }

    /**
     * Supprime un token de réinitialisation
     * 
     * @param string $token
     * @return bool
     * @throws ServiceException
     */
    public function deleteResetToken(string $token): bool
    {
        try {
            return $this->userRepository->deleteResetToken($token);
        } catch (\Exception $e) {
            throw new ServiceException(
                "Erreur lors de la suppression du token",
                500,
                'UserService',
                'deleteResetToken',
                [],
                $e
            );
        }
    }
}
