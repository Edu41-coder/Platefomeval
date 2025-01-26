<?php

namespace App\Middleware;

use Core\Middleware\MiddlewareInterface;
use Core\Middleware\MiddleWareException;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\JsonResponse;
use App\Security\CsrfToken;
use App\Models\Entity\Role;
use App\Services\AuthService;
use App\Models\Repository\UserRepository;
use App\Models\Repository\RoleRepository;

/**
 * Middleware de vérification d'authentification
 */
class AuthMiddleware implements MiddlewareInterface
{
    /**
     * Rôles autorisés pour cette route
     */
    protected array $allowedRoles = [];

    /**
     * Service d'authentification
     */
    protected AuthService $auth;

    private RoleRepository $roleRepository;

    /**
     * Constructeur
     *
     * @param string|array|null $roles Rôle(s) autorisé(s)
     */
    public function __construct($roles = null)
    {
        if ($roles !== null) {
            $this->allowedRoles = is_array($roles) ? $roles : [$roles];
        }
        $this->auth = AuthService::getInstance();
        $this->roleRepository = new RoleRepository();
    }

    /**
     * Vérifie si l'utilisateur est authentifié et a les droits nécessaires
     */
    public function handle(Request $request, callable $next): Response
    {
        try {
            // Ensure session is started
            AuthService::ensureSession();
            
            // Check if user is authenticated using AuthService
            if (!$this->auth->check()) {
                if ($request->wantsJson()) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Session expirée ou invalide',
                        'redirect' => '/Plateformeval/public/login'
                    ], 401);
                }
                
                // Store intended URL for redirect after login
                $_SESSION['intended_url'] = $request->getUri() ?? '/Plateformeval/public/dashboard';
                return new Response('', 302, ['Location' => '/Plateformeval/public/login']);
            }

            // Get current user
            $user = $this->auth->user();
            if (!$user) {
                if ($request->wantsJson()) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Utilisateur non trouvé',
                        'redirect' => '/Plateformeval/public/login'
                    ], 401);
                }
                return new Response('', 302, ['Location' => '/Plateformeval/public/login']);
            }

            // CSRF check for non-GET requests
            if (!$request->isGet()) {
                $data = $request->getJson();
                $token = $data['csrf_token'] ?? $request->post('csrf_token');

                if (!CsrfToken::verify($token)) {
                    if ($request->wantsJson()) {
                        return new JsonResponse([
                            'success' => false,
                            'message' => 'Token CSRF invalide'
                        ], 403);
                    }
                    throw MiddleWareException::forbidden('Token CSRF invalide');
                }
            }

            // Role check if needed
            if (!empty($this->allowedRoles)) {
                if ($user->isAdmin()) {
                    return $next($request);
                }

                $hasAllowedRole = false;
                foreach ($this->allowedRoles as $roleName) {
                    $role = $this->roleRepository->findByName($roleName);
                    if ($role && $this->auth->hasRole($role->getName())) {
                        $hasAllowedRole = true;
                        break;
                    }
                }

                if (!$hasAllowedRole) {
                    if ($request->wantsJson()) {
                        return new JsonResponse([
                            'success' => false,
                            'message' => 'Accès non autorisé'
                        ], 403);
                    }
                    throw MiddleWareException::forbidden('Accès non autorisé');
                }
            }

            // Update last activity time
            $_SESSION['last_activity'] = time();

            return $next($request);
        } catch (\Exception $e) {
            error_log('AuthMiddleware Exception: ' . $e->getMessage());
            
            if ($request->wantsJson()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Erreur d\'authentification: ' . $e->getMessage()
                ], 500);
            }
            throw new MiddleWareException($e->getMessage(), 500);
        }
    }

    /**
     * Crée un middleware qui requiert un rôle spécifique
     */
    public static function role(string $role): self
    {
        return new self($role);
    }

    /**
     * Crée un middleware qui requiert un des rôles spécifiés
     */
    public static function roles(array $roles): self
    {
        return new self($roles);
    }

    /**
     * Crée un middleware qui requiert le rôle admin
     */
    public static function admin(): self
    {
        return new self(Role::ADMIN);
    }

    /**
     * Crée un middleware qui requiert le rôle professeur
     */
    public static function professeur(): self
    {
        return new self(Role::PROFESSEUR);
    }

    /**
     * Crée un middleware qui requiert le rôle étudiant
     */
    public static function etudiant(): self
    {
        return new self(Role::ETUDIANT);
    }

    /**
     * Crée un middleware qui requiert uniquement l'authentification
     */
    public static function auth(): self
    {
        return new self();
    }

    /**
     * Crée un middleware qui requiert le rôle admin ou professeur
     */
    public static function adminOrProfesseur(): self
    {
        return new self([Role::ADMIN, Role::PROFESSEUR]);
    }
}
