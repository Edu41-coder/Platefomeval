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
use App\Models\Repository\MatiereRepository;

class MatiereApiController extends BaseController
{
    protected Database $db;
    protected Validator $validator;
    protected MatiereRepository $matiereRepository;

    public function initialize(): void
    {
        parent::initialize();
        $this->db = Database::getInstance();
        $this->validator = new Validator();
        $this->matiereRepository = new MatiereRepository();
    }

    /**
     * Liste toutes les matières
     * GET /api/matieres
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            $matieres = $this->matiereRepository->findAll();

            return JsonResponse::success([
                'matieres' => $matieres,
                'csrf_token' => CsrfToken::generate()
            ]);
        } catch (\Exception $e) {
            return JsonResponse::serverError('Erreur lors de la récupération des matières');
        }
    }

    /**
     * Récupère une matière spécifique
     * GET /api/matieres/:id
     */
    public function show(Request $request, Response $response, int $id): Response
    {
        try {
            $matiere = $this->matiereRepository->findById($id);

            if (!$matiere) {
                return JsonResponse::notFound('Matière non trouvée');
            }

            return JsonResponse::success([
                'matiere' => $matiere,
                'csrf_token' => CsrfToken::generate()
            ]);
        } catch (\Exception $e) {
            return JsonResponse::serverError('Erreur lors de la récupération de la matière');
        }
    }

    /**
     * Crée une nouvelle matière
     * POST /api/matieres
     */
    public function store(Request $request, Response $response): Response
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
                'nom' => ['required', 'string', 'max:100'],
                'description' => ['nullable', 'string']
            ];

            $validatedData = $this->validator->validate($data, $rules);

            $matiere = $this->matiereRepository->create($validatedData);

            return JsonResponse::created([
                'message' => 'Matière créée avec succès',
                'matiere' => $matiere,
                'csrf_token' => CsrfToken::generate()
            ]);
        } catch (ValidatorException $e) {
            return JsonResponse::validationError($e->getErrors());
        } catch (\Exception $e) {
            return JsonResponse::serverError('Erreur lors de la création de la matière');
        }
    }

    /**
     * Met à jour une matière
     * PUT /api/matieres/:id
     */
    public function update(Request $request, Response $response, int $id): Response
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
                'nom' => ['string', 'max:100'],
                'description' => ['nullable', 'string']
            ];

            $validatedData = $this->validator->validate($data, $rules);

            $matiere = $this->matiereRepository->update($id, $validatedData);

            return JsonResponse::success([
                'message' => 'Matière mise à jour avec succès',
                'matiere' => $matiere,
                'csrf_token' => CsrfToken::generate()
            ]);
        } catch (ValidatorException $e) {
            return JsonResponse::validationError($e->getErrors());
        } catch (\Exception $e) {
            return JsonResponse::serverError('Erreur lors de la mise à jour de la matière');
        }
    }

    /**
     * Supprime une matière
     * DELETE /api/matieres/:id
     */
    public function destroy(Request $request, Response $response, int $id): Response
    {
        $data = $request->json();
        if (!CsrfToken::verify($data['csrf_token'] ?? null)) {
            return JsonResponse::forbidden('Token CSRF invalide');
        }

        try {
            $exists = $this->matiereRepository->exists($id);
            if (!$exists) {
                return JsonResponse::notFound('Matière non trouvée');
            }

            $this->matiereRepository->delete($id);

            return JsonResponse::success([
                'message' => 'Matière supprimée avec succès',
                'csrf_token' => CsrfToken::generate()
            ]);
        } catch (\Exception $e) {
            return JsonResponse::serverError('Erreur lors de la suppression de la matière');
        }
    }
}