<?php

namespace App\Api\Controllers;

use Core\Controller\BaseController;
use Core\Http\JsonResponse;
use Core\Http\Response;
use Core\Http\Request;
use Core\Exception\ValidatorException;
use Core\Validator\Validator;
use App\Security\CsrfToken;
use App\Models\Repository\UserRepository;
use App\Services\AuthService;

class ProfileApiController extends BaseController
{
    protected Validator $validator;
    protected AuthService $authService;
    protected UserRepository $userRepository;

    public function initialize(): void
    {
        parent::initialize();
        $this->validator = new Validator();
        $this->userRepository = new UserRepository();
        $this->authService = new AuthService($this->userRepository);
    }

    public function index(Request $request, Response $response): Response
    {
        // Implement the logic for the index method
        // For example, you might want to return a list of profiles or redirect to another method
        return $this->show($request, $response);
    }

    /**
     * Récupère le profil de l'utilisateur connecté
     * GET /api/profile
     */
    public function show(Request $request, Response $response): Response
    {
        try {
            $user = $this->authService->user();

            if (!$user) {
                return JsonResponse::notFound('Utilisateur non trouvé');
            }

            return JsonResponse::success([
                'user' => $user->toArray(),
                'csrf_token' => CsrfToken::generate()
            ]);
        } catch (\Exception $e) {
            return JsonResponse::serverError('Erreur lors de la récupération du profil utilisateur');
        }
    }

    /**
     * Met à jour le profil de l'utilisateur connecté
     * PUT /api/profile
     */
    public function update(Request $request, Response $response): Response
    {
        $data = $request->json();
        if (!CsrfToken::verify($data['csrf_token'] ?? null)) {
            return JsonResponse::forbidden('Token CSRF invalide');
        }

        try {
            if (!$data) {
                return JsonResponse::badRequest('Données JSON invalides');
            }

            $rules = [
                'adresse' => ['required', 'string', 'max:255']
            ];

            $validatedData = $this->validator->validate($data, $rules);

            $user = $this->authService->user();
            if (!$user) {
                return JsonResponse::notFound('Utilisateur non trouvé');
            }

            $this->userRepository->update($user->getId(), $validatedData);

            return JsonResponse::success([
                'message' => 'Profil mis à jour avec succès',
                'user' => $user->toArray(),
                'csrf_token' => CsrfToken::generate()
            ]);
        } catch (ValidatorException $e) {
            return JsonResponse::validationError($e->getErrors());
        } catch (\Exception $e) {
            return JsonResponse::serverError('Erreur lors de la mise à jour du profil utilisateur');
        }
    }

    /**
     * Met à jour le mot de passe de l'utilisateur connecté
     * PUT /api/profile/password
     */
    public function updatePassword(Request $request, Response $response): Response
    {
        $data = $request->json();
        if (!CsrfToken::verify($data['csrf_token'] ?? null)) {
            return JsonResponse::forbidden('Token CSRF invalide');
        }

        try {
            if (!$data) {
                return JsonResponse::badRequest('Données JSON invalides');
            }

            $rules = [
                'current_password' => ['required', 'string'],
                'new_password' => ['required', 'string', 'min:6']
            ];

            $validatedData = $this->validator->validate($data, $rules);

            $user = $this->authService->user();
            if (!$user) {
                return JsonResponse::notFound('Utilisateur non trouvé');
            }

            if (!$this->authService->updatePassword($validatedData['current_password'], $validatedData['new_password'])) {
                return JsonResponse::validationError(['current_password' => 'Mot de passe actuel incorrect']);
            }

            return JsonResponse::success([
                'message' => 'Mot de passe mis à jour avec succès',
                'csrf_token' => CsrfToken::generate()
            ]);
        } catch (ValidatorException $e) {
            return JsonResponse::validationError($e->getErrors());
        } catch (\Exception $e) {
            return JsonResponse::serverError('Erreur lors de la mise à jour du mot de passe');
        }
    }
}