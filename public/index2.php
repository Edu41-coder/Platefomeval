<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap.php';

// Initialiser l'application
$app = new App\Core\Application();

try {
    // Charger la configuration
    $app->loadConfiguration();
    
    // Initialiser le conteneur de dépendances
    $container = require __DIR__ . '/../config/container.php';
    $app->setContainer($container);
    
    // Charger les routes
    require __DIR__ . '/../routes/web.php';
    
    // Démarrer l'application
    $app->run();
    
} catch (\Exception $e) {
    $app->handleException($e);
}
