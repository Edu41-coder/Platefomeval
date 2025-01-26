<?php

namespace App\Controllers\Auth;

use Core\Controller\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\JsonResponse;
use Core\Exception\ControllerException;
use Core\Exception\ValidatorException;
use Core\Exception\AuthenticationException;
use Core\Exception\ServiceException;
use App\Security\CsrfToken;
use App\Services\AuthService;
use App\Models\Entity\Role;
use App\Models\Repository\UserRepository;
/**
 * Contrôleur de gestion de l'authentification
 */
class AuthController extends BaseController
{
    protected AuthService $auth;

    public function __construct()
    {
        parent::__construct();
        AuthService::ensureSession();
        $this->auth = AuthService::getInstance();
    }

    /**
     * Affiche le formulaire de connexion
     */
    public function loginForm(Request $request, Response $response): Response
    {
        try {
            if ($this->isAuthenticated()) {
                return $this->redirect(redirect_path('dashboard'));
            }

            // Configuration des assets
            $assets = [
                'css' => ['css/auth.css'],
                'js' => ['js/app.js', 'js/login.js']
            ];

            // Ajouter les assets à loadedAssets
            $this->addAssets($assets);

            // Données pour la vue
            $renderData = [
                'pageTitle' => 'Connexion',
                'site_title' => $_ENV['APP_NAME'] ?? 'Plateforme d\'évaluation',
                'csrfToken' => CsrfToken::getToken(),
                'assets' => $this->loadedAssets,
                'user' => $this->auth->user(),
                'bodyClass' => 'login-page'
            ];
            
            $this->setLayout('auth');
            return $this->render('auth/login', $renderData);
        } catch (\Exception $e) {
            error_log('Error in loginForm: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Authentifie un utilisateur
     */
    public function login(Request $request, Response $response): Response
    {
        try {
            // Get raw input and parse JSON data
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Invalid JSON data',
                    'error' => json_last_error_msg()
                ], 400);
            }
            
            // Vérifier le token CSRF
            $csrfToken = $data['csrf_token'] ?? null;
            if (!CsrfToken::verify($csrfToken)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Token CSRF invalide'
                ], 422);
            }

            // Valider les données requises
            if (!isset($data['email']) || !isset($data['password'])) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Email et mot de passe requis'
                ], 422);
            }

            // Tentative de connexion
            $user = $this->auth->attempt($data['email'], $data['password']);
            if (!$user) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Email ou mot de passe incorrect'
                ], 401);
            }

            // Préparer la réponse
            $redirectUrl = redirect_path('dashboard');
            $responseData = [
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->getId(),
                        'email' => $user->getEmail(),
                        'nom' => $user->getNom(),
                        'prenom' => $user->getPrenom(),
                        'role' => $user->getRole()->getName(),
                        'is_admin' => $user->isAdmin()
                    ],
                    'csrf_token' => CsrfToken::getToken(),
                    'redirect' => $redirectUrl
                ],
                'message' => 'Connexion réussie'
            ];

            return new JsonResponse($responseData, 200);

        } catch (\Exception $e) {
            error_log('Login error: ' . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la connexion'
            ], 500);
        }
    }

    /**
     * Déconnecte l'utilisateur
     */
    public function logout(): Response
    {
        try {
            $this->auth->logout();
            $finalUrl = redirect_path('home');

            if ($this->request->isAjax()) {
                return JsonResponse::success([
                    'message' => 'Déconnexion réussie',
                    'redirect' => $finalUrl
                ]);
            }

            return new Response('', 302, ['Location' => $finalUrl]);
            
        } catch (\Exception $e) {
            error_log('Error during logout: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupère les informations de l'utilisateur connecté
     */
    public function me(): JsonResponse
    {
        if (!$this->auth->check()) {
            throw ControllerException::unauthorized('Non authentifié');
        }

        return JsonResponse::success([
            'user' => $this->auth->user()->toArray(),
            'csrf_token' => CsrfToken::getToken()
        ]);
    }

    /**
     * Inscrit un nouvel utilisateur
     */
    public function register(Request $request, Response $response): Response
    {
        try {
            // Si l'utilisateur est déjà connecté, rediriger vers le dashboard
            if ($this->isAuthenticated()) {
                return $this->redirect('dashboard');
            }

            // Configuration des assets
            $assets = [
                'css' => ['css/auth.css'],
                'js' => ['js/register.js', 'js/password-strength.js']
            ];

            // Ajouter les assets
            $this->addAssets($assets);

            // Préparer les données pour la vue
            $renderData = [
                'pageTitle' => 'Inscription',
                'site_title' => $_ENV['APP_NAME'] ?? 'Plateforme d\'évaluation',
                'csrfToken' => CsrfToken::getToken(),
                'assets' => $this->loadedAssets,
                'bodyClass' => 'register-page'
            ];

            // Spécifier explicitement le layout auth
            $this->setLayout('auth');
            return $this->render('auth/register', $renderData);

        } catch (\Exception $e) {
            error_log('Registration error: ' . $e->getMessage());
            error_log($e->getTraceAsString());
            
            // En cas d'erreur, retourner une réponse JSON avec l'erreur
            return new JsonResponse([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'inscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifie si l'utilisateur a un rôle spécifique
     */
    protected function requireRole(string $role): void
    {
        if (!$this->auth->hasRole($role)) {
            throw ControllerException::forbidden('Accès non autorisé');
        }
    }

    /**
     * Vérifie si l'utilisateur est admin
     */
    protected function requireAdmin(): void
    {
        if (!$this->auth->hasRole('admin')) {
            throw ControllerException::forbidden('Accès réservé aux administrateurs');
        }
    }

    /**
     * Génère un nouveau token CSRF
     */
    public function refreshToken(): JsonResponse
    {
        return JsonResponse::success([
            'token' => CsrfToken::generate()
        ]);
    }

    /**
     * Vérifie si un email existe déjà
     */
    public function checkEmail(): JsonResponse
    {
        $email = $this->request->get('email');

        if (!$email) {
            throw ControllerException::badRequest('Email requis');
        }

        return JsonResponse::success([
            'exists' => $this->auth->emailExists($email),
            'csrf_token' => CsrfToken::generate()
        ]);
    }
    /**
     * Affiche le formulaire d'inscription
     */
    public function registerForm(Request $request, Response $response): Response
    {
        return $this->render('auth/register', [
            'pageTitle' => 'Inscription',
            'pageDescription' => 'Créez votre compte'
        ]);
    }

    public function index(Request $request, Response $response): Response
    {
        // Rediriger vers la page de connexion par défaut
        return $this->redirect('/login');
    }

    /**
     * Affiche le formulaire de réinitialisation de mot de passe
     */
    public function forgotPasswordForm(Request $request, Response $response): Response
    {
        try {
            if ($this->isAuthenticated()) {
                return $this->redirect(redirect_path('dashboard'));
            }

            // Configuration des assets
            $assets = [
                'css' => ['css/forgot-password.css'],
                'js' => ['js/app.js', 'js/forgot-password.js']
            ];

            $this->addAssets($assets);

            // Données pour la vue
            $renderData = [
                'pageTitle' => 'Mot de passe oublié',
                'site_title' => $_ENV['APP_NAME'] ?? 'Plateforme d\'évaluation',
                'csrfToken' => CsrfToken::getToken(),
                'assets' => $this->loadedAssets,
                'user' => $this->auth->user(),
                'bodyClass' => 'forgot-password-page'
            ];
            
            $this->setLayout('auth');
            return $this->render('auth/forgot-password', $renderData);
        } catch (\Exception $e) {
            error_log('Error in forgotPasswordForm: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Envoie le lien de réinitialisation de mot de passe
     */
    public function sendResetLink(Request $request, Response $response): Response
    {
        try {
            // Récupérer les données JSON
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Vérifier le token CSRF
            if (!isset($data['csrf_token']) || !CsrfToken::verify($data['csrf_token'])) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Token CSRF invalide'
                ], 422);
            }

            // Valider l'email
            if (!isset($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Adresse email invalide'
                ], 422);
            }

            $email = $data['email'];
            
            // Vérifier si l'utilisateur existe
            $user = $this->auth->findUserByEmail($email);
            if (!$user) {
                // Pour des raisons de sécurité, on renvoie un message de succès même si l'utilisateur n'existe pas
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Si votre email est enregistré, vous recevrez les instructions de réinitialisation.'
                ]);
            }

            // TODO: Générer un token de réinitialisation et l'envoyer par email
            // Pour l'instant, on simule juste le succès
            return new JsonResponse([
                'success' => true,
                'message' => 'Les instructions de réinitialisation ont été envoyées à votre adresse email.'
            ]);

        } catch (\Exception $e) {
            error_log('Error in sendResetLink: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            return new JsonResponse([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'envoi des instructions.'
            ], 500);
        }
    }

    /**
     * Traite la soumission du formulaire d'inscription
     */
    public function handleRegister(Request $request, Response $response): Response
    {
        try {
            // Vérifier le token CSRF
            if (!isset($_POST['csrf_token']) || !CsrfToken::verify($_POST['csrf_token'])) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Token CSRF invalide',
                    'errors' => ['csrf_token' => 'Token CSRF invalide']
                ], 400);
            }

            // Récupérer et nettoyer les données
            $data = array_map('trim', $_POST);

            if (empty($data)) {
                throw new ServiceException(
                    'Aucune donnée reçue',
                    422,
                    'AuthController',
                    'handleRegister'
                );
            }
            
            // Enregistrer l'utilisateur
            $user = $this->auth->register($data);

            return new JsonResponse([
                'success' => true,
                'message' => 'Inscription réussie ! Vous pouvez maintenant vous connecter.',
                'redirect' => url('login')
            ], 201);

        } catch (ServiceException $e) {
            error_log('Service error during registration: ' . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors()
            ], $e->getCode() ?: 400);
        } catch (\Exception $e) {
            error_log('Erreur lors de l\'inscription : ' . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'inscription. Veuillez réessayer.'
            ], 500);
        }
    }
}
