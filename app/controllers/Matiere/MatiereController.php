<?php
namespace App\Controllers;

use Core\Controller\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use App\Services\MatiereService;
use Core\Exception\ValidatorException;
use Core\Validator\Validator;
use App\Models\Entity\Matiere;
use App\Models\Entity\User;

class MatiereController extends BaseController
{
    private MatiereService $matiereService;
    private Validator $validator;

    public function __construct(MatiereService $matiereService)
    {
        parent::__construct();
        $this->matiereService = $matiereService;
        $this->validator = new Validator();
    }

    public function index(Request $request, Response $response): Response
    {
        $matieres = $this->matiereService->getAll();

        return $this->render('matieres/index', [
            'pageTitle' => 'Liste des Matières',
            'matieres' => $matieres
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        return $this->render('matieres/create', [
            'pageTitle' => 'Nouvelle Matière',
            'csrf_token' => $this->generateCsrfToken()
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        try {
            if (!$this->validateCsrf()) {
                throw new \Exception('Token CSRF invalide');
            }

            $data = $this->validator->validate($request->post(), [
                'nom' => ['required', 'string', 'max:100'],
                'description' => ['string', 'nullable']
            ]);

            $this->matiereService->create($data);

            $this->addFlash('success', 'Matière créée avec succès');
            return $this->redirect('/matieres');
        } catch (ValidatorException $e) {
            return $this->render('matieres/create', [
                'errors' => $e->getErrors(),
                'old' => $request->post(),
                'pageTitle' => 'Nouvelle Matière',
                'csrf_token' => $this->generateCsrfToken()
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la création : ' . $e->getMessage());
            return $this->redirect('/matieres');
        }
    }

    public function show(Request $request, Response $response, array $params): Response
    {
        try {
            $matiereId = (int) ($params['id'] ?? 0);
            $page = (int) ($request->get('page', 1));
            $sort = $request->get('sort', 'nom');
            $order = $request->get('order', 'ASC');
            $itemsPerPage = 10;

            // Vérifier si la matière existe et appartient au professeur
            $matiere = $this->matiereService->get($matiereId);
            $user = $this->getUser();
            
            if (!$matiere instanceof Matiere || !$user instanceof User || !$this->matiereService->belongsToProfessor($matiereId, $user->getId())) {
                $this->addFlash('error', 'Matière non trouvée');
                return $this->redirect('professor.matieres');
            }

            // Récupérer les étudiants avec pagination et tri
            $result = $this->matiereService->getEtudiantsByMatiereWithPagination(
                $matiereId,
                $page,
                $itemsPerPage,
                $sort,
                $order
            );

            return $this->render('user/dashboard/professor/matieres/show', [
                'matiere' => $matiere,
                'etudiants' => $result['etudiants'],
                'currentPage' => $page,
                'totalPages' => $result['totalPages'],
                'itemsPerPage' => $itemsPerPage,
                'totalResults' => $result['totalResults'],
                'sort' => $sort,
                'order' => $order
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors du chargement de la matière');
            return $this->redirect('professor.matieres');
        }
    }

    // Ajoutez d'autres méthodes pour afficher, modifier et supprimer des matières
}