<?php

namespace App\Core;

use DI\Container;
use Core\Router\Router;

class Application
{
    private Container $container;
    private Router $router;
    private array $config;
    
    public function loadConfiguration(): void
    {
        $this->config = require __DIR__ . '/../config/app.php';
    }
    
    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }
    
    public function run(): void
    {
        // Nettoyer l'URL
        $url = $this->cleanUrl($_SERVER['REQUEST_URI']);
        
        // Initialiser le router avec l'URL nettoyée
        $this->router = new Router($url);
        
        // Exécuter le router
        $this->router->run();
    }
    
    public function handleException(\Throwable $e): void
    {
        // Gérer les exceptions de manière centralisée
    }
    
    private function cleanUrl(string $url): string
    {
        // Logique de nettoyage d'URL
    }
} 