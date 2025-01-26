<?php

namespace App\Services;

use App\Interfaces\Service\AuthInterface;
use App\Interfaces\Repository\UserRepositoryInterface;
use App\Models\Entity\User;
use App\Models\Entity\Role;
use App\Models\Repository\UserRepository;
use Core\Exception\AuthenticationException;
use Core\Exception\ServiceException;
use Core\Validator\Validator;
use Core\Exception\ValidatorException;

/**
 * @extends AbstractService<User>
 */
class AuthService extends AbstractService implements AuthInterface
{
    private static ?self $instance = null;
    private ?User $currentUser = null;
    private UserRepositoryInterface $userRepository;
    private Validator $validator;
    private const SESSION_LIFETIME = 3600; // 60 minute
    private const LOGIN_ROUTE = '/Plateformeval/public/login';
    private static array $userCache = [];
    private static array $sessionCache = [];
    private const SESSION_CONFIG = [
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ];

    protected array $rules = [
        'email' => ['required', 'email'],
        'password' => [
            'required', 
            ['min' => 6],
            'confirmed'    // Ajoute la vérification de la confirmation
        ],
        'password_confirmation' => ['required'],
        'nom' => ['required', ['min' => 2]],
        'prenom' => ['required', ['min' => 2]]
    ];

    private function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
        $this->validator = new Validator();
        $this->initSession();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            $userRepository = new UserRepository();
            self::$instance = new self($userRepository);
        }
        return self::$instance;
    }

    // Make clone private to prevent cloning of the instance
    private function __clone() {}

    // Make wakeup public as required by PHP for magic methods
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }

    private function initSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params(self::SESSION_CONFIG);
            session_start();
        }

        if (isset($_SESSION['user']['id']) && $this->validateSession()) {
            $this->currentUser = $this->userRepository->findById($_SESSION['user']['id']);
            
            if (!$this->currentUser) {
                $this->logout();
            }
        }
    }

    public static function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params(self::SESSION_CONFIG);
            session_start();
        }
    }

    public function get(int $id): ?User
    {
        if (isset(self::$userCache[$id])) {
            return self::$userCache[$id];
        }

        try {
            $user = $this->userRepository->findById($id);
            if ($user) {
                self::$userCache[$id] = $user;
            }
            return $user;
        } catch (\Exception $e) {
            throw new ServiceException(
                "Erreur lors de la récupération de l'utilisateur",
                500,
                'AuthService',
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
                'AuthService',
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
                'AuthService',
                'create',
                $this->getErrors()
            );
        }

        try {
            return $this->register($data);
        } catch (AuthenticationException $e) {
            throw new ServiceException(
                $e->getMessage(),
                $e->getCode(),
                'AuthService',
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
                'AuthService',
                'update'
            );
        }

        if (!$this->validate($data, ['update' => true])) {
            throw new ServiceException(
                "Erreur de validation",
                422,
                'AuthService',
                'update',
                $this->getErrors()
            );
        }

        try {
            if (!$this->userRepository->update($id, $data)) {
                throw new ServiceException(
                    "Échec de la mise à jour de l'utilisateur",
                    500,
                    'AuthService',
                    'update'
                );
            }
            return $this->get($id);
        } catch (\Exception $e) {
            throw new ServiceException(
                "Erreur lors de la mise à jour de l'utilisateur",
                500,
                'AuthService',
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
                'AuthService',
                'delete',
                [],
                $e
            );
        }
    }

    public function authenticate(string $email, string $password): User
    {
        try {
            $cacheKey = md5($email . $password);
            if (isset(self::$userCache[$cacheKey])) {
                $this->login(self::$userCache[$cacheKey]);
                return self::$userCache[$cacheKey];
            }

            $user = $this->userRepository->findByEmail($email);
            if (!$user || !$user->verifyPassword($password)) {
                throw AuthenticationException::invalidCredentials();
            }

            self::$userCache[$cacheKey] = $user;
            $this->login($user);

            return $user;
        } catch (AuthenticationException $e) {
            throw $e;
        }
    }

    public function logout(): void
    {
        try {
            // Clear current user
            $this->currentUser = null;

            // Check if session exists
            if (session_status() === PHP_SESSION_ACTIVE) {
                // Clear all session data
                $_SESSION = [];

                // Clear session cookie
                if (isset($_COOKIE[session_name()])) {
                    setcookie(session_name(), '', time() - 3600, '/');
                }

                // Destroy the session
                session_destroy();
            }
        } catch (\Exception $e) {
            error_log('Erreur lors de la déconnexion: ' . $e->getMessage());
            throw $e;
        }
    }

    public function check(): bool
    {
        try {
            if (!isset($_SESSION['user'])) {
                return false;
            }

            return $this->validateSession();
        } catch (\Exception $e) {
            error_log('Error in AuthService::check: ' . $e->getMessage());
            return false;
        }
    }

    public function user(): ?User
    {
        if ($this->currentUser === null && isset($_SESSION['user']['id'])) {
            $this->currentUser = $this->userRepository->findById($_SESSION['user']['id']);
        }
        return $this->currentUser;
    }

    public function hasRole(string $role): bool
    {
        return $this->currentUser && $this->currentUser->hasRole($role);
    }

    public function hasPermission(string $permission): bool
    {
        // Utilise isAdmin comme substitut de hasPermission
        return $this->currentUser && $this->currentUser->isAdmin();
    }

    private function prepareValidationData(array $data): array 
    {
        // Créer un nouveau tableau avec les données originales
        $dataToValidate = [];
        
        // Copier toutes les données existantes
        foreach ($data as $key => $value) {
            $dataToValidate[$key] = $value;
        }
        
        // Ajouter ou remplacer password_confirmation
        $dataToValidate['password_confirmation'] = $data['password_confirm'] ?? null;
        
        return $dataToValidate;
    }

    /**
     * Hash un mot de passe
     */
    private function hashPassword(string $password): string 
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function register(array $data): User
    {
        // Validation des données
        $validatedData = $this->validateRegistrationData($data);
        if (!$validatedData) {
            throw new \InvalidArgumentException("Données d'inscription invalides");
        }

        try {
            // Créer l'utilisateur via le repository
            $userId = $this->userRepository->create([
                'email' => $validatedData['email'],
                'nom' => $validatedData['nom'],
                'prenom' => $validatedData['prenom'],
                'password' => $this->hashPassword($validatedData['password']),
                'role_id' => 3, // ID fixe pour le rôle étudiant
                'status' => 'pending',
                'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
                'updated_at' => (new \DateTime())->format('Y-m-d H:i:s')
            ]);

            $user = $this->userRepository->findById($userId);
            if (!$user) {
                throw new \Exception("Erreur lors de la sauvegarde de l'utilisateur");
            }

            return $user;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function emailExists(string $email): bool
    {
        return $this->userRepository->emailExists($email);
    }

    public function generatePasswordResetToken(string $email): string
    {
        $user = $this->userRepository->findByEmail($email);
        if (!$user) {
            throw AuthenticationException::invalidCredentials();
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = new \DateTime('+1 hour');

        if (!$this->userRepository->saveResetToken($user->getId(), $token, $expiresAt)) {
            throw new AuthenticationException("Impossible de générer le token");
        }

        return $token;
    }

    public function resetPassword(string $token, string $newPassword): bool
    {
        $tokenData = $this->userRepository->findResetToken($token);
        if (!$tokenData) {
            return false;
        }

        try {
            $user = $this->get($tokenData['user_id']);
            if (!$user) {
                return false;
            }

            $success = $this->userRepository->update($user->getId(), [
                'password' => $newPassword
            ]);

            if ($success) {
                $this->userRepository->deleteResetToken($token);
            }

            return $success;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function updatePassword(string $currentPassword, string $newPassword): bool
    {
        if (!$this->currentUser) {
            throw new AuthenticationException("Aucun utilisateur connecté");
        }

        if (!$this->currentUser->verifyPassword($currentPassword)) {
            throw new AuthenticationException("Mot de passe actuel incorrect");
        }

        return $this->userRepository->update(
            $this->currentUser->getId(),
            ['password' => $newPassword]
        );
    }

    public function isValidResetToken(string $token): bool
    {
        $tokenData = $this->userRepository->findResetToken($token);
        return $tokenData !== null &&
            new \DateTime() <= new \DateTime($tokenData['expires_at']);
    }

    public function getSessionId(): ?string
    {
        return session_id() ?: null;
    }

    public function regenerateSession(): bool
    {
        return session_regenerate_id(true);
    }

    /**
     * Trouve un utilisateur par son email
     *
     * @param string $email
     * @return User|null
     * @throws ServiceException
     */
    public function findUserByEmail(string $email): ?User
    {
        try {
            return $this->userRepository->findByEmail($email);
        } catch (\Exception $e) {
            throw new ServiceException(
                "Erreur lors de la recherche de l'utilisateur par email",
                500,
                'AuthService',
                'findUserByEmail',
                [],
                $e
            );
        }
    }

    /**
     * Tente d'authentifier un utilisateur avec ses identifiants
     * 
     * @param string $email Email de l'utilisateur
     * @param string $password Mot de passe de l'utilisateur
     * @return User|null L'utilisateur authentifié ou null si échec
     */
    public function attempt(string $email, string $password): ?User
    {
        try {
            // Rechercher l'utilisateur par email
            $user = $this->userRepository->findByEmail($email);
            
            if (!$user || !password_verify($password, $user->getPassword())) {
                return null;
            }

            // Utiliser la méthode login existante qui gère déjà la session correctement
            $this->login($user);
            
            return $user;
        } catch (\Exception $e) {
            error_log('Erreur lors de la tentative de connexion: ' . $e->getMessage());
            return null;
        }
    }

    private function validateSession(): bool
    {
        if (!isset($_SESSION['user']) || !isset($_SESSION['created_at']) || 
            !isset($_SESSION['ip']) || !isset($_SESSION['user_agent'])) {
            return false;
        }

        // Validate IP and User Agent
        if ($_SESSION['ip'] !== $_SERVER['REMOTE_ADDR'] || 
            $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            return false;
        }

        // Check session age
        if ((time() - $_SESSION['last_activity']) >= self::SESSION_LIFETIME) {
            $this->handleExpiredSession();
            return false;
        }

        // Update last activity time
        $_SESSION['last_activity'] = time();
        return true;
    }

    private function handleExpiredSession(): void
    {
        // Clear user data
        $this->currentUser = null;
        
        // Store flash message
        $_SESSION['flash_message'] = 'Votre session a expiré. Veuillez vous reconnecter.';
        
        // Clear session but keep flash message
        $flashMessage = $_SESSION['flash_message'];
        session_unset();
        $_SESSION['flash_message'] = $flashMessage;
        
        // Regenerate session ID
        session_regenerate_id(true);
    }

    private function login(User $user): void
    {
        // Regenerate session ID before setting any data
        session_regenerate_id(true);
        
        // Clear any existing session data
        $_SESSION = [];
        
        $this->currentUser = $user;
        $_SESSION['user'] = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'role_id' => $user->getRole()->getId(),
            'is_admin' => $user->isAdmin()
        ];
        $_SESSION['last_activity'] = time();
        $_SESSION['created_at'] = time();
        $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    }

    public function exists(int $id): bool
    {
        try {
            return $this->userRepository->exists($id);
        } catch (\Exception $e) {
            throw new ServiceException(
                "Erreur lors de la vérification de l'existence de l'utilisateur",
                500,
                'AuthService',
                'exists',
                [],
                $e
            );
        }
    }

    /**
     * Valide les données d'inscription
     * 
     * @param array $data Les données à valider
     * @return array|null Les données validées ou null si invalides
     */
    private function validateRegistrationData(array $data): ?array
    {
        // Vérifier que tous les champs requis sont présents
        $requiredFields = ['email', 'password', 'password_confirm', 'nom', 'prenom'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                error_log("Champ manquant: $field");
                return null;
            }
        }

        // Valider l'email
        if (!User::validateEmail($data['email'])) {
            error_log("Email invalide: {$data['email']}");
            return null;
        }

        // Vérifier si l'email existe déjà
        if ($this->emailExists($data['email'])) {
            error_log("Email déjà utilisé: {$data['email']}");
            return null;
        }

        // Vérifier que les mots de passe correspondent
        if ($data['password'] !== $data['password_confirm']) {
            error_log("Les mots de passe ne correspondent pas");
            return null;
        }

        // Vérifier la longueur du mot de passe
        if (strlen($data['password']) < 8) {
            error_log("Mot de passe trop court");
            return null;
        }

        // Retourner les données validées
        return [
            'email' => $data['email'],
            'password' => $data['password'],
            'password_confirmation' => $data['password_confirm'],
            'nom' => $data['nom'],
            'prenom' => $data['prenom']
        ];
    }
}
