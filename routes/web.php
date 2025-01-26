<?php

use App\Controllers\Professor\MatiereController;
use App\Middleware\AuthMiddleware;

/** @var Router $router */
/** @var Container $container */

// Routes publiques
$router->get('/', 'App\Controllers\Home\HomeController@index', 'home');

// Routes professeur
$router->group('/professor', function(Router $router) use ($container) {
    $router->get('/matieres', function($request, $response) use ($container) {
        return $container->get(MatiereController::class)->index($request, $response);
    }, 'professor.matieres')
    ->middleware(AuthMiddleware::professeur());
    
    $router->get('/matieres/:id', function($request, $response, $id) use ($container) {
        return $container->get(MatiereController::class)->show($request, $response, $id);
    }, 'professor.matieres.show')
    ->middleware(AuthMiddleware::professeur());
}); 