<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/bootstrap.php';

use Core\Router\Router;
use Core\Router\RouterException;
use Core\Http\JsonResponse;

// Charger les configurations
$config = require $_SERVER['DOCUMENT_ROOT'] . '/app/api/config/config.php';

// Configuration initiale
initializeApplication($config);

// Création et configuration du router
$router = createRouter();

// Définition des routes
defineRoutes($router);

// Exécution du router
handleRequest($router, $config);

/**
 * Initialise l'application avec les configurations de base
 */
function initializeApplication(array $config): void 
{
    // Configuration du timezone
    date_default_timezone_set($config['timezone']);

    // Configuration du mode debug
    if ($config['debug']) {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    } else {
        error_reporting(0);
        ini_set('display_errors', 0);
    }

    // Configuration de la session
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_secure' => !empty($_SERVER['HTTPS']),
            'cookie_samesite' => 'Lax'
        ]);
    }
}

/**
 * Crée et configure le router
 */
function createRouter(): Router 
{
    global $config;
    $url = $_SERVER['REQUEST_URI'];
    $router = new Router($url, '');

    // Le middleware Cors doit être le premier pour gérer les preflight requests
    $router->middleware('Cors');
    
    // Autres middlewares globaux
    if ($config['rate_limit']['enabled']) {
        $router->middleware('RateLimit');
    }

    return $router;
}

/**
 * Définit toutes les routes de l'API
 */
function defineRoutes(Router $router): void 
{
    // Routes pour l'authentification
    $router->group('/auth', function(Router $router) {
        // Routes publiques
        $router->post('/login', 'App\Api\Controllers\AuthApiController@login');
        $router->post('/register', 'App\Api\Controllers\AuthApiController@register');
        $router->post('/forgot-password', 'App\Api\Controllers\AuthApiController@forgotPassword');
        $router->post('/reset-password', 'App\Api\Controllers\AuthApiController@resetPassword');
        $router->get('/verify-email/:token', 'App\Api\Controllers\AuthApiController@verifyEmail')
            ->with('token', '[A-Za-z0-9]+');
        $router->post('/check-email', 'App\Api\Controllers\AuthApiController@checkEmail');
        
        // Routes protégées
        $router->post('/logout', 'App\Api\Controllers\AuthApiController@logout')
            ->middleware('Auth');
        $router->get('/me', 'App\Api\Controllers\AuthApiController@me')
            ->middleware('Auth');
        $router->post('/refresh-token', 'App\Api\Controllers\AuthApiController@refreshToken')
            ->middleware('Auth');
        $router->put('/change-password', 'App\Api\Controllers\AuthApiController@changePassword')
            ->middleware('Auth');
    });

    // Routes pour les utilisateurs
    $router->group('/users', function(Router $router) {
        $router->get('/', 'App\Api\Controllers\UserApiController@index')
            ->middleware('Auth')
            ->middleware('Admin');
        $router->get('/:id', 'App\Api\Controllers\UserApiController@show')
            ->middleware('Auth')
            ->with('id', '[0-9]+');
        $router->post('/', 'App\Api\Controllers\UserApiController@store')
            ->middleware('Auth')
            ->middleware('Admin');
        $router->put('/:id', 'App\Api\Controllers\UserApiController@update')
            ->middleware('Auth')
            ->with('id', '[0-9]+');
        $router->delete('/:id', 'App\Api\Controllers\UserApiController@destroy')
            ->middleware('Auth')
            ->middleware('Admin')
            ->with('id', '[0-9]+');
    });

    // Routes pour les évaluations
    $router->group('/evaluations', function(Router $router) {
        $router->get('/', 'App\Api\Controllers\EvaluationApiController@index')
            ->middleware('Auth');
        $router->get('/:id', 'App\Api\Controllers\EvaluationApiController@show')
            ->middleware('Auth')
            ->with('id', '[0-9]+');
        $router->post('/', 'App\Api\Controllers\EvaluationApiController@store')
            ->middleware('Auth');
        $router->put('/:id', 'App\Api\Controllers\EvaluationApiController@update')
            ->middleware('Auth')
            ->with('id', '[0-9]+');
        $router->delete('/:id', 'App\Api\Controllers\EvaluationApiController@destroy')
            ->middleware('Auth')
            ->middleware('Admin')
            ->with('id', '[0-9]+');
    });

    // Routes pour les matières
    $router->group('/matieres', function(Router $router) {
        $router->get('/', 'App\Api\Controllers\MatiereApiController@index')
            ->middleware('Auth');
        $router->get('/:id', 'App\Api\Controllers\MatiereApiController@show')
            ->middleware('Auth')
            ->with('id', '[0-9]+');
        $router->post('/', 'App\Api\Controllers\MatiereApiController@store')
            ->middleware('Auth')
            ->middleware('Admin');
        $router->put('/:id', 'App\Api\Controllers\MatiereApiController@update')
            ->middleware('Auth')
            ->middleware('Admin')
            ->with('id', '[0-9]+');
        $router->delete('/:id', 'App\Api\Controllers\MatiereApiController@destroy')
            ->middleware('Auth')
            ->middleware('Admin')
            ->with('id', '[0-9]+');
    });

    // Routes pour le profil
    $router->group('/profile', function(Router $router) {
        $router->get('/', 'App\Api\Controllers\ProfileApiController@index')
            ->middleware('Auth');
        $router->get('/show', 'App\Api\Controllers\ProfileApiController@show')
            ->middleware('Auth');
        $router->put('/update', 'App\Api\Controllers\ProfileApiController@update')
            ->middleware('Auth');
        $router->put('/update-password', 'App\Api\Controllers\ProfileApiController@updatePassword')
            ->middleware('Auth');
    });
}

/**
 * Gère l'exécution de la requête et les erreurs
 */
function handleRequest(Router $router, array $config): void 
{
    try {
        $router->run();
    } catch (RouterException $e) {
        handleRouterException($e, $config);
    } catch (\Exception $e) {
        handleGenericException($e, $config);
    }
}

/**
 * Gère les exceptions spécifiques au routeur
 */
function handleRouterException(RouterException $e, array $config): void 
{
    $statusCode = $e->getCode() ?: 500;
    $message = $config['debug'] ? $e->getMessage() : 'Erreur de routage';
    $data = $config['debug'] ? ['trace' => $e->getTrace()] : null;
    
    JsonResponse::error($message, $statusCode, $data)->send();
}

/**
 * Gère les exceptions génériques
 */
function handleGenericException(\Exception $e, array $config): void 
{
    error_log($e->getMessage());
    $message = $config['debug'] ? $e->getMessage() : 'Une erreur interne est survenue';
    $data = $config['debug'] ? ['trace' => $e->getTrace()] : null;
    
    JsonResponse::serverError($message, $data)->send();
}