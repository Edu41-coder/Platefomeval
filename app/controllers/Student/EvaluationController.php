<?php

namespace App\Controllers\Student;

use Core\Http\Request;
use Core\Http\Response;
use App\Services\EvaluationService;
use App\Services\MatiereService;
use App\Services\AuthService;
use App\Models\Entity\Matiere;
use App\Models\Entity\Evaluation;
use App\Models\Repository\EvaluationRepository;
use App\Models\Repository\MatiereRepository;

class EvaluationController extends \App\Controllers\Evaluation\EvaluationController
{
    protected AuthService $authService;
    private EvaluationRepository $evaluationRepository;
    private MatiereRepository $matiereRepository;

    public function __construct(
        AuthService $authService,
        EvaluationService $evaluationService,
        MatiereService $matiereService,
        EvaluationRepository $evaluationRepository,
        MatiereRepository $matiereRepository
    ) {
        parent::__construct($evaluationService, $matiereService);
        $this->authService = $authService;
        $this->evaluationRepository = $evaluationRepository;
        $this->matiereRepository = $matiereRepository;
    }

    /**
     * Surcharge de la méthode de validation pour les étudiants
     */
    protected function validateMatiereAccess(int $matiereId): ?Matiere
    {
        $matiere = parent::validateMatiereAccess($matiereId);
        if (!$matiere) return null;

        $student = $this->authService->user();
        if (!$this->matiereService->isStudentEnrolled($matiere->getId(), $student->getId())) {
            $this->addFlash('error', 'Vous n\'êtes pas inscrit à cette matière');
            return null;
        }
        return $matiere;
    }

    /**
     * Surcharge de la méthode de validation pour les étudiants
     */
    protected function validateEvaluationAccess(int $evaluationId): ?Evaluation
    {
        $evaluation = parent::validateEvaluationAccess($evaluationId);
        if (!$evaluation) return null;

        $student = $this->authService->user();
        if (!$this->matiereService->isStudentEnrolled($evaluation->getMatiereId(), $student->getId())) {
            $this->addFlash('error', 'Vous n\'avez pas accès à cette évaluation');
            return null;
        }
        return $evaluation;
    }

    public function index(Request $request, Response $response): Response
    {
        try {
            $matiereId = (int) $request->get('matiere_id');
            if (!$matiereId) {
                $this->addFlash('error', 'ID de matière manquant');
                return $response->redirect('student.matieres');
            }

            $matiere = $this->validateMatiereAccess($matiereId);
            if (!$matiere) {
                return $response->redirect('student.matieres');
            }

            // Récupérer les paramètres de tri
            $sort = $request->get('sort') ?? 'date';
            $order = $request->get('order') ?? 'desc';

            // Récupérer les évaluations avec les notes de l'étudiant
            $student = $this->authService->user();
            $evaluationsObjects = $this->evaluationService->getEvaluationsWithNotes(
                $student->getId(), 
                $matiereId,
                $sort,
                $order
            );

            // Convertir les objets en tableaux pour la vue
            $evaluations = array_map(function($evaluation) {
                return [
                    'date_evaluation' => $evaluation->getDateEvaluation(),
                    'type' => $evaluation->getType(),
                    'description' => $evaluation->getDescription(),
                    'note' => $evaluation->getNote() ? $evaluation->getNote()->getNote() : null,
                    'commentaire' => $evaluation->getNote() ? $evaluation->getNote()->getCommentaire() : null
                ];
            }, $evaluationsObjects);

            return $this->render('user/dashboard/etudiant/evaluations/index', [
                'pageTitle' => 'Évaluations - ' . $matiere->getNom(),
                'evaluations' => $evaluations,
                'matiere' => $matiere,
                'currentSort' => $sort,
                'currentOrder' => $order
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return $response->redirect('student.matieres');
        }
    }

    public function indexAll(Request $request, Response $response): Response
    {
        try {
            $student = $this->authService->user();
            $studentId = $student->getId();
            
            $matieres = $this->matiereService->getMatieresForStudent($studentId);
            
            $selectedMatiereId = null;
            if (isset($request->get['matiere_id']) && !empty($request->get['matiere_id'])) {
                $selectedMatiereId = (int)$request->get['matiere_id'];
                $matiereExists = false;
                foreach ($matieres as $matiere) {
                    if ((int)$matiere['id'] === $selectedMatiereId) {
                        $matiereExists = true;
                        break;
                    }
                }
                if (!$matiereExists) {
                    $selectedMatiereId = null;
                }
            }
            
            $evaluationsObjects = $this->evaluationService->getEvaluationsForStudent($studentId, $selectedMatiereId);
            
            // Convertir les objets Evaluation en tableaux avec le format attendu par la vue
            $evaluations = array_map(function($evaluation) {
                if ($evaluation instanceof \App\Models\Entity\Evaluation) {
                    $note = $evaluation->getNote();
                    return [
                        'date_evaluation' => $evaluation->getDate() ?? $evaluation->getDateEvaluation(),
                        'type' => $evaluation->getType(),
                        'description' => $evaluation->getDescription(),
                        'note' => $note ? $note->getNote() : null,
                        'commentaire' => $note ? $note->getCommentaire() : null,
                        'matiere_id' => $evaluation->getMatiereId()  // Ajout de l'ID de la matière si nécessaire
                    ];
                }
                return $evaluation;
            }, $evaluationsObjects);

            return $this->render('user/dashboard/etudiant/evaluations/all', [
                'pageTitle' => 'Toutes mes évaluations',
                'evaluations' => $evaluations,
                'matieres' => $matieres,
                'selectedMatiereId' => $selectedMatiereId
            ]);
        } catch (\Exception $e) {
            error_log('Error in EvaluationController::indexAll: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            $this->addFlash('error', 'Une erreur est survenue lors du chargement des évaluations.');
            return $this->redirect('dashboard');
        }
    }

    public function show(Request $request, Response $response, int $id): Response
    {
        try {
            $evaluation = $this->validateEvaluationAccess($id);
            if (!$evaluation) {
                return $response->redirect('student.matieres');
            }

            $matiere = $this->matiereService->get($evaluation->getMatiereId());
            if (!$matiere) {
                $this->addFlash('error', 'Matière non trouvée');
                return $response->redirect('student.matieres');
            }

            return $this->render('user/dashboard/etudiant/evaluations/show', [
                'pageTitle' => 'Détails de l\'évaluation',
                'evaluation' => $evaluation,
                'matiere' => $matiere
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return $response->redirect('student.matieres');
        }
    }

    public function all(Request $request, Response $response): Response
    {
        try {
            $user = $this->authService->user();
            
            // S'assurer que selectedMatiereId est toujours défini, même si null
            $selectedMatiereId = null;
            $matiereId = $request->get('matiere_id');
            if ($matiereId !== null) {
                $selectedMatiereId = !empty($matiereId) ? (int)$matiereId : null;
            }
            
            $sort = $request->get('sort') ?? 'date';
            $order = $request->get('order') ?? 'desc';

            // Récupérer les évaluations avec les informations de la matière
            $evaluations = $this->evaluationRepository->findAllForStudent(
                $user->getId(),
                $selectedMatiereId,
                $sort,
                $order
            );

            // Récupérer toutes les matières de l'étudiant pour le filtre
            $matieres = $this->matiereRepository->findAllForStudent($user->getId());

            // Passer toutes les variables nécessaires à la vue
            return $this->render('user/dashboard/etudiant/evaluations/all', [
                'pageTitle' => 'Toutes mes évaluations',
                'evaluations' => $evaluations,
                'matieres' => $matieres,
                'selectedMatiereId' => $selectedMatiereId,
                'user' => $user,
                'currentSort' => $sort,
                'currentOrder' => $order
            ]);
        } catch (\Exception $e) {
            error_log("Error in all(): " . $e->getMessage());
            $this->addFlash('error', $e->getMessage());
            return $response->redirect('dashboard');
        }
    }
} 