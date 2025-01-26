<?php

namespace App\Api\Controllers;

use Core\Controller\BaseController;
use Core\Http\JsonResponse;
use Core\Http\Request;
use App\Security\CsrfToken;
use App\Services\AuthService;
use Core\Exception\AuthenticationException;
use Core\Exception\ValidatorException;
use App\Models\Repository\UserRepository;
use App\Models\Entity\Role;
use Core\Http\Response;

class AuthApiController extends BaseController
{
    private AuthService $auth;

    public function initialize(): void
    {
        parent::initialize();
        $this->auth = new AuthService(new UserRepository());
    }

    /**
     * Page d'index par défaut
     * GET /api/auth
     */
    public function index(Request $request, Response $response): Response
    {
        // Rediriger vers la documentation de l'API ou retourner une réponse par défaut
        return JsonResponse::success([
            'message' => 'Auth API Endpoint',
            'version' => '1.0',
            'endpoints' => [
                'POST /api/auth/login' => 'Authentification',
                'POST /api/auth/logout' => 'Déconnexion',
                'GET /api/auth/me' => 'Informations utilisateur',
                'POST /api/auth/register' => 'Inscription',
                'POST /api/auth/refresh-token' => 'Rafraîchir le token CSRF',
                'POST /api/auth/check-email' => 'Vérifier email',
                'POST /api/auth/forgot-password' => 'Mot de passe oublié',
                'POST /api/auth/reset-password' => 'Réinitialiser mot de passe'
            ]
        ]);
    }

    /**
     * Authentifie un utilisateur
     * POST /api/auth/login
     */
    public function login(): JsonResponse
    {
        $data = $this->request->json();
        
        if (!CsrfToken::verify($data['csrf_token'] ?? null)) {
            return JsonResponse::forbidden('Token CSRF invalide');
        }

        if (!isset($data['email']) || !isset($data['password'])) {
            return JsonResponse::badRequest('Email et mot de passe requis');
        }

        try {
            $user = $this->auth->authenticate($data['email'], $data['password']);
            
            return JsonResponse::success([
                'message' => 'Connexion réussie',
                'user' => $user->toArray(),
                'csrf_token' => CsrfToken::getToken()
            ]);
        } catch (AuthenticationException $e) {
            return JsonResponse::unauthorized($e->getMessage());
        }
    }

    /**
     * Déconnecte l'utilisateur
     * POST /api/auth/logout
     */
    public function logout(): JsonResponse
    {
        $data = $this->request->json();
        
        if (!CsrfToken::verify($data['csrf_token'] ?? null)) {
            return JsonResponse::forbidden('Token CSRF invalide');
        }

        $this->auth->logout();
        
        return JsonResponse::success([
            'message' => 'Déconnexion réussie',
            'csrf_token' => CsrfToken::getToken()
        ]);
    }

    /**
     * Récupère les informations de l'utilisateur connecté
     * GET /api/auth/me
     */
    public function me(): JsonResponse
    {
        if (!$this->auth->check()) {
            return JsonResponse::unauthorized('Non authentifié');
        }

        return JsonResponse::success([
            'user' => $this->auth->user()->toArray(),
            'csrf_token' => CsrfToken::getToken()
        ]);
    }

    /**
     * Rafraîchit le token CSRF
     * POST /api/auth/refresh-token
     */
    public function refreshToken(): JsonResponse
    {
        return JsonResponse::success([
            'csrf_token' => CsrfToken::getToken()
        ]);
    }

    /**
     * Vérifie si un email existe
     * POST /api/auth/check-email
     */
    public function checkEmail(): JsonResponse
    {
        $data = $this->request->json();
        
        if (!isset($data['email'])) {
            return JsonResponse::badRequest('Email requis');
        }

        return JsonResponse::success([
            'exists' => $this->auth->emailExists($data['email']),
            'csrf_token' => CsrfToken::getToken()
        ]);
    }

    /**
     * Demande de réinitialisation de mot de passe
     * POST /api/auth/forgot-password
     */
    public function forgotPassword(): JsonResponse
    {
        $data = $this->request->json();
        
        if (!isset($data['email'])) {
            return JsonResponse::badRequest('Email requis');
        }

        try {
            $token = $this->auth->generatePasswordResetToken($data['email']);
            // TODO: Envoyer l'email avec le lien de réinitialisation
            
            return JsonResponse::success([
                'message' => 'Instructions envoyées par email',
                'csrf_token' => CsrfToken::getToken()
            ]);
        } catch (AuthenticationException $e) {
            return JsonResponse::badRequest($e->getMessage());
        }
    }

    /**
     * Réinitialise le mot de passe
     * POST /api/auth/reset-password
     */
    public function resetPassword(): JsonResponse
    {
        $data = $this->request->json();
        
        if (!isset($data['token']) || !isset($data['password'])) {
            return JsonResponse::badRequest('Token et nouveau mot de passe requis');
        }

        try {
            if ($this->auth->resetPassword($data['token'], $data['password'])) {
                return JsonResponse::success([
                    'message' => 'Mot de passe réinitialisé avec succès',
                    'csrf_token' => CsrfToken::getToken()
                ]);
            }
            return JsonResponse::badRequest('Token invalide ou expiré');
        } catch (AuthenticationException $e) {
            return JsonResponse::badRequest($e->getMessage());
        }
    }

    /**
     * Change le mot de passe de l'utilisateur connecté
     * PUT /api/auth/change-password
     */
    public function changePassword(): JsonResponse
    {
        if (!$this->auth->check()) {
            return JsonResponse::unauthorized('Non authentifié');
        }

        $data = $this->request->json();
        
        if (!isset($data['current_password']) || !isset($data['new_password'])) {
            return JsonResponse::badRequest('Mot de passe actuel et nouveau mot de passe requis');
        }

        try {
            $this->auth->updatePassword($data['current_password'], $data['new_password']);
            return JsonResponse::success([
                'message' => 'Mot de passe modifié avec succès',
                'csrf_token' => CsrfToken::getToken()
            ]);
        } catch (AuthenticationException $e) {
            return JsonResponse::badRequest($e->getMessage());
        }
    }

    /**
     * Inscrit un nouvel utilisateur
     * POST /api/auth/register
     */
    public function register(): JsonResponse
    {
        $data = $this->request->json();

        if (!CsrfToken::verify($data['csrf_token'] ?? null)) {
            return JsonResponse::forbidden('Token CSRF invalide');
        }

        try {
            $required = ['email', 'password', 'nom', 'prenom', 'role'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    throw new ValidatorException([
                        $field => ["Le champ '$field' est requis"]
                    ]);
                }
            }

            if (!Role::isValidRole($data['role'])) {
                throw new ValidatorException([
                    'role' => ['Le rôle spécifié n\'est pas valide']
                ]);
            }

            $user = $this->auth->register($data);

            return JsonResponse::created([
                'message' => 'Compte créé avec succès',
                'user' => $user->toArray(),
                'csrf_token' => CsrfToken::getToken()
            ]);
        } catch (ValidatorException $e) {
            return JsonResponse::badRequest($e->getMessage(), $e->getErrors());
        } catch (AuthenticationException $e) {
            return JsonResponse::badRequest($e->getMessage());
        }
    }
}