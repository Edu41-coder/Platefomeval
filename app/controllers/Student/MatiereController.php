<?php

namespace App\Controllers\Student;

use Core\Controller\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use App\Models\Repository\MatiereRepository;
use App\Services\MatiereService;
use Core\Database\Database;

class MatiereController extends BaseController
{
    private MatiereService $matiereService;

    public function __construct(MatiereService $matiereService)
    {
        parent::__construct();
        $this->matiereService = $matiereService;
    }

    /**
     * Affiche la liste des matières de l'étudiant
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            // Récupérer l'ID de l'étudiant connecté
            $studentId = $this->getUser()['id'];
            
            // Récupérer les matières avec les moyennes de l'étudiant
            $matieres = $this->matiereService->getMatieresForStudent($studentId);

            return $this->render('user/dashboard/etudiant/matieres/index', [
                'pageTitle' => 'Mes matières',
                'matieres' => $matieres
            ]);

        } catch (\Exception $e) {
            error_log('Error in StudentMatiereController::index: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            $this->addFlash('error', 'Une erreur est survenue lors du chargement des matières.');
            return $this->redirect('dashboard');
        }
    }

    /**
     * Affiche le détail d'une matière
     */
    public function show(Request $request, Response $response, int $id): Response
    {
        try {
            $studentId = $this->getUser()['id'];
            $matiere = $this->matiereService->getMatiereForStudent($id, $studentId);

            if (!$matiere) {
                $this->addFlash('error', 'Matière non trouvée ou accès non autorisé.');
                return $this->redirect('student.matieres');
            }

            return $this->render('user/dashboard/etudiant/matieres/show', [
                'pageTitle' => $matiere['nom'],
                'matiere' => $matiere
            ]);

        } catch (\Exception $e) {
            error_log('Error in StudentMatiereController::show: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            $this->addFlash('error', 'Une erreur est survenue lors du chargement de la matière.');
            return $this->redirect('student.matieres');
        }
    }
} 