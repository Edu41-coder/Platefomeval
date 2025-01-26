<?php

namespace App\Api\Controllers;

use Core\Controller\BaseController;
use Core\Http\JsonResponse;
use Core\Http\Response;
use Core\Http\Request;
use Core\Exception\ValidatorException;
use Core\Validator\Validator;
use App\Security\CsrfToken;
use App\Services\EvaluationService;

class EvaluationApiController extends BaseController
{
    private EvaluationService $evaluationService;
    protected Validator $validator;

    public function __construct(EvaluationService $evaluationService)
    {
        parent::__construct();
        $this->evaluationService = $evaluationService;
        $this->validator = new Validator();
    }

    /**
     * Liste toutes les évaluations
     * GET /api/evaluations
     *
     * @OA\Get(
     *     path="/api/evaluations",
     *     summary="Liste toutes les évaluations",
     *     tags={"Evaluations"},
     *     @OA\Parameter(
     *         name="Origin",
     *         in="header",
     *         description="Domaine d'origine",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des évaluations",
     *         @OA\Header(
     *             header="Access-Control-Allow-Origin",
     *             description="Domaine autorisé",
     *             @OA\Schema(type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request, Response $response): Response
    {
        // Le middleware CORS a déjà géré les en-têtes
        try {
            $evaluations = $this->evaluationService->getAllEvaluationsWithDetails();
            return JsonResponse::success(['evaluations' => $evaluations]);
        } catch (\Exception $e) {
            return JsonResponse::serverError('Erreur lors de la récupération des évaluations');
        }
    }

    /**
     * Récupère une évaluation spécifique
     * GET /api/evaluations/:id
     */
    public function show(Request $request, Response $response, int $id): Response
    {
        try {
            $evaluation = $this->evaluationService->getEvaluationWithDetails($id);

            if (!$evaluation) {
                return JsonResponse::notFound('Évaluation non trouvée');
            }

            return JsonResponse::success([
                'evaluation' => $evaluation,
                'csrf_token' => CsrfToken::generate()
            ]);
        } catch (\Exception $e) {
            return JsonResponse::serverError('Erreur lors de la récupération de l\'évaluation');
        }
    }

    /**
     * Crée une nouvelle évaluation
     * POST /api/evaluations
     */
    public function store(Request $request, Response $response): Response
    {
        try {
            $data = $request->json();
            if (!CsrfToken::verify($data['csrf_token'] ?? null)) {
                return JsonResponse::forbidden('Token CSRF invalide');
            }

            if (!$data) {
                return JsonResponse::badRequest('Données JSON invalides');
            }

            $validatedData = $this->evaluationService->validateEvaluationData($data);
            $newEvaluation = $this->evaluationService->create($validatedData);

            return JsonResponse::created([
                'message' => 'Évaluation créée avec succès',
                'evaluation' => $newEvaluation,
                'csrf_token' => CsrfToken::generate()
            ]);
        } catch (ValidatorException $e) {
            return JsonResponse::validationError($e->getErrors());
        } catch (\Exception $e) {
            return JsonResponse::serverError('Erreur lors de la création de l\'évaluation');
        }
    }

    /**
     * Met à jour une évaluation
     * PUT /api/evaluations/:id
     */
    public function update(Request $request, Response $response, int $id): Response
    {
        try {
            $data = $request->json();
            if (!CsrfToken::verify($data['csrf_token'] ?? null)) {
                return JsonResponse::forbidden('Token CSRF invalide');
            }

            if (!$data) {
                return JsonResponse::badRequest('Données JSON invalides');
            }

            $validatedData = $this->evaluationService->validateEvaluationData($data);
            $updatedEvaluation = $this->evaluationService->update($id, $validatedData);

            return JsonResponse::success([
                'message' => 'Évaluation mise à jour avec succès',
                'evaluation' => $updatedEvaluation,
                'csrf_token' => CsrfToken::generate()
            ]);
        } catch (ValidatorException $e) {
            return JsonResponse::validationError($e->getErrors());
        } catch (\Exception $e) {
            return JsonResponse::serverError('Erreur lors de la mise à jour de l\'évaluation');
        }
    }

    /**
     * Supprime une évaluation
     * DELETE /api/evaluations/:id
     */
    public function destroy(Request $request, Response $response, int $id): Response
    {
        try {
            $data = $request->json();
            if (!CsrfToken::verify($data['csrf_token'] ?? null)) {
                return JsonResponse::forbidden('Token CSRF invalide');
            }

            if (!$this->evaluationService->exists($id)) {
                return JsonResponse::notFound('Évaluation non trouvée');
            }

            $this->evaluationService->delete($id);

            return JsonResponse::success([
                'message' => 'Évaluation supprimée avec succès',
                'id' => $id,
                'csrf_token' => CsrfToken::generate()
            ]);
        } catch (\Exception $e) {
            return JsonResponse::serverError('Erreur lors de la suppression de l\'évaluation');
        }
    }
}