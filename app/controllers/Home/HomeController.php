<?php

namespace App\Controllers\Home;

use Core\Controller\BaseController;
use Core\Http\Request;
use Core\Http\Response;

class HomeController extends BaseController
{
    public function initialize(): void
    {
        parent::initialize();

        // Update shared data
        $this->sharedViewData = array_merge($this->sharedViewData, [
            'currentSection' => 'home',
            'meta' => [
                'description' => 'Tableau de bord de la plateforme d\'évaluation en ligne',
                'keywords' => 'évaluation, plateforme, tableau de bord'
            ],
            'assets' => [
                'css' => ['assets/css/dashboard.css'],
                'js' => ['assets/js/dashboard.js']
            ]
        ]);
    }

    public function index(Request $request, Response $response): Response
    {
        try {
            // Get user data
            $userData = $this->getUser();

            // View data
            $viewData = [
                'pageTitle' => 'Accueil',
                'bodyClass' => 'dashboard-page',
                'user' => $userData,
                'stats' => $this->getDashboardStats(),
                'recentActivities' => $this->getRecentActivities()
            ];

            return $this->render('home/index', $viewData, 'default');
        } catch (\Throwable $e) {
            return $this->handleError($e);
        }
    }

    private function getDashboardStats(): array
    {
        return [
            'users' => 0,
            'evaluations' => 0,
            'courses' => 0
        ];
    }

    private function getRecentActivities(): array
    {
        return [];
    }
}