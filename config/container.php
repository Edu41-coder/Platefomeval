<?php

use DI\ContainerBuilder;
use App\Services\MatiereService;
use App\Services\EvaluationService;
use App\Models\Repository\MatiereRepository;
use App\Controllers\Professor\MatiereController;

$builder = new ContainerBuilder();

$builder->addDefinitions([
    // Services
    MatiereService::class => function($container) {
        return new MatiereService(
            $container->get(MatiereRepository::class),
            $container->get(Database::class)
        );
    },
    
    // Controllers
    MatiereController::class => function($container) {
        return new MatiereController(
            $container->get(MatiereService::class),
            $container->get(EvaluationService::class)
        );
    }
]);

return $builder->build(); 