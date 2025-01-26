<?php

namespace App\Controllers\User;

use Core\Controller\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use App\Services\PhotoService;
use App\Models\Repository\PhotoRepository;
use Core\Database\Database;

class DashboardController extends BaseController
{
    private PhotoService $photoService;

    public function __construct()
    {
        parent::__construct();
        // Créer directement les dépendances
        $db = Database::getInstance();
        $photoRepository = new PhotoRepository($db);
        $this->photoService = new PhotoService($photoRepository);
    }

    public function index(Request $request, Response $response): Response
    {
        try {
            // Verify authentication
            if (!$this->isAuthenticated()) {
                $this->addFlash('error', 'Vous devez être connecté pour accéder à cette page');
                return $this->redirect('login');
            }

            // Get user data
            $userData = $this->getUser();
            if (!$userData) {
                throw new \Exception('Utilisateur non trouvé');
            }

            // Récupérer la photo de l'utilisateur
            $photo = $this->photoService->getUserPhoto($userData['id']);

            // Configuration de la réponse
            $response->setHeader('X-Frame-Options', 'DENY');
            $response->setHeader('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';");

            // Render the common dashboard view with role-specific data
            return $this->render('user/dashboard/index', [
                'pageTitle' => 'Tableau de bord',
                'user' => $userData,
                'photo' => $photo,
                'csrfToken' => $_SESSION['csrf_token'] ?? '',
                'site_title' => $_ENV['APP_NAME'] ?? 'Plateforme Eval',
                'assets' => [
                    'css' => ['css/dashboard.css'],
                    'js' => ['js/dashboard.js']
                ]
            ], 'default');
        } catch (\Throwable $e) {
            error_log('Erreur dans DashboardController::index - ' . $e->getMessage());
            return $this->handleError($e);
        }
    }
}