<?php

namespace App\Controllers\Professor;

use Core\Controller\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use App\Services\MatiereService;
use App\Services\AuthService;
use App\Models\Entity\Matiere;
use App\Services\EvaluationService;

class MatiereController extends BaseController
{
    protected MatiereService $matiereService;
    protected AuthService $authService;
    protected EvaluationService $evaluationService;

    private const ITEMS_PER_PAGE = 30;
    private const ALLOWED_SORT_COLUMNS = [
        'nom' => 'u.nom',
        'prenom' => 'u.prenom',
        'email' => 'u.email',
        'moyenne' => 'moyenne',
        'derniere_eval' => 'derniere_eval'
    ];

    public function __construct(
        MatiereService $matiereService,
        EvaluationService $evaluationService
    ) {
        parent::__construct();
        $this->matiereService = $matiereService;
        $this->authService = AuthService::getInstance();
        $this->evaluationService = $evaluationService;
    }

    /**
     * Valide l'accès à une matière pour un professeur
     */
    protected function validateMatiereAccess(int $matiereId): ?Matiere
    {
        $matiere = $this->matiereService->get($matiereId);
        if (!$matiere) {
            $this->addFlash('error', 'Matière non trouvée');
            return null;
        }

        $professor = $this->authService->user();
        if (!$this->matiereService->belongsToProfessor($matiere->getId(), $professor->getId())) {
            $this->addFlash('error', 'Cette matière ne vous appartient pas');
            return null;
        }
        return $matiere;
    }

    public function index(Request $request, Response $response): Response
    {
        $professor = $this->authService->user();
        $matieres = $this->matiereService->getMatieresByProfessor($professor->getId());

        return $this->render('user/dashboard/professor/matieres/index', [
            'pageTitle' => 'Mes matières',
            'matieres' => $matieres
        ]);
    }

    /**
     * Affiche le détail d'une matière
     */
    public function show(Request $request, Response $response, int $id): Response
    {
        try {
            // Récupérer les paramètres de tri et pagination depuis la requête
            $sort = $request->get['sort'] ?? 'nom';
            $order = $request->get['order'] ?? 'ASC';
            $page = (int)($request->get['page'] ?? 1);

            // Validation de l'accès
            $matiere = $this->validateMatiereAccess($id);
            if (!$matiere) {
                throw new \Exception('Cette matière ne vous appartient pas');
            }

            // Récupérer les étudiants
            $etudiants = $this->matiereService->getEtudiantsByMatiere($id);
            $etudiantsData = [];
            
            foreach ($etudiants as $etudiant) {
                $moyenne = $this->evaluationService->calculateStudentAverage($etudiant->getId(), $id);
                $derniereEval = $this->evaluationService->getLastEvaluation($etudiant->getId(), $id);
                
                $etudiantsData[] = [
                    'etudiant' => $etudiant,
                    'moyenne' => $moyenne !== null ? $moyenne : '-',
                    'derniere_eval' => $derniereEval ? date('d/m/Y', strtotime($derniereEval['date'])) : '-'
                ];
            }

            // Tri personnalisé
            usort($etudiantsData, function($a, $b) use ($sort, $order) {
                switch ($sort) {
                    case 'moyenne':
                        $valueA = $a['moyenne'] === '-' ? 0 : (float)$a['moyenne'];
                        $valueB = $b['moyenne'] === '-' ? 0 : (float)$b['moyenne'];
                        break;
                    case 'derniere_eval':
                        $valueA = $a['derniere_eval'] === '-' ? 0 : strtotime($a['derniere_eval']);
                        $valueB = $b['derniere_eval'] === '-' ? 0 : strtotime($b['derniere_eval']);
                        break;
                    default:
                        $valueA = $a['etudiant']->{'get' . ucfirst($sort)}();
                        $valueB = $b['etudiant']->{'get' . ucfirst($sort)}();
                }

                if ($order === 'ASC') {
                    return $valueA <=> $valueB;
                }
                return $valueB <=> $valueA;
            });

            // Pagination
            $itemsPerPage = self::ITEMS_PER_PAGE;
            $totalResults = count($etudiantsData);
            $totalPages = ceil($totalResults / $itemsPerPage);
            $currentPage = max(1, min($page, $totalPages));
            $offset = ($currentPage - 1) * $itemsPerPage;
            
            // Slice the data for the current page
            $etudiantsData = array_slice($etudiantsData, $offset, $itemsPerPage);

            return $this->render('user/dashboard/professor/matieres/show', [
                'pageTitle' => $matiere->getNom(),
                'matiere' => $matiere,
                'etudiantsData' => $etudiantsData,
                'sort' => $sort,
                'order' => $order,
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
                'itemsPerPage' => $itemsPerPage,
                'totalResults' => $totalResults,
                'paginationParams' => [
                    'sort' => $sort,
                    'order' => $order
                ]
            ]);

        } catch (\Exception $e) {
            // Log seulement le message d'erreur sans les données sensibles
            error_log("MatiereController::show - Erreur: " . $e->getMessage());
            $this->addFlash('error', $e->getMessage());
            return $this->redirect('professor.matieres');
        }
    }
} 