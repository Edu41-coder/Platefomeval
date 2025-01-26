<?php
namespace App\Controllers\Evaluation;

use Core\Controller\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use App\Services\EvaluationService;
use App\Services\MatiereService;
use Core\Exception\ValidatorException;
use Core\Validator\Validator;
use App\Models\Entity\Matiere;
use App\Models\Entity\Evaluation;
use App\Security\CsrfToken;

/**
 * Contrôleur de base pour les évaluations
 * Contient les méthodes et propriétés communes aux contrôleurs Student et Professor
 */
abstract class EvaluationController extends BaseController
{
    protected EvaluationService $evaluationService;
    protected MatiereService $matiereService;
    protected Validator $validator;

    public function __construct(EvaluationService $evaluationService, MatiereService $matiereService)
    {
        parent::__construct();
        $this->evaluationService = $evaluationService;
        $this->matiereService = $matiereService;
        $this->validator = new Validator();
    }

    /**
     * Ajoute les données communes à toutes les vues d'évaluation
     */
    protected function getCommonViewData(array $data = []): array
    {
        return array_merge([
            'csrfToken' => CsrfToken::getToken(),
        ], $data);
    }

    /**
     * Vérifie si l'utilisateur est authentifié et a les bons droits
     */
    protected function validateUser(): bool
    {
        if (!$this->isAuthenticated()) {
            return false;
        }
        return true;
    }

    /**
     * Récupère une évaluation par son ID
     */
    protected function getEvaluation(int $id)
    {
        return $this->evaluationService->getById($id);
    }

    /**
     * Valide les données communes d'une évaluation
     */
    protected function validateEvaluationData(array $data): array
    {
        return $this->validator->validate($data, [
            'type' => ['required', 'string'],
            'date' => ['required', 'date'],
            'description' => ['string', 'nullable'],
            'notes' => ['required', 'array'],
            'notes.*' => ['numeric', 'min:0', 'max:20', 'nullable'],
            'commentaires' => ['array', 'nullable']
        ]);
    }

    /**
     * Formate les notes pour le service
     */
    protected function formatNotes(array $notes, array $commentaires): array
    {
        $formattedNotes = [];
        foreach ($notes as $studentId => $note) {
            if ($note !== '' && $note !== null) {
                if (!is_numeric($note) || $note < 0 || $note > 20) {
                    throw new \Exception('Les notes doivent être comprises entre 0 et 20');
                }
                $formattedNotes[] = [
                    'etudiant_id' => $studentId,
                    'note' => (float)$note,
                    'commentaire' => $commentaires[$studentId] ?? null
                ];
            }
        }
        return $formattedNotes;
    }

    /**
     * Vérifie l'accès à une matière pour l'utilisateur courant
     * @param int $matiereId ID de la matière
     * @return Matiere|null La matière si l'accès est autorisé, null sinon
     */
    protected function validateMatiereAccess(int $matiereId): ?Matiere
    {
        $matiere = $this->matiereService->get($matiereId);
        if (!$matiere) {
            $this->addFlash('error', 'Cette matière n\'existe pas');
            return null;
        }
        return $matiere;
    }

    /**
     * Vérifie l'accès à une évaluation pour l'utilisateur courant
     * @param int $evaluationId ID de l'évaluation
     * @return Evaluation|null L'évaluation si l'accès est autorisé, null sinon
     */
    protected function validateEvaluationAccess(int $evaluationId): ?Evaluation
    {
        $evaluation = $this->evaluationService->getById($evaluationId);
        if (!$evaluation) {
            $this->addFlash('error', 'Cette évaluation n\'existe pas');
            return null;
        }
        return $evaluation;
    }

    // Méthodes abstraites que les enfants doivent implémenter
    abstract public function index(Request $request, Response $response): Response;
    abstract public function show(Request $request, Response $response, int $id): Response;
} 
