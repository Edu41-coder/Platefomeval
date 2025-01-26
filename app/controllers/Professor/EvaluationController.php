<?php

namespace App\Controllers\Professor;

use Core\Http\Request;
use Core\Http\Response;
use Core\Http\JsonResponse;
use App\Services\AuthService;
use App\Services\EvaluationService;
use App\Services\MatiereService;
use Core\Exception\ValidatorException;
use App\Security\CsrfToken;
use App\Models\Entity\Matiere;
use App\Models\Entity\Evaluation;
use App\Models\Entity\User;
use Core\Controller\BaseController;
use Core\Exception\ServiceException;
use App\Services\UserService;

class EvaluationController extends BaseController
{
    private $evaluationService;
    private $matiereService;
    private $authService;
    private $userService;

    public function __construct(
        EvaluationService $evaluationService, 
        MatiereService $matiereService,
        AuthService $authService,
        UserService $userService
    ) {
        parent::__construct();
        $this->evaluationService = $evaluationService;
        $this->matiereService = $matiereService;
        $this->authService = $authService;
        $this->userService = $userService;
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

    /**
     * Valide l'accès à une évaluation pour un professeur
     */
    protected function validateEvaluationAccess(int $evaluationId): ?Evaluation
    {
        $evaluation = $this->evaluationService->getById($evaluationId);
        if (!$evaluation) {
            $this->addFlash('error', 'Évaluation non trouvée');
            return null;
        }

        $professor = $this->authService->user();
        if ($evaluation->getProfessorId() !== $professor->getId()) {
            $this->addFlash('error', 'Cette évaluation ne vous appartient pas');
            return null;
        }
        return $evaluation;
    }

    /**
     * Prépare les données communes pour les vues
     */
    protected function prepareViewData(array $data = []): array
    {
        $commonData = [
            'pageTitle' => $data['pageTitle'] ?? 'Évaluations',
            'user' => $this->authService->user(),
            'csrfToken' => CsrfToken::getToken()
        ];

        return array_merge($commonData, $data);
    }

    public function index(Request $request, Response $response): Response
    {
        try {
            // Récupérer l'ID depuis l'URL
            $url = $request->getUri();
            preg_match('/\/Plateformeval\/public\/professor\/matieres\/(\d+)\/evaluations/', $url, $matches);
            
            $matiereId = isset($matches[1]) ? (int)$matches[1] : 0;
            
            // Récupérer les paramètres de tri
            $page = (int) ($request->get('page', 1));
            $sort = $request->get('sort') ?? 'date_evaluation';
            $order = strtoupper($request->get('order') ?? 'DESC');
            $itemsPerPage = 10;

            // Vérifier si la matière existe et appartient au professeur
            $matiere = $this->matiereService->get($matiereId);
            if (!$matiere instanceof Matiere) {
                $this->addFlash('error', 'Matière non trouvée');
                return $this->redirect('professor.matieres');
            }
            
            $user = $this->authService->user();
            
            if (!$user) {
                $this->addFlash('error', 'Utilisateur non trouvé');
                return $this->redirect('professor.matieres');
            }
            
            if (!$this->matiereService->belongsToProfessor($matiereId, $user->getId())) {
                $this->addFlash('error', 'Cette matière ne vous appartient pas');
                return $this->redirect('professor.matieres');
            }

            // Récupérer les évaluations avec pagination et tri
            $result = $this->evaluationService->getEvaluationsByMatiereWithPagination(
                $matiereId,
                $page,
                $itemsPerPage,
                $sort,
                $order
            );

            $viewData = [
                'matiere' => $matiere,
                'evaluations' => $result['evaluations'],
                'currentPage' => $page,
                'totalPages' => $result['totalPages'],
                'itemsPerPage' => $itemsPerPage,
                'totalResults' => $result['totalResults'],
                'sort' => $sort,
                'order' => $order
            ];

            return $this->render('user/dashboard/professor/evaluations/index', 
                $this->prepareViewData($viewData)
            );
        } catch (\Exception $e) {
            error_log("Exception dans EvaluationController::index - " . $e->getMessage());
            $this->addFlash('error', 'Une erreur est survenue lors du chargement des évaluations');
            return $this->redirect('professor.matieres');
        }
    }

    public function create(Request $request, Response $response): Response
    {
        try {
            // Récupérer l'ID de la matière depuis l'URL
            $url = $request->getUri();
            preg_match('/\/Plateformeval\/public\/professor\/matieres\/(\d+)/', $url, $matches);
            $matiereId = isset($matches[1]) ? (int)$matches[1] : 0;
            
            // Vérifier si la matière existe et appartient au professeur
            $matiere = $this->validateMatiereAccess($matiereId);
            if (!$matiere) {
                return $response->redirect('/Plateformeval/public/professor/matieres');
            }

            $etudiantId = $request->get('etudiant_id');
            
            // Récupérer tous les étudiants sans pagination
            if ($etudiantId) {
                // Si un étudiant spécifique est demandé
                $allEtudiants = $this->matiereService->getEtudiantsByMatiere($matiereId);
                $filteredEtudiants = array_filter($allEtudiants, function($etudiant) use ($etudiantId) {
                    return $etudiant->getId() == $etudiantId;
                });
                
                if (empty($filteredEtudiants)) {
                    $this->addFlash('error', 'Étudiant non trouvé dans cette matière');
                    return $response->redirect('/Plateformeval/public/professor/matieres/' . $matiereId . '/evaluations');
                }

                $etudiants = array_values($filteredEtudiants);
            } else {
                // Récupérer tous les étudiants sans pagination
                $etudiants = $this->matiereService->getEtudiantsByMatiere($matiereId);
            }

            return $this->render('user/dashboard/professor/evaluations/create', [
                'pageTitle' => 'Nouvelle évaluation - ' . $matiere->getNom(),
                'matiere' => $matiere,
                'etudiants' => $etudiants,
                'etudiantId' => $etudiantId,
                'sort' => $request->get('sort', 'nom'),
                'order' => strtoupper($request->get('order', 'ASC')),
                'csrfToken' => CsrfToken::getToken()
            ]);

        } catch (\Exception $e) {
            error_log("Erreur dans create: " . $e->getMessage());
            $this->addFlash('error', 'Une erreur est survenue lors du chargement du formulaire');
            return $response->redirect('/Plateformeval/public/professor/matieres');
        }
    }

    /**
     * Crée une nouvelle évaluation
     */
    public function store(Request $request, Response $response): Response
    {
        try {
            $data = $request->post();
            
            // Récupérer matiere_id depuis l'URL
            $url = $request->getUri();
            preg_match('/\/matieres\/(\d+)\/evaluations/', $url, $matches);
            $matiereId = isset($matches[1]) ? (int)$matches[1] : 0;
            
            // Vérifier l'accès à la matière
            $matiere = $this->validateMatiereAccess($matiereId);
            if (!$matiere) {
                return $response->redirect($this->generateUrl('professor.matieres'));
            }

            // Ajouter l'ID du professeur connecté
            $data['prof_id'] = $this->authService->user()->getId();
            $data['matiere_id'] = $matiereId;

            try {
                // Valider les données
                $validatedData = $this->evaluationService->validateEvaluationData($data);
                
                // Créer l'évaluation avec les données validées
                $evaluationId = $this->evaluationService->create($validatedData);
                
                if ($evaluationId) {
                    $this->addFlash('success', 'L\'évaluation a été créée avec succès');
                    return $response->redirect("/Plateformeval/public/professor/matieres/{$matiereId}/evaluations");
                }
            } catch (ValidatorException $e) {
                $errors = $e->getErrors();
                
                // Récupérer les données pour réafficher le formulaire
                $etudiantId = $data['etudiant_id'] ?? null;
                $result = $this->prepareCreateFormData($matiereId, $etudiantId);

                // Réafficher le formulaire avec les erreurs
                return $this->render('user/dashboard/professor/evaluations/create', array_merge([
                    'pageTitle' => 'Nouvelle évaluation - ' . $this->matiereService->get($matiereId)->getNom(),
                    'matiere' => $this->matiereService->get($matiereId),
                    'etudiantId' => $etudiantId,
                    'formData' => $data,
                    'errors' => $errors,
                    'csrfToken' => CsrfToken::getToken(),
                    'sort' => $request->get('sort', 'nom'),
                    'order' => strtoupper($request->get('order', 'ASC'))
                ], $result));
            }

            // En cas d'échec de création
            $this->addFlash('error', 'Une erreur est survenue lors de la création de l\'évaluation');
            return $response->redirect($this->generateUrl('professor.matieres.evaluations.create', [
                'matiere_id' => $matiereId
            ]));

        } catch (\Exception $e) {
            error_log("Erreur dans EvaluationController::store - " . $e->getMessage());
            $this->addFlash('error', 'Une erreur est survenue lors de la création de l\'évaluation');
            return $response->redirect($this->generateUrl('professor.matieres.evaluations.create', [
                'matiere_id' => $matiereId
            ]));
        }
    }

    /**
     * Prépare les données pour le formulaire de création
     */
    private function prepareCreateFormData(int $matiereId, ?int $etudiantId): array
    {
        $page = 1;
        $itemsPerPage = 10;
        $sort = 'nom';
        $order = 'ASC';

        if ($etudiantId) {
            $allEtudiants = $this->matiereService->getEtudiantsByMatiere($matiereId);
            $filteredEtudiants = array_filter($allEtudiants, function($etudiant) use ($etudiantId) {
                return $etudiant->getId() == $etudiantId;
            });
            
            return [
                'etudiants' => array_values($filteredEtudiants),
                'totalPages' => 1,
                'currentPage' => 1,
                'itemsPerPage' => 1,
                'totalResults' => 1,
                'sort' => $sort,
                'order' => $order
            ];
        }

        return $this->matiereService->getEtudiantsByMatiereWithPagination(
            $matiereId,
            $page,
            $itemsPerPage,
            $sort,
            $order
        );
    }

    /**
     * Affiche le formulaire d'édition
     */
    public function edit(Request $request, Response $response): Response
    {
        try {
            $evaluationId = (int)$request->get('id');
            $evaluation = $this->evaluationService->getById($evaluationId);
            
            if (!$evaluation) {
                throw new \Exception('Évaluation non trouvée');
            }

            // Vérifier que l'évaluation appartient au professeur
            $professor = $this->authService->user();
            if ($evaluation->getProfessorId() !== $professor->getId()) {
                throw new \Exception('Vous n\'êtes pas autorisé à modifier cette évaluation');
            }

            return $this->render('user/dashboard/professor/evaluations/edit', [
                'pageTitle' => 'Modifier l\'évaluation',
                'evaluation' => $evaluation,
                'matiere' => $this->matiereService->get($evaluation->getMatiereId()),
                'csrfToken' => CsrfToken::getToken()
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return $response->redirect('/Plateformeval/public/professor/matieres');
        }
    }

    /**
     * Met à jour une évaluation existante
     */
    public function update(Request $request, Response $response): Response
    {
        try {
            // Vérifier le token CSRF
            if (!CsrfToken::verify($request->post('csrf_token'))) {
                throw new \Exception('Token CSRF invalide');
            }

            $evaluationId = (int)$request->post('id');
            $matiereId = (int)$request->get['matiere_id'];

            // Valider et mettre à jour l'évaluation
            $data = [
                'type' => $request->post('type'),
                'description' => $request->post('description'),
                'date_evaluation' => $request->post('date_evaluation')
            ];

            $this->evaluationService->update($evaluationId, $data);

            $this->addFlash('success', 'Évaluation mise à jour avec succès');
            
            // Redirection directe vers la liste des évaluations
            return $response->redirect("/Plateformeval/public/professor/matieres/{$matiereId}/evaluations");

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
            return $response->redirect($this->generateUrl('professor.matieres.evaluations.edit', [
                'matiere_id' => $matiereId,
                'id' => $evaluationId
            ]));
        }
    }

    public function delete(Request $request, Response $response): Response
    {
        try {
            $token = $request->post('csrf_token') ?? $request->get('csrf_token') ?? null;
            $rawId = $request->post('evaluation_id') ?? $request->get('evaluation_id', null);
            
            if ($rawId === null) {
                throw new \Exception('ID évaluation manquant');
            }
            
            $evaluationId = (int)$rawId;

            if (!$token || !CsrfToken::verify($token)) {
                throw new \Exception('Token CSRF invalide');
            }

            if (!$evaluationId) {
                throw new \Exception('ID évaluation invalide');
            }

            $evaluation = $this->validateEvaluationAccess($evaluationId);
            if (!$evaluation) {
                throw new \Exception('Cette évaluation n\'existe pas ou ne vous appartient pas');
            }

            $matiere_id = $evaluation->getMatiereId();
            $this->evaluationService->delete($evaluationId);
            
            $this->addFlash('success', 'Évaluation supprimée avec succès');
            return $response->redirect('/Plateformeval/public/professor/matieres/' . $matiere_id . '/evaluations');

        } catch (\Exception $e) {
            error_log('Erreur lors de la suppression: ' . $e->getMessage());
            $this->addFlash('error', $e->getMessage());
            
            if (isset($matiere_id)) {
                return $response->redirect('/Plateformeval/public/professor/matieres/' . $matiere_id . '/evaluations');
            }
            return $response->redirect('/Plateformeval/public/professor/matieres');
        }
    }

    public function notes(Request $request, Response $response): Response
    {
        $professor = $this->authService->user();
        $evaluation = $this->evaluationService->getById($request->get('id'));

        if (!$evaluation || $evaluation->getProfessorId() !== $professor->getId()) {
            $this->addFlash('error', 'Cette évaluation n\'existe pas ou ne vous appartient pas');
            return $this->redirect('professor.matieres');
        }

        $matiere = $this->matiereService->get($evaluation->getMatiereId());
        $etudiants = $this->matiereService->getEtudiantsByMatiere($matiere->getId());
        $notes = $this->evaluationService->getNotesForEvaluation($evaluation->getId());

        return $this->render('user/dashboard/professor/evaluations/notes', [
            'pageTitle' => 'Notes - ' . $matiere->getNom(),
            'evaluation' => $evaluation,
            'matiere' => $matiere,
            'etudiants' => $etudiants,
            'notes' => $notes
        ]);
    }

    public function show(Request $request, Response $response, int $id): Response
    {
        $evaluation = $this->validateEvaluationAccess($id);
        if (!$evaluation) {
            return $this->redirect('professor.matieres');
        }

        return $this->render('user/dashboard/professor/evaluations/show', [
            'pageTitle' => 'Détails de l\'évaluation',
            'evaluation' => $evaluation,
            'matiere' => $this->matiereService->get($evaluation->getMatiereId())
        ]);
    }

    /**
     * Affiche le formulaire de gestion des notes
     */
    public function showNotes(Request $request, Response $response): Response
    {
        try {
            $evaluationId = (int)$request->get('id');
            $matiereId = (int)$request->get('matiere_id');

            // Récupérer les données nécessaires via le service
            $data = $this->evaluationService->getNotesFormData($evaluationId, $matiereId);

            // Récupérer la liste des étudiants de la matière
            $etudiants = $this->matiereService->getEtudiantsByMatiere($matiereId);

            return $this->render('user/dashboard/professor/evaluations/gestion_notes', 
                $this->prepareViewData([
                    'pageTitle' => 'Gestion des notes',
                    'evaluation' => $data['evaluation'],
                    'matiere' => $data['matiere'],
                    'evaluationNotes' => $data['notes'],
                    'etudiants' => $etudiants,
                    'errors' => []
                ])
            );
        } catch (\Exception $e) {
            error_log("Erreur dans EvaluationController::showNotes - " . $e->getMessage());
            $this->addFlash('error', 'Une erreur est survenue lors du chargement des notes');
            return $response->redirect($this->generateUrl('professor.matieres.evaluations.index', [
                'matiere_id' => $matiereId
            ]));
        }
    }

    /**
     * Gère la mise à jour des notes d'une évaluation
     */
    public function updateNotes(Request $request, Response $response): Response
    {
        try {
            // Vérifier le token CSRF
            if (!CsrfToken::verify($request->post('csrf_token'))) {
                throw new \Exception('Token CSRF invalide');
            }

            $evaluationId = (int)$request->get['id'];
            $matiereId = (int)$request->get['matiere_id'];
            $notes = $request->post('notes', []);
            $commentaires = $request->post('commentaires', []);

            // Mettre à jour les notes
            $this->evaluationService->updateNotes($evaluationId, $notes, $commentaires);

            $this->addFlash('success', 'Notes mises à jour avec succès');
            
            // Redirection directe avec l'URL complète
            return $response->redirect("/Plateformeval/public/professor/matieres/{$matiereId}/evaluations");

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la mise à jour des notes : ' . $e->getMessage());
            return $response->redirect($this->generateUrl('professor.matieres.evaluations.notes', [
                'matiere_id' => $matiereId,
                'id' => $evaluationId
            ]));
        }
    }

    /**
     * Affiche les évaluations d'un étudiant pour une matière
     */
    public function showStudentEvaluations(Request $request, Response $response, int $matiereId, int $studentId): Response
    {
        try {
            // Récupérer les paramètres de tri et pagination
            $sort = $request->get['sort'] ?? 'date_evaluation';
            $order = strtoupper($request->get['order'] ?? 'DESC');
            $page = (int)($request->get['page'] ?? 1);
            $itemsPerPage = 10;

            // Valider les colonnes de tri autorisées
            $allowedSortColumns = ['date_evaluation', 'type', 'note'];
            if (!in_array($sort, $allowedSortColumns)) {
                $sort = 'date_evaluation';
            }

            // Récupérer la matière et l'étudiant
            $matiere = $this->matiereService->get($matiereId);
            if (!$matiere) {
                throw new \Exception('Matière non trouvée');
            }

            $student = $this->userService->get($studentId);
            if (!$student) {
                throw new \Exception('Étudiant non trouvé');
            }

            // Récupérer toutes les évaluations
            $evaluations = $this->evaluationService->getEvaluationsWithNotes($studentId, $matiereId);
            
            // Trier les évaluations
            usort($evaluations, function($a, $b) use ($sort, $order) {
                $valueA = $this->getSortValue($a, $sort);
                $valueB = $this->getSortValue($b, $sort);
                return $order === 'ASC' ? $valueA <=> $valueB : $valueB <=> $valueA;
            });

            // Calculer la pagination
            $totalResults = count($evaluations);
            $totalPages = ceil($totalResults / $itemsPerPage);
            $currentPage = max(1, min($page, $totalPages));
            $offset = ($currentPage - 1) * $itemsPerPage;
            
            // Découper les résultats pour la page courante
            $evaluations = array_slice($evaluations, $offset, $itemsPerPage);
            
            // Calculer la moyenne
            $moyenne = $this->evaluationService->calculateStudentAverage($studentId, $matiereId) ?? 0;

            return $this->render('user/dashboard/professor/evaluations/student', [
                'pageTitle' => "Évaluations de {$student->getPrenom()} {$student->getNom()}",
                'matiere' => $matiere,
                'student' => $student,
                'evaluations' => $evaluations,
                'moyenne' => $moyenne,
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
            $this->addFlash('error', $e->getMessage());
            return $this->redirect("/professor/matieres/{$matiereId}");
        }
    }

    private function getSortValue($evaluation, string $sort)
    {
        switch ($sort) {
            case 'date_evaluation':
                return strtotime($evaluation->getDateEvaluation());
            case 'type':
                return $evaluation->getType();
            case 'note':
                return $evaluation->getNote() ? $evaluation->getNote()->getNote() : 0;
            default:
                return 0;
        }
    }

    public function details(Request $request, Response $response): Response
    {
        try {
            $evaluationId = (int)$request->get['id'];
            $matiereId = (int)$request->get['matiere_id'];

            // Récupérer les données nécessaires via le service
            $data = $this->evaluationService->getNotesFormData($evaluationId, $matiereId);

            // Récupérer la liste des étudiants de la matière
            $etudiants = $this->matiereService->getEtudiantsByMatiere($matiereId);

            return $this->render('user/dashboard/professor/evaluations/detail_evaluation', 
                $this->prepareViewData([
                    'pageTitle' => 'Détails de l\'évaluation',
                    'evaluation' => $data['evaluation'],
                    'matiere' => $data['matiere'],
                    'evaluationNotes' => $data['notes'],
                    'etudiants' => $etudiants
                ])
            );
        } catch (\Exception $e) {
            error_log("Erreur dans EvaluationController::details - " . $e->getMessage());
            $this->addFlash('error', 'Une erreur est survenue lors du chargement des détails');
            return $response->redirect($this->generateUrl('professor.matieres.evaluations.index', [
                'matiere_id' => $matiereId
            ]));
        }
    }
}
