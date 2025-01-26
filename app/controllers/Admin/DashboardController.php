<?php

namespace App\Controllers\Admin;

use Core\Controller\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use App\Services\UserService;

class DashboardController extends BaseController
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        parent::__construct();
        $this->userService = $userService;
        
        // Vérifier si l'utilisateur est admin
        if (!$this->isAuthenticated() || !$this->isAdmin()) {
            $this->redirect('dashboard');
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            // Vérifier à nouveau les permissions
            if (!$this->isAdmin()) {
                return $this->redirect('dashboard');
            }

            // Récupérer les paramètres de pagination et tri
            $page = (int) ($request->get('page', 1));
            $perPage = 10;
            $sort = $request->get('sort', 'created_at');
            // Convertir 'role' en 'role_id' pour le tri
            if ($sort === 'role') {
                $sort = 'role_id';
            }
            $order = $request->get('order', 'DESC');
            $role = $request->get('role');

            // Récupérer les statistiques
            $userRepository = new \App\Models\Repository\UserRepository();
            $stats = $userRepository->getStats();

            // Récupérer la liste des utilisateurs
            $roleId = null;
            if ($role === 'professeur') {
                $roleId = 2;
            } elseif ($role === 'etudiant') {
                $roleId = 3;
            }
            $users = $userRepository->findAllPaginated($page, $perPage, $sort, $order, $roleId);
            $totalUsers = $stats['total'];
            $totalPages = ceil($totalUsers / $perPage);

            // Convertir les objets User en tableaux
            $users = array_map(function($user) {
                return [
                    'id' => $user->getId(),
                    'nom' => $user->getNom(),
                    'prenom' => $user->getPrenom(),
                    'email' => $user->getEmail(),
                    'role' => $user->getRoleId() === 2 ? 'professeur' : 'etudiant',
                    'created_at' => $user->getCreatedAt()
                ];
            }, $users);

            return $this->render('user/dashboard/admin/index', [
                'pageTitle' => 'Dashboard Admin',
                'user' => $this->getUser(),
                'stats' => $stats,
                'users' => $users,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'itemsPerPage' => $perPage,
                'totalResults' => $totalUsers,
                'sort' => $sort,
                'order' => $order,
                'currentRole' => $role,
                'assets' => [
                    'css' => [
                        'assets/css/dashboard.css'
                    ],
                    'js' => [
                        'assets/js/dashboard.js'
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            error_log('Error in DashboardController::index: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            $this->addFlash('error', 'Erreur lors du chargement du tableau de bord');
            return $this->redirect('dashboard');
        }
    }
}