<?php

namespace App\Api\Controllers;

use Core\Controller\BaseController;
use Core\Database\Database;
use Core\Http\JsonResponse;
use Core\Http\Response;
use Core\Http\Request;
use Core\Exception\ValidatorException;
use Core\Validator\Validator;
use App\Security\CsrfToken;
use App\Services\AuthService;
use App\Models\Repository\UserRepository;

class UserApiController extends BaseController
{
    protected Database $db;
    protected Validator $validator;
    protected AuthService $auth;

    public function initialize(): void
    {
        parent::initialize();
        $this->db = Database::getInstance();
        $this->validator = new Validator();
        $userRepository = new UserRepository();
        $this->auth = new AuthService($userRepository);

        if (!$this->auth->check()) {
            throw new \Exception('Authentification requise');
        }
    }

    /**
     * Liste tous les utilisateurs
     * GET /api/users
     */
    public function index(Request $request, Response $response): Response
    {
        if (!$this->auth->hasRole('admin')) {
            return JsonResponse::forbidden('Accès non autorisé');
        }

        try {
            $users = $this->db->query("
                SELECT u.*, r.name as role_name
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                ORDER BY u.created_at DESC
            ")->fetchAll();

            return JsonResponse::success([
                'users' => $users,
                'csrf_token' => CsrfToken::generate()
            ]);
        } catch (\Exception $e) {
            return JsonResponse::serverError('Erreur lors de la récupération des utilisateurs');
        }
    }

    /**
     * Récupère un utilisateur spécifique
     * GET /api/users/:id
     */
    public function show(Request $request, Response $response, int $id): Response
    {
        $currentUser = $this->auth->user();
        if (!$this->auth->hasRole('admin') && $currentUser->getId() !== $id) {
            return JsonResponse::forbidden('Accès non autorisé');
        }

        try {
            $user = $this->db->prepare("
                SELECT u.*, r.name as role_name
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE u.id = ?
            ", [$id])->fetch();

            if (!$user) {
                return JsonResponse::notFound('Utilisateur non trouvé');
            }

            return JsonResponse::success([
                'user' => $user,
                'csrf_token' => CsrfToken::generate()
            ]);
        } catch (\Exception $e) {
            return JsonResponse::serverError('Erreur lors de la récupération de l\'utilisateur');
        }
    }
    /**
     * Crée un nouvel utilisateur
     * POST /api/users
     */
    public function store(Request $request, Response $response): Response
    {
        if (!$this->auth->hasRole('admin')) {
            return JsonResponse::forbidden('Accès non autorisé');
        }

        $data = $request->json();
        if (!CsrfToken::verify($data['csrf_token'] ?? null)) {
            return JsonResponse::forbidden('Token CSRF invalide');
        }

        try {
            if (!$data) {
                return JsonResponse::badRequest('Données JSON invalides');
            }

            $rules = [
                'nom' => ['required', 'string', 'max:100'],
                'prenom' => ['required', 'string', 'max:100'],
                'email' => ['required', 'email', 'unique:users'],
                'password' => ['required', 'string', 'min:8'],
                'adresse' => ['nullable', 'string'],
                'role_id' => ['required', 'exists:roles,id'],
                'is_admin' => ['boolean']
            ];

            $validatedData = $this->validator->validate($data, $rules);

            $userData = [
                'nom' => $validatedData['nom'],
                'prenom' => $validatedData['prenom'],
                'email' => $validatedData['email'],
                'password' => password_hash($validatedData['password'], PASSWORD_DEFAULT),
                'adresse' => $validatedData['adresse'] ?? null,
                'role_id' => $validatedData['role_id'],
                'is_admin' => $validatedData['is_admin'] ?? false
            ];

            $newUserId = $this->db->insert('users', $userData);

            if (!$newUserId) {
                throw new \Exception('Erreur lors de la création de l\'utilisateur');
            }

            return JsonResponse::created([
                'message' => 'Utilisateur créé avec succès',
                'id' => $newUserId,
                'csrf_token' => CsrfToken::generate()
            ]);
        } catch (ValidatorException $e) {
            return JsonResponse::validationError($e->getErrors());
        } catch (\Exception $e) {
            return JsonResponse::serverError('Erreur lors de la création de l\'utilisateur');
        }
    }

    /**
     * Met à jour un utilisateur
     * PUT /api/users/:id
     */
    public function update(Request $request, Response $response, int $id): Response
    {
        $currentUser = $this->auth->user();
        if (!$this->auth->hasRole('admin') && $currentUser->getId() !== $id) {
            return JsonResponse::forbidden('Accès non autorisé');
        }

        $data = $request->json();
        if (!CsrfToken::verify($data['csrf_token'] ?? null)) {
            return JsonResponse::forbidden('Token CSRF invalide');
        }

        try {
            if (!$data) {
                return JsonResponse::badRequest('Données JSON invalides');
            }

            $rules = [
                'nom' => ['string', 'max:100'],
                'prenom' => ['string', 'max:100'],
                'email' => ['email', 'unique:users,' . $id],
                'password' => ['string', 'min:8'],
                'adresse' => ['nullable', 'string'],
                'role_id' => ['exists:roles,id'],
                'is_admin' => ['boolean']
            ];

            $validatedData = $this->validator->validate($data, $rules);

            if (isset($validatedData['password'])) {
                $validatedData['password'] = password_hash($validatedData['password'], PASSWORD_DEFAULT);
            }

            $this->db->update('users', $id, $validatedData);

            return JsonResponse::success([
                'message' => 'Utilisateur mis à jour avec succès',
                'id' => $id,
                'csrf_token' => CsrfToken::generate()
            ]);
        } catch (ValidatorException $e) {
            return JsonResponse::validationError($e->getErrors());
        } catch (\Exception $e) {
            return JsonResponse::serverError('Erreur lors de la mise à jour de l\'utilisateur');
        }
    }

    /**
     * Supprime un utilisateur
     * DELETE /api/users/:id
     */
    public function destroy(Request $request, Response $response, int $id): Response
    {
        if (!$this->auth->hasRole('admin')) {
            return JsonResponse::forbidden('Accès non autorisé');
        }

        $data = $request->json();
        if (!CsrfToken::verify($data['csrf_token'] ?? null)) {
            return JsonResponse::forbidden('Token CSRF invalide');
        }

        $currentUser = $this->auth->user();
        if ($id === $currentUser->getId()) {
            return JsonResponse::forbidden('Vous ne pouvez pas supprimer votre propre compte');
        }

        try {
            $exists = $this->db->exists('users', $id);
            if (!$exists) {
                return JsonResponse::notFound('Utilisateur non trouvé');
            }

            $this->db->delete('users', $id);

            return JsonResponse::success([
                'message' => 'Utilisateur supprimé avec succès',
                'id' => $id,
                'csrf_token' => CsrfToken::generate()
            ]);
        } catch (\Exception $e) {
            return JsonResponse::serverError('Erreur lors de la suppression de l\'utilisateur');
        }
    }
}
