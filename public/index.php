<?php

// Configuration de base
define('BASE_PATH', '/Plateformeval');
define('PUBLIC_PATH', BASE_PATH . '/public');

// Chargement des dépendances
require_once $_SERVER['DOCUMENT_ROOT'] . '/Plateformeval/bootstrap.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Plateformeval/vendor/autoload.php';

// Autoloader personnalisé
spl_autoload_register(function ($class) {
    // Convert namespace separators to directory separators
    $file = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

    // Define possible base paths
    $basePaths = [
        __DIR__ . '/../',
        $_SERVER['DOCUMENT_ROOT'] . '/Plateformeval/'
    ];

    // Try each base path
    foreach ($basePaths as $basePath) {
        $fullPath = $basePath . $file;
        if (file_exists($fullPath)) {
            require_once $fullPath;
            return true;
        }
    }

    return false;
});

use Core\Http\Request;
use Core\Http\Response;
use Core\Http\JsonResponse;
use App\Models\Repository\UserRepository;
use App\Services\AuthService;
use App\Controllers\Profile\ProfileController;
use App\Controllers\Auth\AuthController;
use App\Controllers\Legal\LegalController;
use App\Controllers\Contact\ContactController;
use App\Controllers\User\DashboardController;
use App\Controllers\Evaluation\EvaluationController;
use Core\Router\Router;
use Core\Router\RouterException;
use Core\Database\Database;
use Core\Middleware\MiddlewareException;
use App\Middleware\CorsMiddleware;
use App\Middleware\AuthMiddleware;
use App\Controllers\Professor\EvaluationController as ProfessorEvaluationController;
use App\Models\Repository\EvaluationRepository;
use App\Models\Repository\MatiereRepository;
use App\Services\EvaluationService;
use App\Services\MatiereService;
use App\Controllers\Professor\MatiereController;

// Configuration des sessions
AuthService::ensureSession();

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Initialisation de la base de données
    $db = Database::getInstance();

    // Nettoyage de l'URL
    $url = $_SERVER['REQUEST_URI'];
    $baseDirs = [
        '/Plateformeval/public',
        '/Plateformeval'
    ];

    // Séparer l'URL de base des paramètres de requête
    $urlParts = explode('?', $url);
    $baseUrl = $urlParts[0];
    $queryString = isset($urlParts[1]) ? '?' . $urlParts[1] : '';

    // Nettoyage de l'URL en fonction des chemins de base possibles
    foreach ($baseDirs as $baseDir) {
        if (strpos($baseUrl, $baseDir) === 0) {
            $baseUrl = substr($baseUrl, strlen($baseDir));
            break;
        }
    }

    // Nettoyage final de l'URL
    $baseUrl = $baseUrl ?: '/';
    $baseUrl = trim(preg_replace('#/+#', '/', $baseUrl), '/');
    if (empty($baseUrl)) {
        $baseUrl = '/';
    }

    // Reconstruire l'URL complète pour le routeur
    $url = $baseUrl;

    // Initialisation du router
    $router = new Router($url);

    // Ajout des middlewares globaux
    $router->addGlobalMiddleware(new CorsMiddleware());

    // Routes publiques
    $router->get('/', 'App\Controllers\Home\HomeController@index', 'home');

    // Routes légales et contact
    $router->get('/mentions-legales', 'App\Controllers\Legal\LegalController@mentions', 'mentions-legales');
    $router->get('/confidentialite', 'App\Controllers\Legal\LegalController@privacy', 'confidentialite');
    $router->get('/faq', 'App\Controllers\Legal\LegalController@faq', 'faq');
    $router->get('/contact', 'App\Controllers\Contact\ContactController@index', 'contact');
    $router->post('/contact/send', 'App\Controllers\Contact\ContactController@send', 'contact.send');

    // Routes d'authentification
    $router->get('/login', 'App\Controllers\Auth\AuthController@loginForm', 'login');
    $router->post('/login', 'App\Controllers\Auth\AuthController@login', 'login.post');
    $router->get('/register', 'App\Controllers\Auth\AuthController@registerForm', 'register');
    $router->post('/register', 'App\Controllers\Auth\AuthController@handleRegister', 'register.post');
    $router->post('/logout', 'App\Controllers\Auth\AuthController@logout', 'logout')
        ->middleware(AuthMiddleware::auth());
    $router->get('/mot-de-passe-oublie', 'App\Controllers\Auth\AuthController@forgotPasswordForm', 'mot-de-passe-oublie');
    $router->post('/mot-de-passe-oublie/send', 'App\Controllers\Auth\AuthController@sendResetLink', 'mot-de-passe-oublie.send');

    // Routes du profil
    $router->get('/profile', function(Request $request, Response $response) {
        $controller = new ProfileController();
        return $controller->show($request, $response);
    }, 'profile')
    ->middleware(AuthMiddleware::auth());

    // Ajouter ces routes pour la gestion des photos
    $router->post('/profile/photo', function(Request $request, Response $response) {
        $controller = new ProfileController();
        return $controller->uploadPhoto($request, $response);
    }, 'profile.photo.upload')
    ->middleware(AuthMiddleware::auth());

    $router->post('/profile/photo/delete', function(Request $request, Response $response) {
        $controller = new ProfileController();
        return $controller->deletePhoto($request, $response);
    }, 'profile.photo.delete')
    ->middleware(AuthMiddleware::auth());

    // Route pour voir le profil d'un autre utilisateur
    $router->get('/profile/view', function(Request $request, Response $response) {
        $controller = new ProfileController();
        return $controller->view($request, $response);
    }, 'profile.view')
    ->middleware(AuthMiddleware::auth());

    $router->post('/profile/update', function(Request $request, Response $response) {
        $controller = new ProfileController();
        return $controller->update($request, $response);
    }, 'profile.update')
    ->middleware(AuthMiddleware::auth());

    $router->get('/profile/edit', function(Request $request, Response $response) {
        $controller = new ProfileController();
        return $controller->edit($request, $response);
    }, 'profile.edit')
    ->middleware(AuthMiddleware::auth());

    $router->post('/profile/password', function(Request $request, Response $response) {
        $controller = new ProfileController();
        return $controller->updatePassword($request, $response);
    }, 'profile.password.update')
    ->middleware(AuthMiddleware::auth());

    // Routes des tableaux de bord
    $router->get('/dashboard', 'App\Controllers\User\DashboardController@index', 'dashboard')
        ->middleware(AuthMiddleware::auth());
    $router->get('/professor/dashboard', 'App\Controllers\User\DashboardController@index', 'professor.dashboard')
        ->middleware(AuthMiddleware::professeur());

    // Routes des évaluations
    $router->get('/evaluations', 'App\Controllers\Evaluation\EvaluationController@index', 'evaluations.index')
        ->middleware(AuthMiddleware::auth());
    $router->get('/evaluations/create', 'App\Controllers\Evaluation\EvaluationController@create', 'evaluations.create')
        ->middleware(AuthMiddleware::auth());
    $router->post('/evaluations', 'App\Controllers\Evaluation\EvaluationController@store')
        ->middleware(AuthMiddleware::auth());
    $router->get('/evaluations/:id', 'App\Controllers\Evaluation\EvaluationController@show', 'evaluations.show')
        ->middleware(AuthMiddleware::auth())
        ->with('id', '[0-9]+');

    // Helper function pour créer le DashboardController admin
    function createAdminDashboardController(): \App\Controllers\Admin\DashboardController {
        $userRepository = new \App\Models\Repository\UserRepository();
        $userService = new \App\Services\UserService($userRepository);
        return new \App\Controllers\Admin\DashboardController($userService);
    }

    // Routes admin
    $router->get('/admin', function(Request $request, Response $response) {
        return createAdminDashboardController()->index($request, $response);
    }, 'admin.dashboard')
        ->middleware(AuthMiddleware::admin());

    $router->get('/admin/dashboard', function(Request $request, Response $response) {
        return createAdminDashboardController()->index($request, $response);
    }, 'admin.dashboard')
        ->middleware(AuthMiddleware::admin());

    $router->get('/admin/users', 'App\Controllers\Admin\UserController@index', 'admin.users')
        ->middleware(AuthMiddleware::admin());

    $router->post('/admin/users/:id/delete', 'App\Controllers\User\UserController@destroy', 'admin.users.delete')
        ->middleware(AuthMiddleware::admin())
        ->with('id', '[0-9]+');

    $router->get('/admin/evaluations', function(Request $request, Response $response) {
        return createProfessorEvaluationController()->index($request, $response);
    }, 'admin.evaluations')
        ->middleware(AuthMiddleware::admin());

    // Routes du tableau de bord professeur
    $router->group('/professor', function(Router $router) {
        // Routes des matières
        $router->get('/matieres', function(Request $request, Response $response) {
            return createMatiereController()->index($request, $response);
        }, 'professor.matieres')
            ->middleware(AuthMiddleware::professeur());
        
        $router->get('/matieres/:id', function(Request $request, Response $response, $id) {
            $request->get['id'] = (int)$id;
            return createMatiereController()->show($request, $response, (int)$id);
        }, 'professor.matieres.show')
            ->middleware(AuthMiddleware::professeur())
            ->with('id', '[0-9]+');

        // Routes des évaluations
        $router->get('/matieres/:matiere_id/evaluations/create', function(Request $request, Response $response, $matiere_id) {
            $request->get['matiere_id'] = (int)$matiere_id;
            return createProfessorEvaluationController()->create($request, $response);
        }, 'professor.matieres.evaluations.create')
            ->middleware(AuthMiddleware::professeur())
            ->with('matiere_id', '[0-9]+');

        $router->get('/matieres/:matiere_id/evaluations', function(Request $request, Response $response, $matiere_id) {
            $request->get['matiere_id'] = (int)$matiere_id;
            return createProfessorEvaluationController()->index($request, $response);
        }, 'professor.matieres.evaluations.index')
            ->middleware(AuthMiddleware::professeur())
            ->with('matiere_id', '[0-9]+');

        $router->post('/matieres/:matiere_id/evaluations', function(Request $request, Response $response, $matiere_id) {
            $request->get['matiere_id'] = (int)$matiere_id;
            return createProfessorEvaluationController()->store($request, $response);
        }, 'professor.matieres.evaluations.store')
            ->middleware(AuthMiddleware::professeur())
            ->with('matiere_id', '[0-9]+');

        $router->get('/matieres/:matiere_id/evaluations/edit', function(Request $request, Response $response, $matiere_id) {
            $request->get['matiere_id'] = (int)$matiere_id;
            return createProfessorEvaluationController()->edit($request, $response);
        }, 'professor.matieres.evaluations.edit')
            ->middleware(AuthMiddleware::professeur())
            ->with('matiere_id', '[0-9]+');

        $router->post('/matieres/:matiere_id/evaluations/update', function(Request $request, Response $response, $matiere_id) {
            $request->get['matiere_id'] = (int)$matiere_id;
            return createProfessorEvaluationController()->update($request, $response);
        }, 'professor.matieres.evaluations.update')
            ->middleware(AuthMiddleware::professeur())
            ->with('matiere_id', '[0-9]+');

        $router->post('/evaluations/:id', function(Request $request, Response $response, $id) {
            $request->get['id'] = (int)$id;
            return createProfessorEvaluationController()->update($request, $response);
        }, 'professor.evaluations.update')
            ->middleware(AuthMiddleware::professeur())
            ->with('id', '[0-9]+');

        $router->post('/matieres/:matiere_id/evaluations/delete', function(Request $request, Response $response, $matiere_id) {
            $request->get['matiere_id'] = (int)$matiere_id;
            return createProfessorEvaluationController()->delete($request, $response);
        }, 'professor.matieres.evaluations.delete')
            ->middleware(AuthMiddleware::professeur())
            ->with('matiere_id', '[0-9]+');

        $router->get('/evaluations/:id/notes', function(Request $request, Response $response, $id) {
            $request->get['id'] = (int)$id;
            return createProfessorEvaluationController()->notes($request, $response);
        }, 'professor.evaluations.notes')
            ->middleware(AuthMiddleware::professeur())
            ->with('id', '[0-9]+');

        // Route pour afficher le formulaire de gestion des notes (comme edit)
        $router->get('/matieres/:matiere_id/evaluations/notes', function(Request $request, Response $response, $matiere_id) {
            $request->get['matiere_id'] = (int)$matiere_id;
            return createProfessorEvaluationController()->showNotes($request, $response);
        }, 'professor.matieres.evaluations.notes.show')
            ->middleware(AuthMiddleware::professeur())
            ->with('matiere_id', '[0-9]+');

        // Route pour mettre à jour les notes
        $router->post('/matieres/:matiere_id/evaluations/notes', function(Request $request, Response $response, $matiere_id) {
            $request->get['matiere_id'] = (int)$matiere_id;
            return createProfessorEvaluationController()->updateNotes($request, $response);
        }, 'professor.matieres.evaluations.notes.update')
            ->middleware(AuthMiddleware::professeur())
            ->with('matiere_id', '[0-9]+');

        // Route pour voir les évaluations d'un étudiant
        $router->get('/matieres/:matiere_id/evaluations/student', function(Request $request, Response $response, $matiere_id) {
            $studentId = $request->get['student_id'] ?? null;
            if (!$studentId) {
                throw new \Exception('ID étudiant manquant');
            }
            return createProfessorEvaluationController()->showStudentEvaluations(
                $request, 
                $response, 
                (int)$matiere_id,
                (int)$studentId
            );
        }, 'professor.matieres.evaluations.student')
        ->middleware(AuthMiddleware::professeur())
        ->with('matiere_id', '[0-9]+');

        // Dans le groupe des routes professor
        $router->get('/matieres/:matiere_id/evaluations/details', function(Request $request, Response $response, $matiere_id) {
            $request->get['matiere_id'] = (int)$matiere_id;
            return createProfessorEvaluationController()->details($request, $response);
        }, 'professor.matieres.evaluations.details')
            ->middleware(AuthMiddleware::professeur())
            ->with('matiere_id', '[0-9]+');
    });

    // Helper functions pour les contrleurs étudiants
    function createStudentMatiereController(): \App\Controllers\Student\MatiereController {
        global $db;
        $matiereRepository = new \App\Models\Repository\MatiereRepository();
        $matiereService = new \App\Services\MatiereService($matiereRepository, $db);
        return new \App\Controllers\Student\MatiereController($matiereService);
    }

    function createStudentEvaluationController(): \App\Controllers\Student\EvaluationController {
        global $db;
        
        // Création des repositories
        $matiereRepository = new \App\Models\Repository\MatiereRepository();
        $evaluationRepository = new \App\Models\Repository\EvaluationRepository();
        
        // Création des services avec leurs dépendances
        $authService = \App\Services\AuthService::getInstance();
        $matiereService = new \App\Services\MatiereService($matiereRepository, $db);
        $evaluationService = new \App\Services\EvaluationService($evaluationRepository, $matiereRepository, $db);

        return new \App\Controllers\Student\EvaluationController(
            $authService,
            $evaluationService,
            $matiereService,
            $evaluationRepository,
            $matiereRepository
        );
    }

    // Routes du tableau de bord étudiant
    $router->group('/student', function($router) {
        // Routes des matières
        $router->get('/matieres', function(Request $request, Response $response) {
            return createStudentMatiereController()->index($request, $response);
        }, 'student.matieres')
            ->middleware(AuthMiddleware::etudiant());
        
        $router->get('/matieres/:id', function(Request $request, Response $response, $id) {
            $request->get['id'] = (int)$id;
            return createStudentMatiereController()->show($request, $response, (int)$id);
        }, 'student.matieres.show')
            ->middleware(AuthMiddleware::etudiant())
            ->with('id', '[0-9]+');

        // Routes des évaluations
        $router->get('/matieres/:matiere_id/evaluations', function(Request $request, Response $response, $matiere_id) {
            $request->get['matiere_id'] = (int)$matiere_id;
            return createStudentEvaluationController()->index($request, $response);
        }, 'student.matieres.evaluations')
            ->middleware(AuthMiddleware::etudiant())
            ->with('matiere_id', '[0-9]+');

        // Route pour toutes les évaluations
        $router->get('/evaluations/all', function(Request $request, Response $response) {
            return createStudentEvaluationController()->all($request, $response);
        }, 'student.evaluations.all')
            ->middleware(AuthMiddleware::etudiant());

        $router->get('/evaluations', function(Request $request, Response $response) {
            return createStudentEvaluationController()->index($request, $response);
        }, 'student.evaluations.index')
            ->middleware(AuthMiddleware::etudiant());
    });

    // Routes du tableau de bord professeur
    function createMatiereController(): \App\Controllers\Professor\MatiereController {
        global $db;
        $matiereRepository = new \App\Models\Repository\MatiereRepository();
        $evaluationRepository = new \App\Models\Repository\EvaluationRepository();
        $matiereService = new \App\Services\MatiereService($matiereRepository, $db);
        $evaluationService = new \App\Services\EvaluationService($evaluationRepository, $matiereRepository, $db);
        return new \App\Controllers\Professor\MatiereController($matiereService, $evaluationService);
    }

    function createProfessorEvaluationController(): \App\Controllers\Professor\EvaluationController {
        global $db;
        $matiereRepository = new \App\Models\Repository\MatiereRepository();
        $evaluationRepository = new \App\Models\Repository\EvaluationRepository();
        $matiereService = new \App\Services\MatiereService($matiereRepository, $db);
        $evaluationService = new \App\Services\EvaluationService($evaluationRepository, $matiereRepository, $db);
        $authService = \App\Services\AuthService::getInstance();
        $userService = new \App\Services\UserService(new \App\Models\Repository\UserRepository());
        
        return new \App\Controllers\Professor\EvaluationController(
            $evaluationService, 
            $matiereService, 
            $authService,
            $userService
        );
    }

    // Exécution du router
    $router->run();
} catch (RouterException $e) {
    $request = new Request();
    $response = new Response();
    
    if ($request->wantsJson()) {
        return new JsonResponse([
            'success' => false,
            'message' => $e->getMessage(),
            'code' => $e->getCode() ?: 500
        ], $e->getCode() ?: 500);
    }

    switch ($e->getCode()) {
        case RouterException::ROUTE_NOT_FOUND:
            $response->setStatusCode(404);
            ob_start();
            require __DIR__ . '/../app/views/errors/404.php';
            $content = ob_get_clean();
            $response->setContent($content);
            break;
        case RouterException::METHOD_NOT_ALLOWED:
            $response->setStatusCode(405);
            ob_start();
            require __DIR__ . '/../app/views/errors/405.php';
            $content = ob_get_clean();
            $response->setContent($content);
            break;
        default:
            $response->setStatusCode(500);
            ob_start();
            require __DIR__ . '/../app/views/errors/500.php';
            $content = ob_get_clean();
            $response->setContent($content);
            break;
    }
    
    $response->send();
    exit;

} catch (MiddlewareException $e) {
    error_log('Auth error: ' . $e->getMessage());

    $request = new Request();
    $response = new Response();
    
    if ($request->wantsJson()) {
        return new JsonResponse([
            'success' => false,
            'message' => $e->getMessage(),
            'code' => $e->getCode() ?: 500
        ], $e->getCode() ?: 500);
    }

    switch ($e->getCode()) {
        case MiddlewareException::ERROR_UNAUTHORIZED:
            $response->setStatusCode(401);
            ob_start();
            require __DIR__ . '/../app/views/errors/401.php';
            $content = ob_get_clean();
            $response->setContent($content);
            break;
        case MiddlewareException::ERROR_FORBIDDEN:
            $response->setStatusCode(403);
            ob_start();
            require __DIR__ . '/../app/views/errors/403.php';
            $content = ob_get_clean();
            $response->setContent($content);
            break;
        case MiddlewareException::ERROR_RATE_LIMIT:
            $response->setStatusCode(429);
            ob_start();
            require __DIR__ . '/../app/views/errors/429.php';
            $content = ob_get_clean();
            $response->setContent($content);
            break;
        default:
            $response->setStatusCode(500);
            ob_start();
            require __DIR__ . '/../app/views/errors/500.php';
            $content = ob_get_clean();
            $response->setContent($content);
            break;
    }
    
    $response->send();
    exit;

} catch (\Exception $e) {
    error_log('Critical error: ' . $e->getMessage());

    $request = new Request();
    $response = new Response();
    
    if ($request->wantsJson()) {
        return new JsonResponse([
            'success' => false,
            'message' => 'Une erreur interne est survenue',
            'code' => 500
        ], 500);
    }

    $response->setStatusCode(500);
    ob_start();
    require __DIR__ . '/../app/views/errors/500.php';
    $content = ob_get_clean();
    $response->setContent($content);
    $response->send();
    exit;
}
